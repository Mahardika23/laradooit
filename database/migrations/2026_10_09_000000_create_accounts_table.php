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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('institution');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        // Enumerated columns are native Postgres enum types, created with a
        // raw statement and cast to a PHP backed enum on the model.
        DB::statement("CREATE TYPE account_type AS ENUM ('bank', 'e_wallet', 'cash')");
        DB::statement('ALTER TABLE accounts ADD COLUMN type account_type NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
        DB::statement('DROP TYPE IF EXISTS account_type');
    }
};
