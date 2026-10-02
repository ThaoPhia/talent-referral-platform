<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'referrer', 'recruiter', 'admin'])->default('recruiter')->change();
        });

        DB::table('users')->where('type', 'referrer')->update(['type' => 'recruiter']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'recruiter', 'admin'])->default('recruiter')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'referrer', 'recruiter', 'admin'])->default('referrer')->change();
        });

        DB::table('users')->where('type', 'recruiter')->update(['type' => 'referrer']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'referrer', 'admin'])->default('referrer')->change();
        });
    }
};
