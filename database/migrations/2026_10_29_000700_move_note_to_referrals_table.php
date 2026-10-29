<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->text('referrer_note')->nullable()->after('status');
            $table->text('candidate_note')->nullable()->after('referrer_note');
        });

        // The referral form used to store its note on the candidate's user record.
        DB::table('referrals')->update([
            'referrer_note' => DB::raw('(select users.note from users where users.id = referrals.candidate_id)'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('note')->nullable()->after('resume_url');
        });

        DB::table('users')->update([
            'note' => DB::raw('(select referrals.referrer_note from referrals where referrals.candidate_id = users.id and referrals.referrer_note is not null order by referrals.id desc limit 1)'),
        ]);

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn(['referrer_note', 'candidate_note']);
        });
    }
};
