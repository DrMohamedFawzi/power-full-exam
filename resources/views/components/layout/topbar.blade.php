@php $user = auth()->user(); @endphp

<header class="navbar border-base-300 bg-base-100/80 sticky top-0 z-30 border-b px-4 backdrop-blur lg:px-8">
    <button type="button" class="btn btn-ghost btn-square lg:hidden"
            @click="$dispatch('sidebar-toggle')" aria-label="فتح القائمة">
        <x-heroicon-o-bars-3 class="size-6" />
    </button>

    <div class="flex-1"></div>

    <div class="flex items-center gap-1">
        <x-ui.theme-toggle />

        @if ($user)
            <div class="dropdown dropdown-end">
                <button type="button" tabindex="0" class="btn btn-ghost gap-2 px-2">
                    <span class="avatar avatar-placeholder">
                        <span class="bg-primary/10 text-primary size-9 rounded-full">
                            @if ($user->avatar_path)
                                <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="rounded-full object-cover">
                            @else
                                <span class="text-sm font-bold">{{ mb_substr($user->official_name, 0, 1) }}</span>
                            @endif
                        </span>
                    </span>
                    <span class="hidden text-start leading-tight sm:block">
                        <span class="block text-sm font-bold">{{ $user->official_name }}</span>
                        <span class="muted block text-[11px]">{{ $user->role->label() }}</span>
                    </span>
                </button>

                <ul tabindex="0" class="dropdown-content menu surface z-50 mt-2 w-56 gap-1 p-2">
                    @if (Route::has('profile.edit'))
                        <li>
                            <a href="{{ route('profile.edit') }}" class="gap-2 font-semibold">
                                <x-heroicon-o-user-circle class="size-4" /> الملف الشخصي
                            </a>
                        </li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-error w-full gap-2 font-semibold">
                                <x-heroicon-o-arrow-left-on-rectangle class="size-4" /> تسجيل الخروج
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @endif
    </div>
</header>
