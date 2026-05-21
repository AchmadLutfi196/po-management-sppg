@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    <style>
        .invoice-history-filter-grid {
            display: grid;
            grid-template-columns: minmax(130px, 1.4fr) minmax(90px, 0.8fr) minmax(100px, 1.1fr) minmax(90px, 0.8fr) minmax(90px, 0.9fr) minmax(90px, 0.9fr) minmax(90px, 0.9fr) auto;
            gap: 0.375rem;
            align-items: end;
        }

        @media (max-width: 1023px) {
            .invoice-history-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .invoice-history-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .invoice-history-filter-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }
    </style>

    <section class="mx-auto max-w-[1440px] space-y-4">
        {{-- Tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('invoices.index', ['tab' => 'pending']) }}" class="{{ $activeTab === 'pending' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500' }} shrink-0 rounded-lg px-5 py-2 text-xs font-bold uppercase tracking-wide">
                Siap Rekap Tagihan
            </a>
            <a href="{{ route('invoices.index', ['tab' => 'history']) }}" class="{{ $activeTab === 'history' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500' }} shrink-0 rounded-lg px-5 py-2 text-xs font-bold uppercase tracking-wide">
                Riwayat Invoice
            </a>
        </nav>

        @if ($activeTab === 'pending')
            {{-- Filter Pending --}}
            <form method="GET" action="{{ route('invoices.index') }}" class="mb-4 space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="tab" value="pending">
                <div class="space-y-3">
                    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-end">
                        <div>
                            <h2 class="text-sm font-black tracking-tight text-slate-950">Filter Siap Rekap Tagihan</h2>
                            <p class="mt-0.5 text-xs font-medium text-slate-500">Filter data berdasarkan tanggal PO dan tanggal Dropping.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 items-end">
                        {{-- Tanggal PO --}}
                        <div class="space-y-1.5">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Tanggal PO</span>
                            <input type="date" name="po_date" value="{{ $filters['po_date'] ?? '' }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white" onchange="this.form.submit()">
                        </div>

                        {{-- Tanggal Dropping --}}
                        <div class="space-y-1.5">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Tanggal Dropping</span>
                            <input type="date" name="drop_date" value="{{ $filters['drop_date'] ?? '' }}" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white" onchange="this.form.submit()">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="h-10 flex-1 rounded-lg bg-blue-600 px-5 text-xs font-black uppercase tracking-wide text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">Filter</button>
                            @if(!empty($filters['po_date']) || !empty($filters['drop_date']))
                                <a href="{{ route('invoices.index', ['tab' => 'pending', 'clear' => 1]) }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-black uppercase tracking-wide text-slate-500 transition hover:bg-slate-50" title="Reset">✕</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="w-[4%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No</th>
                                <th class="w-[32%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier & Referensi</th>
                                <th class="w-[16%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Info Item</th>
                                <th class="w-[18%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Rincian Barang</th>
                                <th class="w-[10%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Tanggal PO</th>
                                <th class="w-[10%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Tanggal Drop</th>
                                <th class="w-[10%] px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Aksi</th>
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
                                    <td class="px-3 py-3 text-xs font-bold text-slate-700">
                                        {{ date('d/m/Y', strtotime($entry['order']['date'])) }}
                                    </td>
                                    <td class="px-3 py-3 text-xs font-bold text-slate-700">
                                        @if(!empty($entry['order']['droping_date']))
                                            {{ date('d/m/Y', strtotime($entry['order']['droping_date'])) }}
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('invoices.create', ['id' => $entry['order']['id'], 'supplier' => $entry['supplier']]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                                            Buat Invoice
                                            <span>›</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-10 text-center text-sm font-bold text-slate-400">Belum ada tagihan yang siap direkap.</td>
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
            <form method="GET" action="{{ route('invoices.index') }}" class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <input type="hidden" name="tab" value="history">
                <div class="flex flex-col gap-3">
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-wide text-slate-900">Filter Riwayat Invoice</h2>
                    </div>

                    <div class="invoice-history-filter-grid">
                        {{-- Cari --}}
                        <label class="space-y-1 block">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Cari</span>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">⌕</span>
                                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" placeholder="No invoice / PO / barang..." class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs font-semibold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                            </div>
                        </label>

                        {{-- Status --}}
                        <label class="space-y-1 block">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Status</span>
                            @if ($currentUser['role'] === 'ADMIN')
                                <select name="status" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                    <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua Status</option>
                                    <option value="PAID" @selected(($filters['status'] ?? 'all') === 'PAID')>Lunas</option>
                                    <option value="UNPAID" @selected(($filters['status'] ?? 'all') === 'UNPAID')>Belum Bayar</option>
                                </select>
                            @else
                                <p class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600">Semua</p>
                            @endif
                        </label>

                        {{-- Supplier --}}
                        <label class="space-y-1 block">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Supplier</span>
                            <select name="supplier" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                <option value="">Semua Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier }}" @selected(($filters['supplier'] ?? '') === $supplier)>{{ $supplier }}</option>
                                @endforeach
                            </select>
                        </label>

                        {{-- SPPG --}}
                        <label class="space-y-1 block">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">SPPG</span>
                            <select name="sppg" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/10">
                                <option value="">Semua SPPG</option>
                                @foreach ($sppgs as $sppg)
                                    <option value="{{ $sppg->code }}" @selected(($filters['sppg'] ?? '') === $sppg->code)>{{ $sppg->code }} - {{ $sppg->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        {{-- Tanggal Invoice --}}
                        <div class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Tgl Invoice</span>
                            <input name="invoice_date" value="{{ $filters['invoice_date'] ?? '' }}" type="date" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white" onchange="this.form.submit()">
                        </div>

                        {{-- Tanggal PO --}}
                        <div class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Tgl PO</span>
                            <input name="po_date" value="{{ $filters['po_date'] ?? '' }}" type="date" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white" onchange="this.form.submit()">
                        </div>

                        {{-- Tanggal Dropping --}}
                        <div class="space-y-1">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 block">Tgl Drop</span>
                            <input name="drop_date" value="{{ $filters['drop_date'] ?? '' }}" type="date" class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white" onchange="this.form.submit()">
                        </div>

                        {{-- Buttons --}}
                        <div class="flex gap-2">
                            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-4 text-xs font-black uppercase tracking-wide text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">Filter</button>
                            <a href="{{ route('invoices.index', ['tab' => 'history', 'clear' => 1]) }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-black uppercase tracking-wide text-slate-500 transition hover:bg-slate-50" title="Reset">✕</a>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Tabel Riwayat --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="w-[3%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No</th>
                                <th class="w-[19%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">No Invoice</th>
                                <th class="w-[17%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier</th>
                                <th class="w-[9%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Kepada</th>
                                <th class="w-[9%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Info Drop</th>
                                <th class="w-[16%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Rincian Barang</th>
                                <th class="w-[10%] px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Total</th>
                                <th class="w-[12%] px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Status</th>
                                <th class="w-[5%] px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wide text-slate-400">Aksi</th>
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
                                    <td class="px-3 py-3 text-xs font-bold text-slate-700">
                                        @if(!empty($entry['order']['droping_date']))
                                            <span class="block">{{ date('d/m/Y', strtotime($entry['order']['droping_date'])) }}</span>
                                            @if(!empty($entry['order']['droping_time']))
                                                <span class="block text-[10px] text-slate-400">{{ $entry['order']['droping_time'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @php($invoiceItems = collect($invoice['items'] ?? []))
                                        @if ($invoiceItems->count() > 0)
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="rounded bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white">{{ $invoiceItems->count() }} item</span>
                                                @foreach ($invoiceItems->take(2) as $invItem)
                                                    <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700">
                                                        {{ $invItem['name'] }}
                                                        @if (empty($invItem['purchase_order_item_id']))
                                                            <span class="ml-1 font-black uppercase text-amber-600">Di luar PO</span>
                                                        @endif
                                                    </span>
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
                                                <select name="status" onchange="this.form.submit()" class="{{ $invoice['status'] === 'PAID' ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-blue-200 bg-blue-50 text-blue-600' }} w-full min-w-[110px] rounded border pl-2.5 pr-8 py-1.5 text-[10px] font-black uppercase tracking-wide outline-none cursor-pointer transition-all">
                                                    <option value="UNPAID" @selected($invoice['status'] === 'UNPAID')>Belum Bayar</option>
                                                    <option value="PAID" @selected($invoice['status'] === 'PAID')>Lunas</option>
                                                </select>
                                            </form>
                                        @else
                                            @include('partials.status-badge', ['status' => $invoice['status']])
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($currentUser['role'] === 'ADMIN')
                                                <button type="button" onclick="openAddItemModal('{{ $entry['order']['id'] }}', '{{ $invoice['number'] }}', '{{ $invoice['supplier'] }}')" class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100" title="Tambah Barang">
                                                    ＋
                                                </button>
                                            @endif
                                            <a href="{{ route('invoices.preview', ['id' => $entry['order']['id'], 'invoice' => $invoice['number'], 'supplier' => $invoice['supplier']]) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                                Cetak
                                            </a>
                                        </div>
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

    {{-- Modal Tambah Barang ke Invoice --}}
    @if ($currentUser['role'] === 'ADMIN')
        <div id="add-item-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white shadow-2xl">
                <form id="add-item-form" method="POST" action="">
                    @csrf
                    <input type="hidden" name="invoice_number" id="modal-invoice-number">

                    <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h3 class="text-base font-black text-slate-950">Tambah Barang</h3>
                            <p class="mt-0.5 text-xs font-semibold text-slate-500" id="modal-invoice-label"></p>
                        </div>
                        <button type="button" onclick="closeAddItemModal()" class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</button>
                    </header>

                    <div class="space-y-4 px-5 py-5">
                        <label class="block">
                            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Nama Barang</span>
                            <input type="text" name="name" required placeholder="Contoh: AYAM FILET" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Qty</span>
                                <input type="number" name="qty" min="0.01" step="0.01" value="1" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Satuan</span>
                                <input type="text" name="unit" value="KG" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold uppercase text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Harga</span>
                                <input type="number" name="price" min="1" value="0" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                        </div>
                    </div>

                    <footer class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="button" onclick="closeAddItemModal()" class="rounded-lg px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700">Batal</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700">Simpan</button>
                    </footer>
                </form>
            </div>
        </div>

        <script>
            function openAddItemModal(orderId, invoiceNumber, supplierName) {
                const modal = document.getElementById('add-item-modal');
                const form = document.getElementById('add-item-form');
                const label = document.getElementById('modal-invoice-label');
                const invoiceInput = document.getElementById('modal-invoice-number');

                form.action = '/invoices/' + orderId + '/add-item';
                invoiceInput.value = invoiceNumber;
                label.textContent = invoiceNumber + ' — ' + supplierName;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeAddItemModal() {
                const modal = document.getElementById('add-item-modal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.getElementById('add-item-modal')?.addEventListener('click', function(e) {
                if (e.target === this) closeAddItemModal();
            });
        </script>
    @endif
@endsection
