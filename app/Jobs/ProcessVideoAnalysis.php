<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Services\CvAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideoAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries   = 2;
    public int $backoff = 10;

    public function __construct(
        public readonly int   $jobId,
        public readonly array $videoMap,
        public readonly array $metadata,
    ) {}

    public function handle(): void
    {
        $job = AnalysisJob::findOrFail($this->jobId);

        Log::info("[ProcessVideoAnalysis] Mulai job #{$this->jobId}");

        try {
            $service = new CvAnalysisService();
            $result  = $service->uploadToFlask($this->videoMap, $this->metadata);

            if (empty($result['job_id'])) {
                throw new \RuntimeException('Flask tidak mengembalikan job_id. Response: ' . json_encode($result));
            }

            $job->update([
                'python_job_id' => $result['job_id'],
                'status'        => 'processing',
            ]);

            Log::info("[ProcessVideoAnalysis] Selesai job #{$this->jobId}, python_job_id={$result['job_id']}");

            // ════════════════════════════════════════════════════════
            // HAPUS VIDEO LOKAL setelah BERHASIL dikirim ke HuggingFace
            // (HF sudah pegang video & job_id → file lokal tidak dibutuhkan)
            // Ini mencegah volume Railway penuh
            // ════════════════════════════════════════════════════════
            $this->cleanupVideos();

        } catch (\Throwable $e) {
            Log::error("[ProcessVideoAnalysis] Gagal job #{$this->jobId}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $job->update(['status' => 'failed', 'error_msg' => $e->getMessage()]);
                // Hapus video juga kalau sudah final gagal (tidak akan di-retry lagi)
                $this->cleanupVideos();
            }

            throw $e;
        }
    }

    /**
     * Hapus file video lokal + folder job-nya dari storage.
     * Dipanggil setelah video berhasil dikirim ke HF (atau final gagal).
     */
    private function cleanupVideos(): void
    {
        try {
            $disk = Storage::disk('public');

            foreach ($this->videoMap as $relativePath) {
                if ($disk->exists($relativePath)) {
                    $disk->delete($relativePath);
                }
            }

            // Hapus folder job kalau sudah kosong (analisis_videos/{jobId})
            $jobFolder = "analisis_videos/{$this->jobId}";
            if ($disk->exists($jobFolder)) {
                $disk->deleteDirectory($jobFolder);
            }

            Log::info("[ProcessVideoAnalysis] Video lokal job #{$this->jobId} dibersihkan.");
        } catch (\Throwable $e) {
            // Cleanup gagal tidak boleh menggagalkan job utama — cukup log
            Log::warning("[ProcessVideoAnalysis] Gagal cleanup video job #{$this->jobId}: " . $e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[ProcessVideoAnalysis] Job #{$this->jobId} FINAL GAGAL: " . $exception->getMessage());

        AnalysisJob::where('id', $this->jobId)->update(['status' => 'failed']);

        // Pastikan video tetap dibersihkan walau job gagal total
        $this->cleanupVideos();
    }
}
