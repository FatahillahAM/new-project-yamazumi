<?php

use App\Jobs\ProcessVideoAnalysis;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\AnalysisJob;
use Illuminate\Support\Facades\Auth;

new
    #[Title('Setup Analyst')]
    class extends Component {

    use WithFileUploads;

    public $step = 1;

    public $line_name;
    public $part_name;
    public $output_harian;
    public $brand;
    public $style;
    public $mp_aktual;

    public $file_list    = [];
    public $station_name = [];

    public $effectiveHours = 7.2;

    protected $rules = [
        'line_name'       => 'required|string|max:100',
        'part_name'       => 'nullable|string|max:150',
        'style'           => 'nullable|string|max:100',
        'brand'           => 'nullable|string|max:100',
        'output_harian'   => 'required|integer|min:1',
        'mp_aktual'       => 'required|integer|min:1',
        'file_list.*'     => 'required|file|mimes:mp4,avi,mov,mkv,mts|max:204800',
        'station_name.*'  => 'required|string|max:100',
    ];

    /**
     * Hook generic updated() — PASTI fire untuk SEMUA perubahan property,
     * termasuk array file upload (yang updatedFileList() tidak fire di LW4).
     *
     * $field bisa berupa: 'line_name', 'output_harian', 'file_list',
     *                     'file_list.0', 'file_list.1', dst.
     */
    public function updated($field)
    {
        // Kalau yang berubah ada hubungannya dengan file_list → sync station_name
        if (str_starts_with($field, 'file_list')) {
            $this->syncStationNames();
        }

        $this->updateStep();
    }

    /**
     * Sinkronkan station_name dengan file_list.
     * Pertahankan nama yang sudah diedit user, isi default untuk file baru.
     */
    private function syncStationNames(): void
    {
        $newNames = [];
        foreach ($this->file_list as $i => $file) {
            // Pertahankan nama lama kalau ada, kalau tidak ambil dari nama file
            if (isset($this->station_name[$i]) && !empty($this->station_name[$i])) {
                $newNames[$i] = $this->station_name[$i];
            } else {
                $newNames[$i] = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
            }
        }
        $this->station_name = $newNames;
    }

    private function updateStep(): void
    {
        if (!empty($this->file_list)) {
            $this->step = 3;
        } elseif ($this->line_name && $this->output_harian) {
            $this->step = 2;
        } else {
            $this->step = 1;
        }
    }

    public function removeVideo($i)
    {
        unset($this->file_list[$i]);
        unset($this->station_name[$i]);

        $this->file_list    = array_values($this->file_list);
        $this->station_name = array_values($this->station_name);

        $this->updateStep();
    }

    public function getTaktTimeProperty()
    {
        if (!$this->output_harian) return null;

        return round(($this->effectiveHours * 3600) / $this->output_harian, 2);
    }

    public function resetForm()
    {
        $this->reset();
        $this->step = 1;
    }

    public function save()
    {
        $this->validate();

        $jamKerjaDetik = $this->effectiveHours * 3600;
        $taktTime      = round($jamKerjaDetik / $this->output_harian, 2);

        // 1. Buat job di DB
        $job = AnalysisJob::create([
            'user_id'         => Auth::id(),
            'line_name'       => $this->line_name,
            'part_name'       => $this->part_name,
            'style'           => $this->style,
            'brand'           => $this->brand,
            'output_harian'   => $this->output_harian,
            'mp_aktual'       => (int) $this->mp_aktual,
            'jam_kerja_detik' => $jamKerjaDetik,
            'takt_time'       => $taktTime,
            'n_stations'      => count($this->file_list),
            'status'          => 'pending',
        ]);

        // 2. Simpan video ke storage
        $videoMap = [];
        foreach ($this->file_list as $i => $file) {
            $name = $this->station_name[$i] ?? 'Stasiun ' . ($i + 1);
            $ext  = $file->getClientOriginalExtension();

            $file->storeAs("analisis_videos/{$job->id}", "{$name}.{$ext}", 'public');

            $videoMap[$name] = "analisis_videos/{$job->id}/{$name}.{$ext}";
        }

        // 3. Metadata untuk Flask
        $metadata = [
            'output_harian' => $this->output_harian,
            'mp_aktual'     => $this->mp_aktual,
            'nama_line'     => $this->line_name,
            'nama_bagian'   => $this->part_name ?? $this->line_name,
        ];

        // 4. Dispatch ke queue (atau sync kalau QUEUE_CONNECTION=sync)
        ProcessVideoAnalysis::dispatch($job->id, $videoMap, $metadata);

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: 'Video tersimpan. Sedang mengirim ke server analisa...'
        );

        $this->redirectRoute('menu.report', navigate: true);
    }
};
