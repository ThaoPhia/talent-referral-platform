<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'archived'])->default('active')->change();
            $table->date('closed_date')->nullable()->after('post_on');
        });

        DB::table('jobs')->orderBy('id')->each(function (object $job) {
            DB::table('jobs')->where('id', $job->id)->update([
                'closed_date' => Carbon::parse($job->post_on)->addMonthsNoOverflow(3)->toDateString(),
            ]);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->date('closed_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        DB::table('jobs')->where('status', 'draft')->update(['status' => 'archived']);

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn('closed_date');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->enum('status', ['active', 'archived'])->default('active')->change();
        });
    }
};
