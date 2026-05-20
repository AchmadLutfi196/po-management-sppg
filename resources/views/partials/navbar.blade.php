<header class="flex min-h-16 shrink-0 flex-col gap-3 border-b border-slate-200 bg-white px-3 py-3 sm:px-6 lg:h-16 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-0">
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden" data-mobile-sidebar-open aria-label="Buka menu">
            @include('partials.icon', ['name' => 'dashboard', 'class' => 'h-5 w-5'])
        </button>
        <h1 class="min-w-0 truncate text-base font-black tracking-tight text-slate-900 sm:text-lg">{{ $title }}</h1>
        <span class="hidden h-4 w-px bg-slate-200 sm:block"></span>
        <span class="w-fit rounded border border-blue-100 bg-blue-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-600">Live View</span>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if (request()->routeIs('purchase-orders.index'))
            <a href="{{ route('purchase-orders.create') }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 sm:px-4">
                PO Baru
            </a>
        @endif

    </div>
</header>
