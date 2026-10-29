<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Either an external http(s) URL or a path on the private "resumes" disk.
            $table->string('candidate_resume', 2048)->nullable()->after('candidate_note');
        });

        DB::table('referrals')->update([
            'candidate_resume' => DB::raw('(select users.resume_url from users where users.id = referrals.candidate_id)'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('resume_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('resume_url')->nullable()->after('email');
        });

        // Uploaded files can't be represented on the user, so only links are restored.
        DB::table('users')->update([
            'resume_url' => DB::raw("(select referrals.candidate_resume from referrals where referrals.candidate_id = users.id and (referrals.candidate_resume like 'http://%' or referrals.candidate_resume like 'https://%') order by referrals.id desc limit 1)"),
        ]);

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('candidate_resume');
        });
    }
};
