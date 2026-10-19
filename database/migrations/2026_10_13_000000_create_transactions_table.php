<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained();
            // Nullable because a pending Ingested Transaction can arrive with
            // its Category unset. The key is also what keeps a Category
            // referenced by a Transaction from being deleted.
            $table->foreignUuid('category_id')->nullable()->constrained();
            // The day the money moved, as a plain calendar date in the
            // instance timezone. Cycle boundaries compare against it whole.
            $table->date('occurred_on');
            // The amount in integer minor units of the instance currency
            // (see ADR 0003), always positive; the direction column says
            // which way it moved.
            $table->bigInteger('amount');
            // The merchant as it arrived, and the shape Merchant::normalize
            // reduced it to. The raw form is what the user reads; the
            // normalized form is what the Duplicate hash compares.
            $table->string('merchant_raw');
            $table->string('merchant_normalized');
            $table->char('dedup_hash', 64);
            // The other leg, when this Transfer is one half of an internal
            // hop. Unique because a leg has at most one partner, and the
            // uniqueness is what keeps concurrent auto-pairing from
            // double-linking a leg.
            $table->uuid('paired_transfer_id')->nullable()->unique();
            // The Upload this row was extracted from. Null on every Manual
            // Entry. The foreign key itself arrives with the uploads table.
            $table->uuid('upload_id')->nullable();
            $table->timestamps();
        });

        // The self-reference is added after the create because the primary
        // key must exist before a key can reference it.
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('paired_transfer_id')->references('id')->on('transactions');
        });

        // Enumerated columns are native Postgres enum types, created with a
        // raw statement and cast to a PHP backed enum on the model.
        DB::statement("CREATE TYPE transaction_direction AS ENUM ('in', 'out')");
        DB::statement("CREATE TYPE transaction_kind AS ENUM ('expense', 'income', 'transfer', 'fee', 'refund')");
        DB::statement("CREATE TYPE transaction_status AS ENUM ('pending_review', 'confirmed', 'rejected')");
        DB::statement("CREATE TYPE category_source AS ENUM ('rule', 'llm', 'manual')");
        DB::statement('ALTER TABLE transactions ADD COLUMN direction transaction_direction NOT NULL');
        DB::statement('ALTER TABLE transactions ADD COLUMN kind transaction_kind NOT NULL');
        DB::statement('ALTER TABLE transactions ADD COLUMN status transaction_status NOT NULL');
        DB::statement('ALTER TABLE transactions ADD COLUMN category_source category_source NOT NULL');

        // An amount is always positive; the sign lives in the direction.
        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_amount_positive CHECK (amount > 0)');

        // The dedup hash is unique per Account among Ingested Transactions —
        // the rows with an Upload — which is the backstop that keeps a
        // re-ingested line from ever landing twice. Manual Entries are
        // deliberately outside the index: the user is warned about a
        // Duplicate and may proceed anyway, because two identical purchases
        // on the same day really happen.
        DB::statement('CREATE UNIQUE INDEX transactions_dedup_hash_unique ON transactions (account_id, dedup_hash) WHERE upload_id IS NOT NULL');

        // The transactions page reads one monthly Cycle of Confirmed rows.
        DB::statement('CREATE INDEX transactions_cycle_read ON transactions (status, occurred_on)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        DB::statement('DROP TYPE IF EXISTS transaction_direction');
        DB::statement('DROP TYPE IF EXISTS transaction_kind');
        DB::statement('DROP TYPE IF EXISTS transaction_status');
        DB::statement('DROP TYPE IF EXISTS category_source');
    }
};
