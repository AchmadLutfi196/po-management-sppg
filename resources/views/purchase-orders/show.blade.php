@extends('layouts.app', ['title' => 'Pesanan Pembelian'])

@section('content')
    @php
        $total = collect($order['items'])->sum(fn ($item) => $item['qty'] * $item['price']);
        $invoiceTotal = collect($order['invoices'] ?? [])->sum('total_amount');
        $dropSchedule = $order['droping_date'] ? $order['droping_date'].' '.$order['droping_time'] : '-';
    @endphp

    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-5 backdrop-blur-sm">
        <section class="mx-auto max-w-[1150px] overflow-hidden rounded-3xl bg-slate-100 shadow-2xl">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-9 py-4">
                <div class="flex items-start gap-4">
                    <span class="mt-1 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-lg font-black text-white">P</span>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Detail Pesanan: {{ $order['number'] ?? 'Belum Diterbitkan' }}</h1>
                        <div class="mt-2">@include('partials.status-badge', ['status' => $order['status']])</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @if ($currentUser['role'] === 'ADMIN' && ! $order['number'])
                        <button type="submit" form="supplier-assignment-form" class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20">
                            Simpan Penugasan &amp; Terbitkan No. PO
                        </button>
                    @endif
                    @if ($order['number'])
                        <a href="{{ route('purchase-orders.preview', $order['id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-black text-slate-600">Cetak PDF</a>
                    @endif
                    <a href="{{ route('purchase-orders.index') }}" class="text-3xl leading-none text-slate-400 hover:text-slate-700">&times;</a>
                </div>
            </header>

            <form id="supplier-assignment-form" method="POST" action="{{ route('purchase-orders.suppliers.update', $order['id']) }}" class="space-y-8 px-9 py-6">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-9 lg:grid-cols-2">
                    <div class="space-y-7">
                        <section class="rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                            <h2 class="mb-6 text-sm font-black uppercase tracking-[0.18em] text-slate-400">ⓘ Informasi PO</h2>
                            <dl class="space-y-5">
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Dibuat Oleh</dt>
                                    <dd class="text-base font-black text-slate-700">{{ $order['created_by'] }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Tanggal PO</dt>
                                    <dd class="text-base font-black text-slate-700">{{ date('d F Y', strtotime($order['date'])) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-xs font-black uppercase tracking-widest text-slate-400">No. PO</dt>
                                    <dd>
                                        @if ($order['number'])
                                            <span class="text-base font-black text-slate-700">{{ $order['number'] }}</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-black text-amber-600 border border-amber-200">
                                                ⚠ Belum Diterbitkan — Tentukan supplier terlebih dahulu
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                            <h2 class="mb-6 text-sm font-black uppercase tracking-[0.18em] text-slate-400">⛟ Informasi Logistik</h2>
                            <dl class="space-y-5">
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-xs font-black uppercase tracking-widest text-slate-400">No. SPPG</dt>
                                    <dd class="text-base font-black text-slate-700">{{ $order['sppg_code'] }} ({{ $order['sppg'] }})</dd>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-xs font-black uppercase tracking-widest text-slate-400">Jadwal Drop</dt>
                                    <dd class="text-base font-black text-slate-700">{{ $dropSchedule }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <section class="h-fit rounded-xl bg-slate-950 p-9 text-white shadow-2xl shadow-slate-400/40">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">$ &nbsp; Total Invoice Keseluruhan</p>
                        <p class="mt-5 text-4xl font-black tracking-tight">Rp {{ number_format($invoiceTotal, 0, ',', '.') }}</p>
                        <div class="mt-8 flex items-center justify-between border-t border-white/10 pt-7">
                            <span class="text-sm font-black uppercase text-slate-400">Jumlah Barang</span>
                            <span class="text-base font-black">{{ count($order['items']) }} Jenis</span>
                        </div>
                    </section>
                </div>

                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
                    <div class="border-b border-slate-100 px-7 py-5">
                        <h2 class="text-sm font-black uppercase tracking-[0.18em] text-blue-600">♢ Penugasan Supplier & Daftar Barang</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[880px] divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Barang & Grade</th>
                                    <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Supplier</th>
                                    <th class="px-7 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Qty</th>
                                    <th class="px-7 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Harga</th>
                                    <th class="px-7 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($order['items'] as $item)
                                    <tr>
                                        <td class="px-7 py-6">
                                            <p class="text-base font-black uppercase text-slate-900">{{ $item['name'] }}</p>
                                            @if (! empty($item['request']))
                                                <p class="mt-1 text-xs font-bold italic text-orange-600">Ket: {{ $item['request'] }}</p>
                                            @endif
                                            <span class="mt-2 inline-flex rounded bg-emerald-50 px-2 py-1 text-xs font-black text-emerald-600">{{ $item['grade'] }}</span>
                                        </td>
                                        <td class="px-7 py-6">
                                            @if ($currentUser['role'] === 'ADMIN')
                                                <select name="suppliers[]" class="min-w-72 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-black uppercase text-blue-600 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier }}" @selected($item['supplier'] === $supplier)>{{ $supplier }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span class="inline-flex min-w-72 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-black uppercase text-blue-600">{{ $item['supplier'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-7 py-6 text-right text-base font-black text-slate-700">{{ $item['qty'] }} <span class="text-xs uppercase text-slate-400">{{ $item['unit'] }}</span></td>
                                        <td class="px-7 py-6 text-right text-base font-medium text-slate-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                                        <td class="px-7 py-6 text-right text-base font-black text-slate-950">Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                @if ($currentUser['role'] === 'ADMIN' && ! $order['number'])
                    <div class="sticky bottom-0 -mx-9 -mb-6 flex items-center justify-between border-t border-amber-200 bg-amber-50 px-9 py-4 text-xs font-black uppercase tracking-[0.18em] text-amber-700">
                        <span>⚠ Tentukan supplier untuk semua item agar nomor PO diterbitkan dan status berubah ke PROCESSING.</span>
                        <button type="submit" class="underline underline-offset-4">Simpan & Terbitkan</button>
                    </div>
                @elseif ($currentUser['role'] === 'ADMIN')
                    <div class="sticky bottom-0 -mx-9 -mb-6 flex items-center justify-between border-t border-emerald-100 bg-emerald-50 px-9 py-4 text-xs font-black uppercase tracking-[0.18em] text-emerald-700">
                        <span>Terdapat perubahan penugasan supplier yang bisa disimpan.</span>
                        <button type="submit" class="underline underline-offset-4">Simpan Sekarang</button>
                    </div>
                @endif
            </form>
        </section>
    </div>
@endsection
