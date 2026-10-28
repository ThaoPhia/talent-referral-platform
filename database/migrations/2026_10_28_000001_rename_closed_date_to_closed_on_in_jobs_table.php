<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * SQLite ignores PRAGMA foreign_keys inside a transaction, and dropping the
     * jobs table with foreign keys on would cascade-delete every referral.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // MySQL already placed the column after post_on; other drivers don't support column order.
            Schema::table('jobs', function (Blueprint $table) {
                $table->renameColumn('closed_date', 'closed_on');
            });

            return;
        }

        // SQLite appends new columns, so rebuild the table to place closed_on right after post_on.
        Schema::withoutForeignKeyConstraints(function () {
            DB::transaction(function () {
                Schema::create('jobs_reordered', function (Blueprint $table) {
                    $table->id();
                    $table->string('title');
                    $table->text('description');
                    $table->string('location');
                    $table->dateTime('post_on');
                    $table->date('closed_on');
                    $table->enum('status', ['draft', 'active', 'archived'])->default('active');
                    $table->timestamps();
                });

                DB::statement('insert into jobs_reordered (id, title, description, location, post_on, closed_on, status, created_at, updated_at)
                    select id, title, description, location, post_on, closed_date, status, created_at, updated_at from jobs');

                Schema::drop('jobs');
                Schema::rename('jobs_reordered', 'jobs');
            });
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->renameColumn('closed_on', 'closed_date');
        });
    }
};
