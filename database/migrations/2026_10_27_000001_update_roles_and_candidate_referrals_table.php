<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'candidate', 'referrer', 'admin'])->default('referrer')->change();
        });

        DB::table('users')->where('type', 'normal')->update(['type' => 'candidate']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['candidate', 'referrer', 'admin'])->default('referrer')->change();
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->renameColumn('referrer_id', 'candidate_id');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreign('candidate_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['candidate_id']);
            $table->renameColumn('candidate_id', 'referrer_id');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreign('referrer_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'candidate', 'referrer', 'admin'])->default('referrer')->change();
        });

        DB::table('users')->where('type', 'candidate')->update(['type' => 'normal']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'referrer', 'admin'])->default('referrer')->change();
        });
    }
};
