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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('parent_id')->nullable();
            // The Budget amount in integer minor units of the instance
            // currency (see ADR 0003), null unless the Category is budgeted.
            $table->bigInteger('budget_amount')->nullable();
            $table->timestamps();
        });

        // Deleting a Category that still has children is refused by the
        // app; this key is the last line of defence. It is added after the
        // create because the primary key must exist before a key can
        // reference it. Transactions get their own foreign key with the
        // transactions table.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories');
        });

        // Enumerated columns are native Postgres enum types, created with a
        // raw statement and cast to a PHP backed enum on the model.
        DB::statement("CREATE TYPE budget_period AS ENUM ('monthly', 'weekly')");
        DB::statement('ALTER TABLE categories ADD COLUMN budget_period budget_period NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
        DB::statement('DROP TYPE IF EXISTS budget_period');
    }
};
