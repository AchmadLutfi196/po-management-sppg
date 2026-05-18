@extends('layouts.app', ['title' => 'Surat Jalan (Delivery)'])

@section('content')
    <section class="mx-auto max-w-[1440px] space-y-7">
        <form method="GET" class="flex flex-col justify-between gap-5 rounded-xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70 lg:flex-row lg:items-center">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-950">Manajemen Surat Jalan</h2>
                <p class="mt-1 text-sm font-medium text-slate-500">Kelola pengiriman barang dan bukti drop barang.</p>
            </div>
            <div class="relative w-full lg:w-96">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">⌕</span>
                <input name="search" value="{{ $filters['search'] ?? '' }}" type="search" placeholder="Cari No PO / No SJ..." class="w-full rounded-lg border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm font-semibold text-slate-600 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">
            </div>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-[1260px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Identitas PO</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Barang & Qty</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Info Pengiriman</th>
                            <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Bukti Drop</th>
                            <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            @php
                                $sequence    = ($orders->firstItem() ?? 1) + $loop->index;
                                $hasDelivery = ! empty($order['delivery']);
                                $supplierCount = collect($order['items'])->pluck('supplier')->unique()->count();
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-7 text-center text-xs font-black text-slate-400">{{ $sequence }}</td>
                                <td class="px-5 py-7">
                                    <p class="max-w-64 truncate text-xs font-black text-slate-950">{{ $order['number'] }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="rounded bg-blue-50 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-blue-600">{{ $order['created_by'] }}</span>
                                        <span class="text-[10px] font-black text-slate-400">{{ date('m/d/Y', strtotime($order['date'])) }}</span>
                                    </div>
                                    <span class="mt-2 inline-flex rounded border border-slate-200 bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase text-slate-700">{{ $order['sppg'] }}</span>
                                </td>
                                <td class="px-5 py-7">
                                    <div class="overflow-hidden rounded border border-slate-100 bg-slate-50">
                                        <div class="grid grid-cols-[1fr_150px_70px] gap-3 px-3 py-2 text-[9px] font-black uppercase tracking-[0.18em] text-slate-400">
                                            <span>Nama Barang</span>
                                            <span>Supplier</span>
                                            <span class="text-right">Qty</span>
                                        </div>
                                        @foreach ($order['items'] as $item)
                                            <div class="grid grid-cols-[1fr_150px_70px] gap-3 border-t border-slate-100 px-3 py-2 text-[10px] font-black">
                                                <span class="truncate text-slate-900">{{ $item['name'] }}</span>
                                                <span class="truncate rounded bg-blue-50 px-2 py-0.5 text-blue-600">{{ $item['supplier'] }}</span>
                                                <span class="text-right text-slate-900">{{ $item['qty'] }} {{ strtoupper($item['unit']) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-7">
                                    @if ($hasDelivery)
                                        <p class="text-xs font-black text-slate-950">{{ $order['delivery']['number'] }}</p>
                                        <p class="mt-1 text-[10px] font-black text-emerald-600">Tgl: {{ date('m/d/Y', strtotime($order['delivery']['date'])) }}</p>
                                    @else
                                        <p class="text-xs font-black italic text-slate-400">Menunggu SJ</p>
                                    @endif
                                </td>
                                <td class="px-5 py-7">
                                    @if ($hasDelivery)
                                        <span class="inline-flex rounded border border-emerald-100 bg-emerald-50 px-3 py-1 text-[10px] font-black text-emerald-600">Ada Bukti</span>
                                        <p class="mt-2 text-[10px] font-black uppercase text-slate-400">+{{ max(1, $supplierCount) }} item foto</p>
                                    @else
                                        <span class="text-xs font-bold text-slate-300">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-7 text-right">
                                    @if ($hasDelivery)
                                        <a href="{{ route('surat-jalan.show', $order['id']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                            Lihat / Edit
                                            <span class="text-sm">›</span>
                                        </a>
                                    @else
                                        <a href="{{ route('surat-jalan.show', $order['id']) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                                            Proses Kirim
                                            <span class="text-sm">›</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm font-bold text-slate-400">Belum ada surat jalan.</td>
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
