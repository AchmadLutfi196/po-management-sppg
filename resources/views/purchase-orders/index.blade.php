@extends('layouts.app', ['title' => 'Pesanan Pembelian'])

@section('content')
    <section class="mx-auto max-w-[1440px] space-y-7">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                <div class="absolute right-0 top-0 h-full w-2 bg-emerald-400/10"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Nilai PO</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">Rp {{ number_format($stats['total_value'], 0, ',', '.') }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        @include('partials.icon', ['name' => 'trending-up', 'class' => 'h-5 w-5'])
                    </span>
                </div>
            </article>

            <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                <div class="absolute right-0 top-0 h-full w-2 bg-blue-400/10"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">PO Aktif</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $stats['active'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        @include('partials.icon', ['name' => 'clock', 'class' => 'h-5 w-5'])
                    </span>
                </div>
            </article>

            <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                <div class="absolute right-0 top-0 h-full w-2 bg-orange-400/10"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Valid</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $stats['valid'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        @include('partials.icon', ['name' => 'box', 'class' => 'h-5 w-5'])
                    </span>
                </div>
            </article>

            <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                <div class="absolute right-0 top-0 h-full w-2 bg-indigo-400/10"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Selesai</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $stats['completed'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        @include('partials.icon', ['name' => 'check-circle', 'class' => 'h-5 w-5'])
                    </span>
                </div>
            </article>
        </div>

        <form method="GET" class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/70 lg:flex-row">
            <div class="relative min-w-0 flex-1">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">⌕</span>
                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" placeholder="Cari berdasarkan No PO, barang, atau nama pembuat..." class="w-full rounded-lg border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm font-semibold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">
            </div>
            <div class="relative w-full lg:w-52">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">▽</span>
                <select name="status" class="w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-xs font-black uppercase tracking-wider text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10" onchange="this.form.submit()">
                    @foreach (['ALL' => 'Semua Status', 'VALID' => 'Valid', 'PROCESSING' => 'Proses', 'INVOICED' => 'Tertagih', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'ALL') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-[1180px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Identitas PO</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Barang & Request</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Qty & Satuan</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">SPPG</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Droping</th>
                            <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Finansial</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Status</th>
                            <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $total           = collect($order['items'])->sum(fn ($item) => $item['qty'] * $item['price']);
                                $firstItem       = $order['items'][0];
                                $remaining       = max(0, count($order['items']) - 1);
                                // Kolom NO = urutan tampil di list (1, 2, 3...)
                                $sequence        = ($orders->firstItem() ?? 1) + $loop->index;
                                // Hitung status supplier semua item
                                $allItems        = collect($order['items']);
                                $hasAnySupplier  = $allItems->contains(fn ($i) => $i['supplier'] !== '-' && $i['supplier'] !== null);
                                $allHaveSupplier = $allItems->every(fn ($i) => $i['supplier'] !== '-' && $i['supplier'] !== null);
                                $isLocked        = in_array($order['status'], ['COMPLETED', 'INVOICED'], true);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-5 text-center text-xs font-black text-slate-400">{{ $sequence }}</td>
                                <td class="px-5 py-5">
                                    @if ($order['number'])
                                        <a href="{{ route('purchase-orders.show', $order['id']) }}" class="block max-w-64 truncate text-xs font-black text-slate-950">{{ $order['number'] }}</a>
                                    @else
                                        <a href="{{ route('purchase-orders.show', $order['id']) }}" class="block max-w-64 truncate text-xs font-black italic text-slate-400">Menunggu Validasi</a>
                                    @endif
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="rounded bg-blue-50 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-blue-600">{{ $order['created_by'] }}</span>
                                        <span class="text-[10px] font-black text-slate-400">{{ date('m/d/Y', strtotime($order['date'])) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-5">
                                    <p class="max-w-56 truncate text-xs font-black uppercase text-slate-900">
                                        {{ $firstItem['name'] }}
                                        @if ($remaining > 0)
                                            <span class="text-blue-600">+{{ $remaining }} lainnya</span>
                                        @endif
                                    </p>
                                    @if (! empty($firstItem['request']))
                                        <p class="mt-1 text-[10px] font-bold italic text-slate-400">"{{ $firstItem['request'] }}"</p>
                                    @endif
                                    {{-- Status supplier --}}
                                    @if ($allHaveSupplier)
                                        <p class="mt-1 text-[9px] font-black uppercase text-emerald-600">
                                            Supplier OK
                                            <span class="ml-1 text-blue-600">{{ $firstItem['supplier'] }}</span>
                                        </p>
                                    @elseif ($hasAnySupplier)
                                        <p class="mt-1 text-[9px] font-black uppercase text-orange-500">Partial Supplier</p>
                                    @else
                                        <p class="mt-1 text-[9px] font-black uppercase text-rose-500">No Supplier</p>
                                    @endif
                                </td>
                                <td class="px-5 py-5 text-xs font-black text-slate-900">
                                    {{ collect($order['items'])->sum('qty') }} <span class="text-[10px] uppercase text-slate-400">{{ $firstItem['unit'] }}</span>
                                </td>
                                <td class="px-5 py-5">
                                    <span class="inline-flex max-w-40 rounded border border-slate-200 bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase text-slate-700">{{ $order['sppg'] }}</span>
                                </td>
                                <td class="px-5 py-5 text-xs font-black text-slate-700">
                                    @if ($order['droping_date'])
                                        <span class="block">{{ $order['droping_date'] }}</span>
                                        <span class="block text-[10px] text-slate-400">{{ $order['droping_time'] }}</span>
                                    @else
                                        <span class="block text-slate-400">-</span>
                                        <span class="block text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-5 text-right text-xs font-black text-slate-950">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                @php
                                    $statusSelectClass = [
                                        'VALID' => 'border-orange-200 bg-orange-50 text-orange-600 focus:border-orange-400 focus:ring-orange-500/10',
                                        'PROCESSING' => 'border-blue-200 bg-blue-50 text-blue-600 focus:border-blue-400 focus:ring-blue-500/10',
                                        'COMPLETED' => 'border-emerald-200 bg-emerald-50 text-emerald-600 focus:border-emerald-400 focus:ring-emerald-500/10',
                                        'INVOICED' => 'border-indigo-200 bg-indigo-50 text-indigo-600 focus:border-indigo-400 focus:ring-indigo-500/10',
                                        'CANCELLED' => 'border-slate-200 bg-slate-50 text-slate-400 focus:border-slate-400 focus:ring-slate-500/10',
                                    ][$order['status']] ?? 'border-slate-200 bg-slate-50 text-slate-600 focus:border-blue-500 focus:ring-blue-500/10';
                                @endphp
                                <td class="px-5 py-5">
                                    @if ($currentUser['role'] === 'ADMIN')
                                        <form method="POST" action="{{ route('purchase-orders.status.update', $order['id']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" onchange="this.form.submit()" class="{{ $statusSelectClass }} min-w-36 rounded-full border px-3 py-2 text-[10px] font-black uppercase tracking-wider outline-none transition focus:ring-4">
                                                @foreach (['VALID' => 'VALID', 'PROCESSING' => 'PROSES', 'INVOICED' => 'TERTAGIH', 'COMPLETED' => 'SELESAI', 'CANCELLED' => 'DIBATALKAN'] as $value => $label)
                                                    <option value="{{ $value }}" @selected($order['status'] === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        @include('partials.status-badge', ['status' => $order['status']])
                                    @endif
                                </td>
                                <td class="px-5 py-5">
                                    <div class="flex justify-end gap-1.5">
                                        @if ($currentUser['role'] === 'ADMIN')
                                            @if (! $isLocked)
                                                <a href="{{ route('purchase-orders.edit', $order['id']) }}" title="Edit PO" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.651-1.651a2.121 2.121 0 013 3L7.5 19.849 3 21l1.151-4.5L16.862 4.487z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('purchase-orders.destroy', $order['id']) }}" onsubmit="return confirm('Hapus PO ini dari tampilan sementara?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus PO" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-7 4v6m4-6v6M8 7l1 13h6l1-13" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('purchase-orders.preview', $order['id']) }}" title="Preview PDF" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7V3z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M9 13h6M9 17h6" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('purchase-orders.show', $order['id']) }}" title="Detail PO" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center text-sm font-bold text-slate-400">Tidak ada data PO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </section>
    </section>
@endsection
