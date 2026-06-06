<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen flex overflow-hidden font-sans
bg-gradient-to-br from-[#081221] via-[#0B1628] to-[#0F203A]
dark:from-[#050A14] dark:via-[#081426] dark:to-[#0B1B33]
text-gray-200">

    {{-- LEFT PANEL --}}
    <div class="hidden lg:flex w-[54%] relative flex-col justify-between p-16 overflow-hidden">
        <div class="absolute inset-0 opacity-20 pointer-events-none
        [background-image:linear-gradient(rgba(91,155,213,.06)_1px,transparent_1px),
        linear-gradient(90deg,rgba(91,155,213,.06)_1px,transparent_1px)]
        [background-size:44px_44px]"></div>

        <div class="absolute w-[600px] h-[600px] top-1/2 left-[60%]
        -translate-x-1/2 -translate-y-1/2
        bg-[radial-gradient(circle,rgba(126,184,232,.15)_0%,transparent_70%)]
        blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col h-full">
            <div class="mb-24">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-4 group transition duration-500" wire:navigate>
                    <span class="flex h-12 w-12 items-center justify-center backdrop-blur transition-all duration-500 group-hover:scale-105">
                        <x-app-logo-icon class="h-7 fill-current text-gray-200 group-hover:text-[#9FD2F4] transition duration-500" />
                    </span>
                    <div>
                        <div class="font-mono text-sm tracking-widest text-gray-200">
                            {{ config('app.name', 'Laravel') }}
                        </div>
                        <div class="text-[11px] text-[#8BA5C0] uppercase tracking-wider">
                            PT. Fukuryo Indonesia
                        </div>
                    </div>
                </a>
            </div>

            <div>
                <h1 class="text-[48px] font-bold leading-[1.05] tracking-[-.03em] mb-6 text-gray-100">
                    Adaptive <br>
                    <span class="bg-gradient-to-r from-[#7EB8E8] via-[#9FD2F4] to-[#CDEBFF] bg-clip-text text-transparent">
                        Line Balancing
                    </span><br>
                    System
                </h1>
                <p class="text-lg leading-relaxed max-w-[420px] mb-16 text-[#8BA5C0]">
                    Sistem analisis lini berbasis Computer Vision dan Robust Optimization untuk mereduksi variabilitas produksi serta meningkatkan stabilitas performa line.
                </p>
            </div>

            <div class="mt-12 w-full">
                <div class="grid grid-cols-3 gap-8 w-full">
                    @foreach ([['97.7%', 'Coverage (μ+2σ)'], ['±0.3s', 'Akurasi IoU'], ['5T', 'Kategori Waste']] as $stat)
                        <div class="group bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl py-10 text-center transition-all duration-500 hover:-translate-y-2 hover:border-[#7EB8E8]/40 hover:bg-white/10 hover:shadow-xl">
                            <div class="font-mono text-4xl tracking-tight mb-3 text-gray-100">
                                {{ $stat[0] }}
                            </div>
                            <div class="text-xs uppercase tracking-widest text-[#8BA5C0]">
                                {{ $stat[1] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

   <div class="flex-1 flex items-center justify-center p-16 bg-gradient-to-br from-[#0B1628] to-[#10223F] dark:from-[#081221] dark:to-[#050A14]">
        <div id="loginCard" class="w-full max-w-[380px] transition-all duration-700 text-gray-200">
            {{ $slot }}
        </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const loginCard = document.getElementById('loginCard');
        setTimeout(() => {
          loginCard.classList.remove('opacity-0', 'scale-95');
          loginCard.classList.add('opacity-100', 'scale-100');
        }, 100);
      });
    </script>

    @fluxScripts          {{-- ← TAMBAHKAN BARIS INI --}}
</body>
</html>