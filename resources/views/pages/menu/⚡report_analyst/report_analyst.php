<?php

use App\Exports\LineAnalysisExport;
use App\Jobs\ProcessVideoAnalysis;
use App\Models\AnalysisJob;
use App\Models\StationResult;
use App\Models\WorkElement;
use App\Services\CvAnalysisService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

new
    #[Title('Report Analyst')] class extends Component {

    public $taktTime;
    public $n_stations;
    public $operators;
    public $target;

    public $stations     = [];
    public $meanCT       = [];
    public $robustCT     = [];
    public $cvData       = [];
    public $kpis         = [];
    public $elements     = [];
    public $metrics      = [];
    public $elementsData = [];

    public $bottleneckCount   = 0;
    public $maxCV             = 0;
    public $selectedStation   = null;

    public $isProcessing      = false;
    public $processingMessage = 'Sedang menganalisis video...';

    public $isFailed     = false;
    public $errorMessage = '';

    // ID job yang sedang ditampilkan (dari query ?job_id=, atau job terbaru jika kosong)
    public $jobId = null;

    public function mount()
    {
        // Poin 4: kalau dibuka dari Riwayat (?job_id=X), tampilkan job ITU.
        // Kalau tidak ada job_id, fallback ke job terbaru (alur upload baru).
        $queryJobId = request()->query('job_id');
        $job = $queryJobId
            ? AnalysisJob::find($queryJobId)
            : AnalysisJob::latest()->first();

        if (!$job) return;

        // Simpan id job aktif agar dipakai konsisten oleh polling & retry
        $this->jobId = $job->id;

        // 'pending'    = baru didispatch, worker queue belum upload ke Flask
        // 'processing' = Flask sedang menganalisa video di background
        // Keduanya dianggap "masih berjalan": tampilkan loading dan biarkan
        // wire:poll terus cek status sampai 'completed' (atau 'failed').
        if (in_array($job->status, ['pending', 'processing'])) {
            $this->isProcessing      = true;
            $this->processingMessage = $job->progress_message ?: 'Sedang menganalisis video... mohon tunggu.';
            return;
        }

        if ($job->status === 'completed') {
            $this->loadFromDb($job);
            return;
        }

        if ($job->status === 'failed') {
            $this->isFailed     = true;
            $this->errorMessage = $job->error_msg ?: 'Analisis gagal diproses oleh server.';
        }
    }

    /**
     * Polling otomatis setiap 3 detik via wire:poll.
     */
    public function checkFlaskStatus()
    {
        if (!$this->isProcessing) return;

        $job = $this->jobId ? AnalysisJob::find($this->jobId) : AnalysisJob::latest()->first();
        if (!$job) return;

        try {
            $service = new CvAnalysisService();
            $status  = $service->checkAndSync($job->id);

            if ($status === 'processing' || $status === 'pending') {
                $job->refresh();
                $base = $job->progress_message ?: 'Sedang menganalisis video... mohon tunggu.';
                // Tampilkan progress X/Y video kalau Flask sudah melaporkannya
                if (($job->progress_total ?? 0) > 0) {
                    $base .= " ({$job->progress_current}/{$job->progress_total} video)";
                }
                $this->processingMessage = $base;
                return;
            }

            if ($status === 'failed') {
                $job->refresh();
                $this->isProcessing = false;
                $this->isFailed     = true;
                $this->errorMessage = $job->error_msg ?: 'Analisis gagal diproses oleh server.';
                $this->dispatch('swal-toast', icon: 'error', title: 'Gagal', text: 'Analisis gagal diproses oleh server.');
                return;
            }

            if ($status === 'completed') {
                $this->isProcessing = false;
                $this->loadFromDb($job->fresh());
                $this->dispatch('swal-toast', icon: 'success', title: 'Selesai', text: 'Analisis berhasil diselesaikan!');
                $this->dispatch('results-ready');
            }

        } catch (\Exception $e) {
            Log::error('checkFlaskStatus error: ' . $e->getMessage());
            $this->dispatch('swal-toast', icon: 'error', title: 'Error', text: substr($e->getMessage(), 0, 200));
        }
    }

    /**
     * Tombol manual — klik untuk cek status sekarang (fallback jika wire:poll belum dipasang).
     */
    public function manualCheck()
    {
        $this->isProcessing = true;
        $this->checkFlaskStatus();
    }

    /**
     * Coba lagi: jalankan ulang analisa dari video yang SUDAH tersimpan
     * (tidak perlu upload ulang dari awal). Dipakai saat status job = failed.
     */
    public function retryAnalysis(): void
    {
        $job = $this->jobId ? AnalysisJob::find($this->jobId) : AnalysisJob::latest()->first();
        if (!$job) return;

        // Susun ulang videoMap dari file yang tersimpan di storage
        $files = Storage::disk('public')->files("analisis_videos/{$job->id}");
        if (empty($files)) {
            $this->dispatch('swal-toast', icon: 'error', title: 'Tidak bisa coba lagi',
                text: 'Video sumber tidak ditemukan. Silakan upload ulang.');
            return;
        }

        $videoMap = [];
        foreach ($files as $path) {
            $videoMap[pathinfo($path, PATHINFO_FILENAME)] = $path;
        }

        // Reset job ke kondisi awal lalu dispatch ulang ke queue
        $job->update([
            'status'           => 'pending',
            'error_msg'        => null,
            'python_job_id'    => null,
            'progress_current' => 0,
            'progress_total'   => 0,
            'progress_message' => '',
        ]);

        ProcessVideoAnalysis::dispatch($job->id, $videoMap, [
            'output_harian' => $job->output_harian,
            'mp_aktual'     => $job->mp_aktual,
            'nama_line'     => $job->line_name,
            'nama_bagian'   => $job->part_name ?? $job->line_name,
        ]);

        $this->reset('isFailed', 'errorMessage');
        $this->isProcessing      = true;
        $this->processingMessage = 'Mencoba lagi... mengirim ulang ke server analisa.';
    }

    /**
     * Kembali ke halaman upload untuk mulai analisa baru dari awal.
     */
    public function goToUpload()
    {
        return $this->redirectRoute('menu.analyst', navigate: true);
    }

    /**
     * Load data dari DB ke properti Livewire.
     */
    private function loadFromDb(AnalysisJob $job): void
    {
        $this->taktTime   = $job->takt_time;
        $this->operators  = $job->n_stations;
        $this->target     = $job->output_harian;
        $this->n_stations = $job->n_stations;

        $stations = StationResult::where('job_id', $job->id)
            ->orderBy('station_order')
            ->get();

        $this->stations  = $stations->pluck('station_name')->toArray();
        $this->meanCT    = $stations->pluck('mean_ct')->toArray();
        $this->robustCT  = $stations->pluck('robust_ct')->toArray();
        $this->cvData    = $stations->pluck('cv_persen')->toArray();

        $this->kpis = [
            ['label' => 'Line Efficiency',    'value' => $job->line_efficiency  ?? 0, 'target' => 75,           'unit' => '%',    'direction' => 'higher', 'accent' => '#3D7A99'],
            ['label' => 'Balance Delay',      'value' => $job->balance_delay    ?? 0, 'target' => 15,           'unit' => '%',    'direction' => 'lower',  'accent' => '#2C8C83'],
            ['label' => 'Smoothness Index',   'value' => $job->smoothness_index ?? 0, 'target' => 40,           'unit' => '',     'direction' => 'lower',  'accent' => '#FA6868'],
            ['label' => 'Output Aktual/Hari', 'value' => $job->line_output_hari ?? 0, 'target' => $this->target,'unit' => ' pcs', 'direction' => 'higher', 'accent' => '#312E81'],
        ];

        $this->bottleneckCount = $stations->where('status_station', 'Bottleneck')->count();
        $this->maxCV           = $stations->max('cv_persen') ?? 0;

        $this->elementsData = [];
        foreach ($stations as $station) {
            $this->elementsData[$station->station_name] = WorkElement::where('station_id', $station->id)->get();
        }
    }

    public function export()
    {
        $data = [
            'kpis'     => $this->kpis,
            'stations' => $this->stations,
            'meanCT'   => $this->meanCT,
            'robustCT' => $this->robustCT,
            'cvData'   => $this->cvData,
            'elements' => WorkElement::all(),
        ];

        return Excel::download(new LineAnalysisExport($data), 'line_analysis.xlsx');
    }

    public function startSimulation()
    {
        return redirect()->route('menu.simulation');
    }
};
