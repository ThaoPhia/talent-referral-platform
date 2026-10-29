<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferralDuplicateMigrationTest extends TestCase
{
    public function test_migration_preserves_latest_pair_and_archives_original_duplicates_with_reversible_rollback(): void
    {
        $originalConnection = config('database.default');
        config([
            'database.connections.migration_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'database.default' => 'migration_test',
        ]);

        try {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('job_id');
                $table->string('status');
                $table->timestamps();
            });
            $rows = [
                ['id' => 1, 'user_id' => 10, 'candidate_id' => 20, 'job_id' => 30, 'status' => 'accepted', 'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-02 12:00:00'],
                ['id' => 2, 'user_id' => 11, 'candidate_id' => 20, 'job_id' => 30, 'status' => 'pending', 'created_at' => '2026-10-02 12:00:00', 'updated_at' => '2026-10-02 12:00:00'],
                ['id' => 3, 'user_id' => 12, 'candidate_id' => 20, 'job_id' => 30, 'status' => 'rejected', 'created_at' => '2026-10-03 12:00:00', 'updated_at' => '2026-10-03 12:00:00'],
                ['id' => 4, 'user_id' => 10, 'candidate_id' => 21, 'job_id' => 30, 'status' => 'pending', 'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-01 12:00:00'],
            ];
            DB::table('referrals')->insert($rows);
            $migration = require database_path('migrations/2026_10_28_233440_add_invitation_tracking_to_referrals_table.php');
            $migration->up();

            $this->assertSame([3, 4], DB::table('referrals')->orderBy('id')->pluck('id')->all());
            $archived = DB::table('referral_duplicate_archive')->orderBy('original_referral_id')->get();
            $this->assertCount(2, $archived);
            foreach ($archived as $index => $record) {
                $this->assertSame(3, $record->retained_referral_id);
                $this->assertEquals($rows[$index], json_decode($record->original_data, true, 512, JSON_THROW_ON_ERROR));
            }
            $this->assertTrue(Schema::hasIndex('referrals', ['job_id', 'candidate_id'], 'unique'));

            $migration->down();
            $this->assertFalse(Schema::hasTable('referral_duplicate_archive'));
            $this->assertEquals($rows, DB::table('referrals')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge('migration_test');
        }
    }
}
