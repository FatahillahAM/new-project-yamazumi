<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 z-90">
        <flux:sidebar.header>
            <flux:sidebar.brand name="{{ config('app.name') }}" :href="route('dashboard')">
                <x-app-logo-icon class="size-6 fill-current text-black dark:text-white" />
            </flux:sidebar.brand>
            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>
        <flux:sidebar.nav>
    @can('read_dashboard')
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </flux:sidebar.item>
    @endcan

    @can('read_analyst')
        <flux:sidebar.item icon="chart-bar" :href="route('menu.analyst')"
            :current="request()->routeIs('menu.analyst')" wire:navigate>
            Analyst Flow
        </flux:sidebar.item>
    @endcan

    @can('read_report')
        <flux:sidebar.item icon="arrow-trending-up" :href="route('menu.report')"
            :current="request()->routeIs('menu.report')" wire:navigate>
            Report Analyst
        </flux:sidebar.item>
    @endcan

    @can('read_simulation')
        <flux:sidebar.item icon="arrow-path" :href="route('menu.simulation')"
            :current="request()->routeIs('menu.simulation')" wire:navigate>
            Simulation
        </flux:sidebar.item>
    @endcan

    @can('read_riwayat')
        <flux:sidebar.item icon="bookmark" :href="route('data.riwayat')"
            :current="request()->routeIs('data.riwayat')" wire:navigate>
            Riwayat
        </flux:sidebar.item>
    @endcan

    @can('read_validasi_iou')
        <flux:sidebar.item icon="clock" :href="route('data.validasi_iou')"
            :current="request()->routeIs('data.validasi_iou')" wire:navigate>
            Validasi IOU
        </flux:sidebar.item>
    @endcan

    @php
        $isUserManagementActive = request()->routeIs('management.role', 'management.user');
    @endphp
    @canany(['read_role', 'read_user'])
        <flux:sidebar.group heading="Managemen User" icon="cog-6-tooth" expandable
            :expanded="$isUserManagementActive">
            @can('read_role')
                <flux:sidebar.item icon="user-group" :href="route('management.role')"
                    :current="request()->routeIs('management.role')" wire:navigate>
                    Kelola Role
                </flux:sidebar.item>
            @endcan
            @can('read_user')
                <flux:sidebar.item icon="user" :href="route('management.user')"
                    :current="request()->routeIs('management.user')" wire:navigate>
                    Kelola Pengguna
                </flux:sidebar.item>
            @endcan
        </flux:sidebar.group>
    @endcanany
</flux:sidebar.nav>

        <flux:sidebar.spacer />
        <flux:sidebar.item icon="sun" tooltip="$flux.appearance === 'dark' ? 'Light Mode' : 'Dark Mode'"
            x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" class="cursor-pointer">
            <span x-text="$flux.appearance === 'dark' ? 'Light Mode' : 'Dark Mode'"></span>
        </flux:sidebar.item>
        <flux:dropdown position="top" align="start" class="max-lg:hidden">
            <flux:sidebar.profile :name="auth()->user()->name">
                <x-slot name="avatar">
                    <flux:avatar size="sm" name="{{ auth()->user()->name }}">
                    </flux:avatar>
                </x-slot>
            </flux:sidebar.profile>
            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar name="{{ auth()->user()->name }}" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" variant="danger" icon="arrow-right-start-on-rectangle"
                        class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>


    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    <x-swal-toast />

    <x-confirm-delete />

    @fluxScripts

    <footer
        class="fixed z-10 bottom-0 w-full text-end px-6 py-4 text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-zinc-800 shadow-inner">
        &copy; {{ date('Y') }} PT. Fukuryo Indonesia. All rights reserved.
    </footer>
</body>

</html>