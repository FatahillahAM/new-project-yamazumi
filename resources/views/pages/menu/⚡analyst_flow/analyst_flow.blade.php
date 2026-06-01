<div
    class="w-full border-t-4 border-slate-500 dark:border-slate-700 bg-white dark:bg-neutral-900 rounded-2xl shadow-sm p-8">
    <div class="mb-8">
        <flux:heading size="lg" class="font-semibold">
            Upload Video Process
            <flux:subheading class="flex items-center text-sm text-neutral-500">
                Lengkapi informasi line dan unggah video untuk memulai proses analisis.
            </flux:subheading>
        </flux:heading>

    </div>
    {{-- STEPPER --}}
    <div class="flex items-center justify-between mb-10">

        {{-- STEP 1 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 1 ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                1
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Metadata Lini
                </p>
            </div>
        </div>

        <div class="flex-1 h-[2px] mx-4
        {{ $step >= 2 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}">
        </div>

        {{-- STEP 2 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 2 ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                2
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Upload Video
                </p>
            </div>
        </div>

        <div class="flex-1 h-[2px] mx-4
        {{ $step >= 3 ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-700' }}">
        </div>

        {{-- STEP 3 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 3 ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                3
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Proses Analisis
                </p>
            </div>
        </div>

    </div>
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:input label="Nama Line" required wire:model.defer="line_name" placeholder="Jas A"
                    class="font-medium" />
                @error('line_name')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Nama Bagian" required wire:model.defer="part_name" placeholder="Front"
                    class="font-medium" />
                @error('part_name')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input type="number" label="Output Harian (PCS)" required wire:model.live="output_harian"
                    placeholder="120" class="font-medium" />
                @error('output_harian')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Style Product" required wire:model.defer="style" placeholder="Man Jas 2P"
                    class="font-medium" />
                @error('style')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Brand" required wire:model.defer="brand" placeholder="Brand B" class="font-medium" />
                @error('brand')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <div class="p-4 bg-gray-100 dark:bg-neutral-800 rounded-lg">
                    <h2 class="text-sm font-medium">Takt Time</h2>

                    @if ($this->taktTime)
                        <p class="text-sm font-semibold text-red-600">
                            {{ number_format($this->taktTime, 1) }} detik / pcs
                        </p>
                    @else
                        <p class="text-xs font-medium text-gray-500">
                            Output belum tersedia
                        </p>
                    @endif
                </div>

                <div class="space-y-2">
                    <flux:input type="number" required label="Jumlah Man Power Aktual (Total Operator di Line)*"
                        wire:model.defer="mp_aktual" placeholder="5" class:input="!w-64 font-medium" />
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 max-w-xl">
                        Isi jumlah operator yang aktual bekerja di line ini saat ini. Digunakan sebagai
                        constraint pada simulasi <span class="font-medium">Man Power Balancing</span>.
                        Jika ada stasiun dengan lebih dari 1 operator, tetap masukkan total seluruh operator.
                    </p>
                </div>
                @error('mp_aktual')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-t border-slate-200 dark:border-slate-700"></div>
        <div class="space-y-6">
            <div class="mb-4">
                <flux:heading size="lg" class="font-semibold">
                    Upload Video Rekaman
                    <div class="flex flex-wrap items-center mt-1 text-slate-500 space-x-2">
                        <flux:text size="md">Satu video per stasiun</flux:text>
                        <span>•</span>
                        <flux:text size="md">nama file = nama stasiun</flux:text>
                    </div>
                </flux:heading>
            </div>

            <div x-data="{ isDropping:false, progress:null }" x-on:dragover.prevent="isDropping = true"
                x-on:dragleave.prevent="isDropping = false" x-on:drop.prevent="isDropping = false"
                x-on:livewire-upload-start="progress = 0"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                x-on:livewire-upload-finish="progress = 100; setTimeout(()=>progress=null,800)"
                x-on:livewire-upload-error="progress = null" class="min-h-[220px] flex flex-col items-center justify-center
rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600
hover:border-[#2A5298] hover:bg-[#2A5298]/10 hover:text-[#2A5298] transition cursor-pointer relative overflow-hidden">

                <input type="file" wire:model="file_list" multiple accept=".mp4,.avi,.mov,.mkv,.mts"
                    class="absolute inset-0 opacity-0 cursor-pointer">

                <!-- DONUT PROGRESS -->
                <div x-show="progress !== null" class="absolute flex items-center justify-center">
                    <div class="relative w-24 h-24">
                        <svg class="w-24 h-24 -rotate-90">
                            <circle cx="48" cy="48" r="40" stroke-width="8" class="text-gray-200 dark:text-neutral-700"
                                stroke="currentColor" fill="transparent" />
                            <circle cx="48" cy="48" r="40" stroke-width="8" stroke="currentColor" fill="transparent"
                                stroke-linecap="round" class="text-blue-500 transition-all duration-500 ease-out"
                                stroke-dasharray="251" :stroke-dashoffset="251 - (251 * progress / 100)" />
                        </svg>
                        <div
                            class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-blue-500">
                            <span x-text="progress + '%'"></span>
                        </div>
                    </div>
                </div>

                <div x-show="progress === null"
                    class="flex flex-col items-center text-center pointer-events-none space-y-1.5">
                    <flux:icon.cloud-arrow-up variant="solid" class="text-[#2A5298] size-12" />
                    <p class="font-semibold text-md text-slate-700 dark:text-slate-200">
                        Klik atau drag & drop video di sini
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Nama file akan otomatis disimpan sebagai <strong>nama stasiun kerja</strong>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Contoh: <strong>jahit_lengan.mp4</strong> → Stasiun <strong>"jahit_lengan"</strong>
                    </p>
                    <div class="flex gap-1 flex-wrap justify-center">
                        <flux:badge color="blue" size="sm">.mp4</flux:badge>
                        <flux:badge color="blue" size="sm">.avi</flux:badge>
                        <flux:badge color="blue" size="sm">.mov</flux:badge>
                        <flux:badge color="blue" size="sm">.mkv</flux:badge>
                        <flux:badge color="blue" size="sm">.mts</flux:badge>
                    </div>
                </div>
            </div>
            @error('file_list')
                <flux:text size="sm" class="text-rose-500">
                    {{ $message }}
                </flux:text>
            @enderror

            @error('file_list.*')
                <flux:text size="sm" class="text-rose-500">
                    {{ $message }}
                </flux:text>
            @enderror

            <div wire:loading wire:target="file_list">
                <flux:text size="sm" class="text-indigo-500">
                    Mengunggah video...
                </flux:text>
            </div>

            @if ($file_list)
                <div class="mt-4 flex flex-col gap-3 w-full">
                    @foreach ($station_name as $index => $name)
                    @php $video = $file_list[$index] ?? null; @endphp
                    @if ($video && in_array(strtolower($video->getClientOriginalExtension()), ['mp4', 'mov', 'avi', 'mkv', 'mts']))

                            <div wire:key="video-{{ $index }}"
                                class="flex items-center justify-between gap-4 p-4
                                                                                 border border-gray-200 dark:border-neutral-700
                                                                                 rounded-xl bg-white dark:bg-neutral-900 shadow-sm">
                                <div class="flex items-center gap-4">

                                    <flux:icon.film class="size-6 text-blue-500" />

                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ $video->getClientOriginalName() }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format($video->getSize() / 1024 / 1024, 2) }} MB
                                        </span>

                                    </div>
                                </div>

                                <button wire:click="removeVideo({{ $index }})" type="button"
                                    class="text-red-500 hover:text-red-600 text-sm font-medium transition">
                                    Hapus
                                </button>

                            </div>

                            <!-- Nama Stasiun -->
                            <div class="flex flex-col gap-2">
                                <flux:input label="Nama Stasiun" wire:model.defer="station_name.{{ $index }}"
                                    class:input="w-52 text-sm" />

                                <flux:text class="text-xs text-slate-500 dark:text-slate-400">
                                    Nama ini akan digunakan sebagai nama file saat disimpan
                                </flux:text>
                            </div>

                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ACTION --}}
        <div class="pt-2 flex justify-end gap-2">
            <flux:button wire:click="resetForm" variant="filled"
                class="px-6 disabled:cursor-not-allowed disabled:opacity-50">
                Reset
            </flux:button>
            <flux:button type="submit" variant="filled"
                class="!bg-[#2A5298] !text-white px-4 py-2 rounded-lg text-sm disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="$step < 3">
                Mulai Analisis
            </flux:button>
        </div>
    </form>

    <div wire:loading.flex wire:target="save"
        class="fixed inset-0 z-[9999] items-center justify-center bg-slate-900/60 backdrop-blur-md">
        <div
            class="relative w-full max-w-xl bg-white dark:bg-neutral-900 rounded-3xl shadow-2xl p-8 space-y-6 animate-[fadeUp_.35s_ease]">
            <!-- HEADER -->
            <div class="flex items-center gap-4">
                <div class="w-7 h-7 border-[3px] border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                <div>
                    <div class="text-sm font-semibold text-slate-800 dark:text-white">
                        Menganalisis Video…
                    </div>
                    <div class="text-xs text-slate-500">
                        MediaPipe Pose sedang mendeteksi aktivitas operator
                    </div>
                </div>

            </div>

            <!-- GLOBAL PROGRESS -->
            <div class="space-y-2">
                <div class="flex justify-between text-xs text-slate-500">
                    <span>Phase 1 — Frame Extraction</span>
                    <span>AI Processing</span>
                </div>

                <div class="relative h-2 bg-slate-200 dark:bg-neutral-800 rounded-full overflow-hidden">
                    <div
                        class="absolute inset-y-0 w-1/3 bg-gradient-to-r from-transparent via-indigo-500 to-transparent animate-[shimmer_2s_linear_infinite]">
                    </div>
                </div>
            </div>

            <!-- CONSOLE -->
            <div class="bg-slate-900 text-slate-300 text-[11px] font-mono rounded-lg p-3 h-[90px] overflow-hidden">
                <div class="animate-pulse">> Initializing AI model...</div>
                <div class="animate-pulse">> Loading MediaPipe Pose...</div>
                <div class="animate-pulse">> Extracting frames...</div>
                <div class="animate-pulse">> Detecting operator movement...</div>
            </div>

            <!-- STATUS -->
            <div class="flex items-center justify-center gap-2">
                <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[10px] uppercase tracking-[0.2em] font-semibold text-slate-400">
                    AI Processing Pipeline
                </span>
            </div>
        </div>
    </div>
    <style>
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(300%);
            }
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</div>