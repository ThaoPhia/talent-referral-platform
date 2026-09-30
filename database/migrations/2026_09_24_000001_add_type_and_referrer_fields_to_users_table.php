<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['normal', 'admin'])->default('normal')->after('id');
            $table->string('resume_url')->nullable()->after('email');
            $table->text('note')->nullable()->after('resume_url');
        });

        // Add an admin user
        \App\Models\User::forceCreate([
            'name' => 'Phia Admin',
            'email' => 'thoj.phia+admin@gmail.com',
            'password' => bcrypt('password'),
            'type' => 'admin',
            'email_verified_at' => now(),
        ]);
        // Add a normal user
        \App\Models\User::forceCreate([
            'name' => 'Phia User',
            'email' => 'thoj.phia+user@gmail.com',
            'password' => bcrypt('password'),
            // Converted to 'referrer' by 2026_09_29_000001_add_referrer_type_to_users_table.
            'type' => 'normal',
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['type', 'resume_url', 'note']);
        });
    }
};
