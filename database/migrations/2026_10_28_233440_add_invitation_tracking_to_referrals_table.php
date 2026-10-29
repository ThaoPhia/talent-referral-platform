<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('referral_duplicate_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_referral_id')->unique();
            $table->unsignedBigInteger('retained_referral_id');
            $table->longText('original_data');
            $table->timestamp('archived_at');
        });

        // The newest ID wins; preserve every displaced row verbatim for recovery.
        DB::transaction(function () {
            $pairs = DB::table('referrals')
                ->select('job_id', 'candidate_id')
                ->selectRaw('MAX(id) as retained_id')
                ->groupBy('job_id', 'candidate_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($pairs as $pair) {
                $duplicates = DB::table('referrals')
                    ->where('job_id', $pair->job_id)
                    ->where('candidate_id', $pair->candidate_id)
                    ->where('id', '<>', $pair->retained_id)
                    ->get();

                foreach ($duplicates as $duplicate) {
                    DB::table('referral_duplicate_archive')->insert([
                        'original_referral_id' => $duplicate->id,
                        'retained_referral_id' => $pair->retained_id,
                        'original_data' => json_encode($duplicate, JSON_THROW_ON_ERROR),
                        'archived_at' => now(),
                    ]);
                    DB::table('referrals')->where('id', $duplicate->id)->delete();
                }
            }
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->timestamp('viewed_at')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('viewed_at');
            // Invitation links are keyed by job + candidate, so each pair must be unique.
            $table->unique(['job_id', 'candidate_id']);
        });

    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropUnique(['job_id', 'candidate_id']);
            $table->dropColumn(['viewed_at', 'responded_at']);
        });

        if (Schema::hasTable('referral_duplicate_archive')) {
            DB::transaction(function () {
                foreach (DB::table('referral_duplicate_archive')->orderBy('id')->get() as $archived) {
                    DB::table('referrals')->insert(json_decode($archived->original_data, true, 512, JSON_THROW_ON_ERROR));
                }
            });
            Schema::drop('referral_duplicate_archive');
        }
    }
};
