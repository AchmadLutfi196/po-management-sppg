@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    <style>
        .invoice-history-filter-grid {
            display: grid;
            grid-template-columns: minmax(190px, 1.35fr) minmax(120px, 0.7fr) minmax(170px, 1fr) minmax(170px, 1fr) minmax(135px, 0.8fr) minmax(135px, 0.8fr) minmax(105px, 0.55fr) minmax(90px, 0.5fr);
            gap: 0.5rem;
            align-items: end;
        }

        @media (max-width: 1280px) {
            .invoice-history-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .invoice-history-filter-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <section class="mx-auto max-w-[1440px] space-y-4">
        {{-- Tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('invoices.index') }}" class="{{ $activeTab === 'pending' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500' }} shrink-0 rounded-lg px-5 py-2 text-xs font-bold uppercase tracking-wide">
                Siap Rekap Tagihan
            </a>
            <a href="{{ route('invoices.index', ['tab' => 'history']) }}" class="{{ $activeTab === 'history' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500' }} shrink-0 rounded-lg px-5 py-2 text-xs font-bold uppercase tracking-wide">
                Riwayat Invoice
            </a>
        </nav>

        @if ($activeTab === 'pending')
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="w-12 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No</th>
                                <th class="w-72 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier & Referensi</th>
                                <th class="w-40 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Info Item</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Rincian Barang</th>
                                @if ($currentUser['role'] === 'ADMIN')
                                    <th class="w-36 px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingInvoices as $entry)
                                <tr class="align-top hover:bg-slate-50/50">
                                    <td class="px-3 py-3 text-xs font-bold text-slate-400">{{ ($pendingInvoices->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-3 py-3">
                                        <p class="text-xs font-black uppercase text-slate-950">{{ $entry['supplier'] }}</p>
                                        <p class="mt-1 text-[10px] font-bold text-slate-600">Ref PO: {{ $entry['order']['number'] }}</p>
                                        <span class="mt-1.5 inline-flex rounded border border-blue-100 bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-600">Siap Tagih</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <p class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">1 PO · {{ $entry['items']->count() }} Item</p>
                                        <p class="mt-1.5 text-[11px] font-bold text-emerald-600">Rp {{ number_format($entry['total'], 0, ',', '.') }}</p>
                                    </td>
                                    <td class="px-3 py-3">
                                        @php($pendingItems = collect($entry['items']))
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="rounded bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white">{{ $pendingItems->count() }} item</span>
                                            @foreach ($pendingItems->take(2) as $pendingItem)
                                                <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700">{{ $pendingItem['name'] }}</span>
                                            @endforeach
                                            @if ($pendingItems->count() > 2)
                                                <span class="text-[10px] font-bold text-slate-400">+{{ $pendingItems->count() - 2 }} lagi</span>
                                            @endif
                                        </div>
                                    </td>
                                    @if ($currentUser['role'] === 'ADMIN')
                                        <td class="px-3 py-3 text-right">
                                            <a href="{{ route('invoices.create', ['id' => $entry['order']['id'], 'supplier' => $entry['supplier']]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                                                Buat Invoice
                                                <span>›</span>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $currentUser['role'] === 'ADMIN' ? 5 : 4 }}" class="px-3 py-10 text-center text-sm font-bold text-slate-400">Belum ada tagihan yang siap direkap.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($pendingInvoices->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $pendingInvoices->links() }}
                    </div>
                @endif
            </section>
        @else
            {{-- Stats --}}
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Tagihan Beredar</p>
                    <p class="mt-1.5 text-lg font-black text-slate-950">Rp {{ number_format($stats['total'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Lunas</p>
                    <p class="mt-1.5 text-lg font-black text-emerald-600">Rp {{ number_format($stats['paid'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Belum Dibayar</p>
                    <p class="mt-1.5 text-lg font-black text-rose-600">Rp {{ number_format($stats['unpaid'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Dokumen</p>
                    <p class="mt-1.5 text-lg font-black text-slate-950">{{ $stats['count'] }} Invoice</p>
                </article>
            </section>

            {{-- Filter Riwayat --}}
            <form method="GET" action="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="tab" value="history">
                <div class="space-y-3">
                    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-end">
                        <div>
                            <h2 class="text-sm font-black tracking-tight text-slate-950">Filter Riwayat Invoice</h2>
                            <p class="mt-0.5 text-xs font-medium text-slate-500">Cari invoice berdasarkan nomor, supplier, SPPG, referensi PO, atau barang.</p>
                        </div>
                    </div>

                    <div class="invoice-history-filter-grid">
                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">Cari</span>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">Cari</span>
                                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" placeholder="No invoice / PO / barang..." class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-12 pr-3 text-sm font-semibold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                            </div>
                        </label>

                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">Status</span>
                            @if ($currentUser['role'] === 'ADMIN')
                                <select name="status" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua</option>
                                    <option value="PAID" @selected(($filters['status'] ?? 'all') === 'PAID')>Lunas</option>
                                    <option value="UNPAID" @selected(($filters['status'] ?? 'all') === 'UNPAID')>Belum Bayar</option>
                                </select>
                            @else
                                <p class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600">Semua</p>
                            @endif
                        </label>

                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">Supplier</span>
                            <select name="supplier" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier }}" @selected(($filters['supplier'] ?? '') === $supplier)>{{ $supplier }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">SPPG</span>
                            <select name="sppg" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                <option value="">Semua SPPG</option>
                                @foreach ($sppgs as $sppg)
                                    <option value="{{ $sppg->code }}" @selected(($filters['sppg'] ?? '') === $sppg->code)>{{ $sppg->code }} - {{ $sppg->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">Dari</span>
                            <input name="date_from" value="{{ $filters['date_from'] ?? '' }}" type="date" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                        </label>

                        <label class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">Sampai</span>
                            <input name="date_to" value="{{ $filters['date_to'] ?? '' }}" type="date" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                        </label>

                        <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-xs font-black uppercase tracking-wide text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">Terapkan</button>
                        <a href="{{ route('invoices.index', ['tab' => 'history']) }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-xs font-black uppercase tracking-wide text-slate-500 transition hover:bg-slate-50">Reset</a>
                    </div>
                </div>
            </form>

            {{-- Tabel Riwayat --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="w-12 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No</th>
                                <th class="w-56 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No Invoice</th>
                                <th class="w-40 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier</th>
                                <th class="w-32 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Kepada</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Rincian Barang</th>
                                <th class="w-28 px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Total</th>
                                <th class="w-32 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Status</th>
                                <th class="w-20 px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($historyInvoices as $entry)
                                @php($invoice = $entry['invoice'])
                                <tr class="align-top hover:bg-slate-50/50">
                                    <td class="px-3 py-3 text-xs font-bold text-slate-400">{{ ($historyInvoices->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-3 py-3">
                                        <p class="truncate text-xs font-black text-slate-950">{{ $invoice['number'] }}</p>
                                        <p class="mt-1 text-[10px] font-bold text-slate-500">{{ date('d/m/Y', strtotime($invoice['date'])) }}</p>
                                        <span class="mt-1 inline-flex rounded border border-blue-100 bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-600">Ref: {{ $entry['order']['number'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-xs font-bold uppercase text-slate-800">{{ $invoice['supplier'] }}</td>
                                    <td class="px-3 py-3 text-xs font-bold uppercase text-slate-500">{{ $entry['order']['sppg'] }}</td>
                                    <td class="px-3 py-3">
                                        @php($invoiceItems = collect($invoice['items'] ?? []))
                                        @if ($invoiceItems->count() > 0)
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="rounded bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white">{{ $invoiceItems->count() }} item</span>
                                                @foreach ($invoiceItems->take(2) as $invItem)
                                                    <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700">{{ $invItem['name'] }}</span>
                                                @endforeach
                                                @if ($invoiceItems->count() > 2)
                                                    <span class="text-[10px] font-bold text-slate-400">+{{ $invoiceItems->count() - 2 }} lagi</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs font-bold text-slate-400">Belum ada rincian.</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs font-black text-slate-950">Rp {{ number_format($invoice['total_amount'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-3">
                                        @if ($currentUser['role'] === 'ADMIN')
                                            <form method="POST" action="{{ route('invoices.status.update', $entry['order']['id']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="invoice_no" value="{{ $invoice['number'] }}">
                                                <select name="status" onchange="this.form.submit()" class="{{ $invoice['status'] === 'PAID' ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-blue-200 bg-blue-50 text-blue-600' }} w-full rounded border px-2 py-1.5 text-[10px] font-bold uppercase tracking-wide outline-none">
                                                    <option value="UNPAID" @selected($invoice['status'] === 'UNPAID')>Belum Bayar</option>
                                                    <option value="PAID" @selected($invoice['status'] === 'PAID')>Lunas</option>
                                                </select>
                                            </form>
                                        @else
                                            @include('partials.status-badge', ['status' => $invoice['status']])
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('invoices.preview', ['id' => $entry['order']['id'], 'invoice' => $invoice['number'], 'supplier' => $invoice['supplier']]) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                            Cetak
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-10 text-center text-sm font-bold text-slate-400">Belum ada riwayat invoice.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($historyInvoices->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $historyInvoices->links() }}
                    </div>
                @endif
            </section>
        @endif
    </section>
@endsection
