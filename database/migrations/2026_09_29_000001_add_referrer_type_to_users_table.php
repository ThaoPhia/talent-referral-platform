<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'referrer', 'admin'])->default('referrer')->change();
        });

        // Existing members become referrers; only referred candidates stay 'normal'.
        DB::table('users')
            ->where('type', 'normal')
            ->whereNotIn('id', DB::table('referrals')->select('referrer_id'))
            ->update(['type' => 'referrer']);
    }

    public function down(): void
    {
        DB::table('users')->where('type', 'referrer')->update(['type' => 'normal']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'admin'])->default('normal')->change();
        });
    }
};
