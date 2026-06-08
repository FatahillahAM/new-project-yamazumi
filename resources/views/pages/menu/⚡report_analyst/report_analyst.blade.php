<div class="flex flex-col gap-6" wire:poll.3000ms="checkFlaskStatus">

    {{-- LOADING STATE --}}
    @if($isProcessing)
    <div class="flex flex-col items-center justify-center py-32 gap-6">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent"></div>
        <p class="text-gray-500 dark:text-neutral-400 text-lg">{{ $processingMessage }}</p>
        <flux:button wire:click="manualCheck" variant="outline" icon="arrow-path">
            Cek Status Sekarang
        </flux:button>
    </div>

    {{-- FAILED STATE --}}
    @elseif($isFailed)
    <div class="flex flex-col items-center justify-center py-32 gap-5 text-center">
        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30">
            <flux:icon.exclamation-triangle class="h-8 w-8 text-red-500" />
        </div>
        <div>
            <flux:heading size="lg" class="text-red-600 dark:text-red-400">Analisis Gagal</flux:heading>
            <p class="mt-2 max-w-xl text-sm text-gray-500 dark:text-neutral-400">{{ $errorMessage }}</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="retryAnalysis" icon="arrow-path" variant="primary">
                Coba Lagi
            </flux:button>
            <flux:button wire:click="goToUpload" icon="arrow-up-tray" variant="outline">
                Upload Ulang
            </flux:button>
        </div>
    </div>
    @else
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" class="font-semibold">
                Report Analyst — Line Jas B
                <flux:subheading class="flex items-center text-sm text-neutral-500 space-x-2">
                    <span>Station: {{ $n_stations }}</span>
                    <span>·</span>
                    <span>Takt Time: {{ number_format($taktTime, 1) }}s</span>
                    <span>·</span>
                    <span>Output: {{ $target }} pcs/hari</span>
                </flux:subheading>
            </flux:heading>
        </div>
        <div class="flex items-center gap-2 justify-end">
            <div class="flex gap-2">
                <flux:badge size="sm" class="text-xs" icon="exclamation-triangle" variant="micro" color="red">
                    {{ $bottleneckCount }} Bottleneck Aktif
                </flux:badge>

                <flux:badge size="sm" class="text-xs" icon="exclamation-triangle" variant="micro" color="amber">
                    High Risk Statio {{ number_format($this->maxCV, 1) }}%
                </flux:badge>
            </div>
            <div class="flex items-center gap-2 justify-end">
                <flux:button wire:click="startSimulation" icon="clock" variant="primary">
                    Mulai Simulasi
                </flux:button>

                <flux:button wire:click="export" icon="cloud-arrow-down" variant="primary" color="blue">
                    Export Data
                </flux:button>
            </div>
        </div>
    </div>

    {{-- KPI GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($kpis as $kpi)
            @php
                $delta = $kpi['value'] - $kpi['target'];
                $isGood = $kpi['direction'] === 'higher'
                    ? $delta >= 0
                    : $delta <= 0;
                if ($delta == 0) {
                    $arrow = '→';
                    $color = 'text-yellow-500';
                } elseif ($isGood) {
                    $arrow = '↑';
                    $color = 'text-green-500';
                } else {
                    $arrow = '↓';
                    $color = 'text-red-500';
                }
                $note = "vs target {$kpi['target']}{$kpi['unit']}";
            @endphp

            <div class="group cursor-pointer transition duration-500 hover:-translate-y-2">
                <flux:card class="relative overflow-hidden py-4 px-6 rounded-2xl shadow-md transition-all duration-500"
                    style="border-top:4px solid {{ $kpi['accent'] }}">
                    <div class="text-md font-semibold text-gray-700 dark:text-neutral-200">
                        {{ $kpi['label'] }}
                    </div>
                    <div class="mt-3 text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ number_format($kpi['value'], 1) }}
                        <span class="text-base font-medium text-gray-500 dark:text-neutral-400">
                            {{ $kpi['unit'] }}
                        </span>
                    </div>
                    <div class="mt-3 text-xs uppercase flex items-center gap-1 text-gray-500">
                        <span class="{{ $color }} font-semibold">
                            {{ $arrow }} {{ number_format(abs($delta), 1) }}{{ $kpi['unit'] }}
                        </span>
                        <span>
                            {{ $note }}
                        </span>
                    </div>
                </flux:card>
            </div>
        @endforeach
    </div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-2 xl:grid-cols-6 gap-6">
        {{-- CHART --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-4">
            <flux:heading size="md" class="mb-4 font-semibold">
                Cycle Time per Stasiun
                <flux:subheading class="flex items-center text-xs text-neutral-500 space-x-2">
                    <span>
                        Mean CT vs Robust CT (μ+2σ)
                    </span>
                    <span>·</span>
                    <span> Takt Time: {{ number_format($taktTime, 1) }}s</span>
                </flux:subheading>
            </flux:heading>

            <div wire:ignore class="mt-4 border-t flex justify-center items-center w-full">
                <div id="ctChart" class="h-70 w-full"></div>
            </div>
        </flux:card>

        {{-- STATION STATUS --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-2">
            <flux:heading size="md" class="mb-4 font-semibold">
                Ringkasan Variabilitas
                <flux:subheading class="flex items-center text-xs text-neutral-500 space-x-2">
                    <span>
                        {{ $operators }} Operator
                    </span>
                    <span>·</span>
                    <span> Takt Time: {{ number_format($taktTime, 1) }}s</span>
                </flux:subheading>
            </flux:heading>
            <div class="pt-4 border-t"></div>
            <flux:table class="w-full text-sm">
                <flux:table.columns class="font-medium bg-gray-100 dark:bg-neutral-700">
                    <flux:table.column class="!px-4">Stasiun</flux:table.column>
                    <flux:table.column align="center">CT</flux:table.column>
                    <flux:table.column align="center">CV</flux:table.column>
                    <flux:table.column align="center" class="!px-4">Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($stations as $i => $station)
                        @php
                            $ct = $meanCT[$i];
                            $cv = $cvData[$i];

                            if ($ct > $taktTime) {
                                $status = 'bottleneck';
                                $color = 'red';
                            } elseif ($ct > $taktTime * 0.9) {
                                $status = 'at-risk';
                                $color = 'amber';
                            } elseif ($ct < $taktTime * 0.75) {
                                $status = 'underloaded';
                                $color = 'blue';
                            } else {
                                $status = 'balanced';
                                $color = 'green';
                            }

                            // Warna berdasarkan CV
                            if ($cv < 10) {
                                $cvColor = 'green';
                            } elseif ($cv > 20) {
                                $cvColor = 'red';
                            } else {
                                $cvColor = 'yellow';
                            }
                        @endphp

                        <flux:table.row>
                            <flux:table.cell align="left" class="!px-4 !align-top max-w-[180px]">
                                <div
                                    class="font-medium text-sm leading-tight whitespace-normal break-words text-gray-800 dark:text-gray-100"
                                    title="{{ $station }}">
                                    {{ $station }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell align="center" class="!align-top whitespace-nowrap">
                                <div class="font-medium text-sm text-{{ $color }}-600">
                                    {{ number_format($ct, 1) }}s
                                </div>
                            </flux:table.cell>

                            <flux:table.cell align="center">
                                <div class="font-medium text-sm text-{{ $cvColor }}-500">
                                    {{ number_format($cv, 1) }}%
                                </div>
                            </flux:table.cell>

                            <flux:table.cell align="center" size="sm">
                                <flux:badge size="sm" color="{{ $color }}" class="text-xs">
                                    {{ $status }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    {{-- WORK ELEMENT --}}
    <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-2">
        <flux:heading size="md" class="mb-4 flex items-center justify-between">
            <div class="font-semibold">
                Detail Elemen Kerja per Stasiun
                <flux:subheading class="flex items-center text-xs text-neutral-500 space-x-2">
                    <span>Cycle-normalized duration</span>
                    <span>·</span>
                    <span>durasi per siklus</span>
                </flux:subheading>
            </div>

            <flux:select wire:model.live="selectedStation" class="w-48">
                <flux:select.option value="">Semua Stasiun</flux:select.option>
                @foreach($stations as $station)
                    <flux:select.option value="{{ $station }}">{{ $station }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:heading>

        <flux:table>
            <flux:table.columns class="font-medium bg-gray-100 dark:bg-neutral-700">
                <flux:table.column class="!px-4">Stasiun</flux:table.column>
                <flux:table.column>Elemen Kerja</flux:table.column>
                <flux:table.column align="center">Kategori</flux:table.column>
                <flux:table.column align="center">Durasi / Siklus</flux:table.column>
                <flux:table.column align="center">Std Dev</flux:table.column>
                <flux:table.column align="center">CV</flux:table.column>
                <flux:table.column align="center" class="!px-4">Frekuensi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($elementsData as $stationName => $elements)
                    @if(empty($selectedStation) || $selectedStation === $stationName)
                        @foreach($elements as $el)
                            @php
                                if ($el->cv_persen < 10) {
                                    $cvColor = 'green';
                                } elseif ($el->cv_persen > 20) {
                                    $cvColor = 'red';
                                } else {
                                    $cvColor = 'yellow';
                                }
                            @endphp
                            <flux:table.row
                                class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                                <flux:table.cell class="!font-medium !px-4">{{ $stationName }}</flux:table.cell>
                                <flux:table.cell class="!font-medium">{{ $el->elemen_kerja }}</flux:table.cell>
                                <flux:table.cell class="!font-medium" align="center">
                                    <flux:badge size="sm"
                                        color="{{ $el->kategori_va == 'VA' ? 'green' : ($el->kategori_va == 'N-NVA' ? 'yellow' : 'red') }}">
                                        {{ $el->kategori_va }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="!font-medium" align="center">{{ number_format($el->durasi_detik, 1) }}s
                                </flux:table.cell>
                                <flux:table.cell class="!font-medium" align="center">±{{ number_format($el->std_dev, 1) }}s
                                </flux:table.cell>
                                <flux:table.cell class="!font-medium" align="center">
                                    <span class="text-{{ $cvColor }}-600">
                                        {{ number_format($el->cv_persen, 1) }}%
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="!font-medium !px-4" align="center">{{ $el->frekuensi }}×</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    @endif
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
    @endif

@script
<script>
    let ctChartInstance = null;

    function renderCtChart() {
        const el = document.querySelector("#ctChart");
        if (!el) return; // container belum ada (masih loading) → lewati

        // Ambil data TERBARU dari komponen (bukan nilai lama saat halaman baru dibuka)
        const taktTime   = $wire.get('taktTime');
        const stations   = $wire.get('stations') || [];
        const meanData   = $wire.get('meanCT') || [];
        const robustData = $wire.get('robustCT') || [];
        const cvData     = $wire.get('cvData') || [];

        if (!stations.length) return; // data belum siap → lewati

        // Hapus chart lama agar tidak dobel saat render ulang
        if (ctChartInstance) { try { ctChartInstance.destroy(); } catch (e) {} ctChartInstance = null; }
        el.innerHTML = '';

        // ================= STATUS STATION =================

        const meanStatus = meanData.map(v => {
            if (v > taktTime) return 'bottleneck'
            if (v > taktTime * 0.9) return 'at-risk'
            if (v < taktTime * 0.75) return 'underloaded'
            return 'balanced'

        })

        // ================= MARKER COLOR =================

        const markerColors = meanStatus.map(s => {
            if (s === 'bottleneck') return '#ef4444'
            if (s === 'at-risk') return '#f59e0b'
            if (s === 'underloaded') return '#3b82f6'
            return '#10b981'
        })
        // ================= MAIN CHART =================

        const ctOptions = {

            chart: {
                type: 'area',
                height: 450,
                width: '100%',
                animations: { enabled: true, easing: 'easeinout', speed: 600 }
            },

            series: [
                { name: 'Mean Cycle Time', data: meanData },
                { name: 'Robust CT (μ+2σ)', data: robustData }
            ],

            markers: { size: 0 },

            xaxis: {
                categories: stations,
                labels: {
                    style: {
                        colors: markerColors,
                        fontWeight: 600,
                        fontSize: '12px'
                    },
                    rotate: 45
                }
            },

            yaxis: {
                min: 0,
                labels: {
                    formatter: val => val + ' s'
                }
            },

            stroke: { width: [3, 3], dashArray: [0, 6], curve: 'smooth' },
            colors: ['#1e3a8a', '#38bdf8'],

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },

            grid: {
                borderColor: '#e5e7eb',
                strokeDashArray: 4,
                padding: { left: 20, right: 60 }
            },

            // ================= TAKT LINE =================
            annotations: {
                yaxis: [
                    {
                        y: taktTime,
                        borderColor: '#ef4444',
                        strokeDashArray: 4,
                        label: {
                            style: { color: '#fff', background: '#ef4444' },
                            text: 'Takt Time = ' + taktTime.toFixed(1) + 's'
                        }
                    }
                ],

                // ================= STATION LABEL =================

                points: stations.map((station, i) => {
                    let color = '#10b981'
                    let text = 'Balanced'
                    if (meanStatus[i] === 'bottleneck') {
                        color = '#ef4444'
                        text = 'Bottleneck'
                    }
                    else if (meanStatus[i] === 'at-risk') {
                        color = '#f59e0b'
                        text = 'At Risk'
                    }
                    else if (meanStatus[i] === 'underloaded') {
                        color = '#3b82f6'
                        text = 'Underloaded'
                    }
                    return {
                        x: station,
                        y: meanData[i],
                        marker: { size: 0 },
                        label: {
                            text: text,
                            borderColor: color,
                            offsetY: 30,
                            style: { color: '#fff', background: color }
                        }
                    }
                })
            },

            // ================= TOOLTIP =================

            tooltip: {
                theme: 'dark',
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {

                    const station = stations[dataPointIndex]
                    const val = series[seriesIndex][dataPointIndex]
                    const diff = val - taktTime
                    const cv = cvData[dataPointIndex]

                    const status =
                        diff > 0
                            ? `⚠ +${diff.toFixed(1)}s overflow`
                            : `✓ ${Math.abs(diff).toFixed(1)}s idle`

                    return `
                        <div style="
                        background:#0B1628;
                        padding:8px 10px;
                        border-radius:6px;
                        border:1px solid rgba(91,155,213,.2);
                        color:#D4E1EF;
                        font-size:10px;
                        line-height:1.4;
                        min-width:150px">

                        <div style="color:#A8D4F5;font-weight:600;margin-bottom:4px">
                        ${station}
                        </div>

                        <div style="margin-bottom:4px">
                        ${w.config.series[seriesIndex].name} :
                        <b>${val}s</b>
                        </div>

                        <div>
                        ${status}
                        </div>

                        <div style="color:#9CA3AF">
                        CV : ${cv}%
                        </div>

                        </div>

                        `
                }
            },
            legend: { position: 'bottom' }

        }
        // ================= RENDER =================
        ctChartInstance = new ApexCharts(el, ctOptions);
        ctChartInstance.render();
    }

    // 1. Render saat komponen dimuat / pindah halaman, mis. buka dari Riwayat
    setTimeout(renderCtChart, 50);

    // 2. Render OTOMATIS begitu analisa selesai via polling,
    //    tanpa harus pindah halaman dulu. Beri jeda agar #ctChart sempat tampil.
    $wire.on('results-ready', () => setTimeout(renderCtChart, 200));
</script>
@endscript
</div>
