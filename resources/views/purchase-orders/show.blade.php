@extends('layouts.app', ['title' => 'Pesanan Pembelian'])

@section('content')
    @php
        $total = collect($order['items'])->sum(fn ($item) => $item['qty'] * $item['price']);
        $invoiceTotal = collect($order['invoices'] ?? [])->sum('total_amount');
        $dropSchedule = $order['droping_date'] ? $order['droping_date'].' '.$order['droping_time'] : '-';
        $isLocked = in_array($order['status'], ['COMPLETED', 'INVOICED'], true);
        $canEditSupplier = $currentUser['role'] === 'ADMIN' && ! $isLocked;
        $canEditItemValues = $currentUser['role'] === 'ADMIN' && ! $isLocked && $order['status'] === 'PROCESSING';
    @endphp

    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-2 backdrop-blur-sm sm:p-5">
        <section class="mx-auto max-w-[1150px] overflow-hidden rounded-2xl bg-slate-100 shadow-2xl sm:rounded-3xl">
            <header class="flex flex-col gap-4 border-b border-slate-200 bg-white px-4 py-4 sm:px-9 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-start gap-3 sm:gap-4">
                    @include('partials.app-logo', ['class' => 'mt-1 h-12 w-12'])
                    <div class="min-w-0">
                        <h1 class="break-words text-lg font-black tracking-tight text-slate-950 sm:text-2xl">Detail Pesanan: {{ $order['number'] ?? 'Belum Diterbitkan' }}</h1>
                        <div class="mt-2">@include('partials.status-badge', ['status' => $order['status']])</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-4">
                    @if ($currentUser['role'] === 'ADMIN' && ! $isLocked && ! $order['number'])
                        <button type="submit" form="supplier-assignment-form" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 sm:px-5 sm:py-3">
                            Simpan Penugasan &amp; Terbitkan No. PO
                        </button>
                    @endif
                    @if ($order['number'])
                        <a href="{{ route('purchase-orders.preview', $order['id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-black text-slate-600 sm:px-5 sm:py-3">Cetak PDF</a>
                    @endif
                    <a href="{{ route('purchase-orders.index') }}" class="text-3xl leading-none text-slate-400 hover:text-slate-700">&times;</a>
                </div>
            </header>

            <form id="supplier-assignment-form" method="POST" action="{{ route('purchase-orders.suppliers.update', $order['id']) }}" class="space-y-4 px-3 py-4 sm:space-y-6 sm:px-6 sm:py-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/70 sm:p-5">
                        <h2 class="mb-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">ⓘ Informasi PO</h2>
                        <dl class="space-y-3">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:flex-col lg:items-start lg:gap-1 xl:flex-row xl:items-center xl:gap-4">
                                <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Dibuat Oleh</dt>
                                <dd class="break-words text-base font-black text-slate-700">{{ $order['created_by'] }}</dd>
                            </div>
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:flex-col lg:items-start lg:gap-1 xl:flex-row xl:items-center xl:gap-4">
                                <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Tanggal PO</dt>
                                <dd class="break-words text-base font-black text-slate-700">{{ date('d F Y', strtotime($order['date'])) }}</dd>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:flex-col lg:items-start lg:gap-2 xl:flex-row xl:items-center xl:gap-4">
                                <dt class="text-xs font-black uppercase tracking-widest text-slate-400">No. PO</dt>
                                <dd class="min-w-0">
                                    @if ($order['number'])
                                        <span class="break-all text-base font-black text-slate-700">{{ $order['number'] }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-600">
                                            ⚠ Belum Diterbitkan — Tentukan supplier terlebih dahulu
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/70 sm:p-5">
                        <h2 class="mb-4 text-xs font-black uppercase tracking-[0.18em] text-slate-400">⛟ Informasi Logistik</h2>
                        <dl class="space-y-3">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:flex-col lg:items-start lg:gap-1 xl:flex-row xl:items-center xl:gap-4">
                                <dt class="text-xs font-black uppercase tracking-widest text-slate-400">No. SPPG</dt>
                                <dd class="break-words text-base font-black text-slate-700">{{ $order['sppg_code'] }} ({{ $order['sppg'] }})</dd>
                            </div>
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-6 lg:flex-col lg:items-start lg:gap-1 xl:flex-row xl:items-center xl:gap-4">
                                <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Jadwal Drop</dt>
                                <dd class="break-words text-base font-black text-slate-700">{{ $dropSchedule }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg bg-slate-950 p-4 text-white shadow-xl shadow-slate-400/30 sm:p-6">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">$ &nbsp; Total Invoice Keseluruhan</p>
                        <p class="mt-3 break-words text-xl font-black tracking-tight sm:text-3xl">Rp {{ number_format($total, 0, ',', '.') }}</p>
                        <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4">
                            <span class="text-sm font-black uppercase text-slate-400">Jumlah Barang</span>
                            <span class="text-base font-black">{{ count($order['items']) }} Jenis</span>
                        </div>
                    </section>
                </div>

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/70">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">♢ Penugasan Supplier & Daftar Barang</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[880px] table-fixed divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="w-[28%] px-5 py-3 text-left text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Barang & Grade</th>
                                    <th class="w-[32%] px-5 py-3 text-left text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Supplier</th>
                                    <th class="w-[12%] px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Qty</th>
                                    <th class="w-[14%] px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Harga</th>
                                    <th class="w-[14%] px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($order['items'] as $item)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-black uppercase text-slate-900">{{ $item['name'] }}</p>
                                            @if (! empty($item['request']))
                                                <p class="mt-1 text-xs font-bold italic text-orange-600">Ket: {{ $item['request'] }}</p>
                                            @endif
                                            <span class="mt-2 inline-flex rounded bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-600">{{ $item['grade'] }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($canEditSupplier)
                                                <select name="suppliers[]" class="w-full rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-black uppercase text-blue-600 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier }}" @selected($item['supplier'] === $supplier)>{{ $supplier }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="inline-flex w-full rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-black uppercase text-blue-600">{{ $item['supplier'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @if ($canEditItemValues)
                                                <div class="ml-auto flex w-32 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10">
                                                    <input name="qty_actual[]" type="number" min="0.01" step="0.01" value="{{ $item['qty'] }}" class="min-w-0 flex-1 bg-transparent text-right text-sm font-black text-slate-700 outline-none">
                                                    <span class="ml-2 shrink-0 text-xs font-black uppercase text-slate-400">{{ $item['unit'] }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm font-black text-slate-700">{{ $item['qty'] }} <span class="text-xs uppercase text-slate-400">{{ $item['unit'] }}</span></span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @if ($canEditItemValues)
                                                <div class="ml-auto flex w-36 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10">
                                                    <span class="mr-2 text-xs font-black text-slate-400">Rp</span>
                                                    <input name="prices[]" type="text" inputmode="numeric" data-currency-input value="{{ $item['price'] }}" class="min-w-0 flex-1 bg-transparent text-right text-sm font-semibold text-slate-700 outline-none">
                                                </div>
                                            @else
                                                <span class="text-sm font-medium text-slate-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-right text-sm font-black text-slate-950">Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <div class="text-center text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">
                    @include('partials.copyright')
                </div>
                @if ($currentUser['role'] === 'ADMIN' && ! $isLocked && ! $order['number'])
                    <div class="sticky bottom-0 -mx-3 -mb-4 flex flex-col gap-2 border-t border-amber-200 bg-amber-50 px-3 py-3 text-xs font-black uppercase tracking-[0.12em] text-amber-700 sm:-mx-6 sm:-mb-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:tracking-[0.14em]">
                        <span>⚠ Tentukan supplier untuk semua item agar nomor PO diterbitkan dan status berubah ke PROCESSING.</span>
                        <button type="submit" class="underline underline-offset-4">Simpan & Terbitkan</button>
                    </div>
                @elseif ($currentUser['role'] === 'ADMIN' && ! $isLocked)
                    <div class="sticky bottom-0 -mx-3 -mb-4 flex flex-col gap-2 border-t border-emerald-100 bg-emerald-50 px-3 py-3 text-xs font-black uppercase tracking-[0.12em] text-emerald-700 sm:-mx-6 sm:-mb-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:tracking-[0.14em]">
                        <span>{{ $canEditItemValues ? 'Terdapat perubahan supplier, qty, atau harga yang bisa disimpan.' : 'Terdapat perubahan penugasan supplier yang bisa disimpan.' }}</span>
                        <button type="submit" class="underline underline-offset-4">Simpan Sekarang</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection
