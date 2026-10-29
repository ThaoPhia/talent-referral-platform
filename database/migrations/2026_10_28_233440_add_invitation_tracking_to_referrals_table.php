<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
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
    }
};
