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