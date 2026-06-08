<?php

use App\Models\TemporalIoUResult;
use App\Models\StationResult;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

new
    #[Title('Validasi IOU')] class extends Component {

    public $kpis = [];
    public $stations = [];
    public $iouResults = [];

    public $selectedStation = null;
    public $segments = [
        [
            'activity' => '',
            'start' => '',
            'end' => '',
        ]
    ];

    protected $rules = [
        'selectedStation' => 'required',

        'segments.*.activity' => 'required|string|max:255',
        'segments.*.start' => 'required|numeric|min:0',
        'segments.*.end' => 'required|numeric|gt:segments.*.start',
    ];

    public function mount()
    {
        $this->stations = StationResult::orderBy('station_order')->get();

        $this->iouResults();
        $this->refreshKpi();
    }

    #[Computed]
    public function iouResults()
    {
        $this->iouResults = TemporalIouResult::with('station')
            ->get();
    }


    public function refreshKpi()
    {
        $avg = TemporalIouResult::avg('avg_iou') ?? 0;

        $total = TemporalIouResult::count();

        $needFix = TemporalIouResult::where('avg_iou', '<', 0.5)->count();
        $stationsCount = $this->iouResults->pluck('station_id')->unique()->count();

        if ($avg >= 0.70) {
            $statusIoU = '✓ Baik (≥0.70)';
        } elseif ($avg >= 0.50) {
            $statusIoU = 'Cukup (≥0.50)';
        } else {
            $statusIoU = 'Perlu Perbaikan (<0.50)';
        }

        $this->kpis = [

            [
                'label' => 'Rata-rata IoU',
                'value' => $avg,
                'unit' => '',
                'accent' => '#22C55E',
                'note' => $statusIoU
            ],

            [
                'label' => 'Aktivitas Diuji',
                'value' => $total,
                'unit' => '',
                'accent' => '#3B82F6',
                'note' => $stationsCount . ' Stasiun'
            ],

            [
                'label' => 'Perlu Perbaikan',
                'value' => $needFix,
                'unit' => '',
                'accent' => '#F97316',
                'note' => $needFix > 0
                    ? 'IoU < 0.50'
                    : 'Tidak ada'
            ],
        ];
    }


    public function addSegment()
    {
        $this->segments[] = [
            'activity' => '',
            'start' => '',
            'end' => '',
        ];
    }

    public function removeSegment($index)
    {
        unset($this->segments[$index]);
        $this->segments = array_values($this->segments); // reindex array
    }

    public function calculateIoU()
    {
        $this->validate(); // WAJIB sebelum proses

        foreach ($this->segments as $segment) {

            $start = floatval($segment['start']);
            $end = floatval($segment['end']);

            $predStart = $start;
            $predEnd = $end;

            $gtStart = $start;
            $gtEnd = $end;

            $intersection = max(
                0,
                min($predEnd, $gtEnd) - max($predStart, $gtStart)
            );

            $union = max($predEnd, $gtEnd) - min($predStart, $gtStart);

            $iou = $union > 0 ? $intersection / $union : 0;

            $status =
                $iou >= 0.70 ? 'Baik' :
                ($iou >= 0.50 ? 'Cukup' : 'Perlu Perbaikan');

            TemporalIouResult::create([
                'station_id' => $this->selectedStation,
                'calculated_by' => Auth::id(),
                'calculated_at' => now(),

                'activity' => $segment['activity'],

                'n_samples_pred' => 1,
                'n_samples_gt' => 1,

                'total_intersection' => $intersection,
                'total_union' => $union,

                'avg_iou' => $iou,
                'keterangan' => $status
            ]);
        }

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: 'IoU berhasil dihitung!'
        );

        $this->iouResults();
        $this->refreshKpi();
    }
};
