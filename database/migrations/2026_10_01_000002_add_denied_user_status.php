<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'inactive', 'denied'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('status', 'denied')->update(['status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'inactive'])->default('inactive')->change();
        });
    }
};
