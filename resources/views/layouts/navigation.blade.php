<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Esquerda -->
            <div class="flex items-center gap-8">
                <a href="{{ route('master.dashboard') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-6 w-6 text-gray-800" />
                </a>

                <div class="hidden sm:flex sm:items-center sm:space-x-6">
                    <x-nav-link :href="route('master.dashboard')" :active="request()->routeIs('master.*')">
                        {{ __('Painel Master') }}
                    </x-nav-link>

                    @if (Route::has('operacional.kanban'))
                        <x-nav-link :href="route('operacional.kanban')" :active="request()->routeIs('operacional.kanban')">
                            {{ __('Operacional') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Direita -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm text-gray-600">
                            <div>{{ Auth::user()->name }}</div>
                            <svg class="ms-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @if (Route::has('profile.edit'))
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Mobile -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="p-2 rounded-md text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"><path :class="{'hidden': open, 'inline-flex': ! open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/><path :class="{'hidden': ! open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Mobile -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('master.dashboard')" :active="request()->routeIs('master.*')">
                {{ __('Painel Master') }}
            </x-responsive-nav-link>
            @if (Route::has('operacional.kanban'))
                <x-responsive-nav-link :href="route('operacional.kanban')" :active="request()->routeIs('operacional.kanban')">
                    {{ __('Operacional') }}
                </x-responsive-nav-link>
            @endif
        </div>
    </div>
</nav>
