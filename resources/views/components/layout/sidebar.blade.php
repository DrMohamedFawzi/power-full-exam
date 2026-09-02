@php
    $groups = \App\Support\Navigation\Navigation::for(auth()->user());
@endphp

<aside x-data="{ open: false }"
       class="border-base-300 bg-base-100 fixed inset-y-0 start-0 z-40 w-64 shrink-0 border-e transition-transform lg:static lg:translate-x-0"
       :class="open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
       x-on:sidebar-toggle.window="open = !open">

    <div class="flex h-full flex-col">
        <a href="{{ url('/') }}" class="border-base-300 flex items-center gap-2.5 border-b px-5 py-4">
            <x-ui.logo class="size-8" />
            <div class="leading-tight">
                <p class="font-extrabold">{{ config('app.name') }}</p>
                <p class="muted text-[11px]">منصة الاختبارات الآمنة</p>
            </div>
        </a>

        <nav class="flex-1 overflow-y-auto p-3" aria-label="القائمة الرئيسية">
            @foreach ($groups as $group)
                <p class="muted px-3 pt-4 pb-1 text-[11px] font-bold tracking-wide uppercase">{{ $group['label'] }}</p>

                <ul class="menu menu-sm w-full gap-0.5 p-0">
                    @foreach ($group['items'] as $item)
                        <li>
                             <a href="{{ $item->url() }}"
                               @class(['flex items-center gap-3 rounded-xl font-semibold transition-all', 'menu-active border-s-4 border-primary !rounded-s-none' => $item->isActive()])
                               @if ($item->isActive()) aria-current="page" @endif>
                                <x-dynamic-component :component="'heroicon-o-'.$item->icon" class="size-5 shrink-0" />
                                <span class="truncate">{{ $item->label }}</span>
                                @if ($item->badge)
                                    <span class="badge badge-primary badge-sm ms-auto">{{ $item->badge }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="border-base-300 border-t p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost text-error w-full justify-start gap-3 font-semibold">
                    <x-heroicon-o-arrow-left-on-rectangle class="size-5" />
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</aside>
