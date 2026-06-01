<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN hanya untuk MySQL (TiDB Cloud)
        // SQLite tidak perlu karena sudah pakai TEXT, bukan ENUM
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE analysis_jobs
                MODIFY COLUMN status
                ENUM('pending','queued','processing','completed','failed')
                NOT NULL DEFAULT 'pending'
            ");
        }

        Schema::table('analysis_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('analysis_jobs', 'progress_current')) {
                $table->unsignedInteger('progress_current')->default(0);
            }
            if (! Schema::hasColumn('analysis_jobs', 'progress_total')) {
                $table->unsignedInteger('progress_total')->default(0);
            }
            if (! Schema::hasColumn('analysis_jobs', 'progress_message')) {
                $table->string('progress_message', 255)->default('');
            }
            if (! Schema::hasColumn('analysis_jobs', 'chart_base64')) {
                $table->longText('chart_base64')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE analysis_jobs
                MODIFY COLUMN status
                ENUM('processing','completed','failed')
                NOT NULL DEFAULT 'processing'
            ");
        }

        Schema::table('analysis_jobs', function (Blueprint $table) {
            $cols = ['progress_current', 'progress_total', 'progress_message', 'chart_base64'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('analysis_jobs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};