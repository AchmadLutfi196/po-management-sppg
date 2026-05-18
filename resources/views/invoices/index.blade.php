@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    <section class="mx-auto max-w-[1440px] space-y-7">
        <nav class="flex gap-4">
            <a href="{{ route('invoices.index') }}" class="{{ $activeTab === 'pending' ? 'bg-white text-blue-600 shadow-md shadow-slate-200/70' : 'text-slate-500' }} rounded-xl px-8 py-3 text-sm font-black uppercase tracking-[0.18em]">
                Siap Rekap Tagihan
            </a>
            <a href="{{ route('invoices.index', ['tab' => 'history']) }}" class="{{ $activeTab === 'history' ? 'bg-white text-emerald-600 shadow-md shadow-slate-200/70' : 'text-slate-500' }} rounded-xl px-8 py-3 text-sm font-black uppercase tracking-[0.18em]">
                Riwayat Invoice
            </a>
        </nav>

        @if ($activeTab === 'pending')
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-14 px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Supplier & Referensi</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Info Item</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Rincian Barang</th>
                                <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($pendingInvoices as $entry)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-7 text-center text-xs font-black text-slate-400">{{ ($pendingInvoices->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-5 py-7">
                                        <p class="text-sm font-black uppercase text-slate-950">{{ $entry['supplier'] }}</p>
                                        <p class="mt-1 text-xs font-black text-slate-700">Ref PO: {{ $entry['order']['number'] }}</p>
                                        <span class="mt-3 inline-flex rounded border border-blue-100 bg-blue-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-blue-600">Siap Tagih</span>
                                    </td>
                                    <td class="px-5 py-7">
                                        <p class="inline-flex rounded-lg bg-slate-50 px-3 py-2 text-xs font-black text-slate-700">{{ 1 }} PO / {{ $entry['items']->count() }} Item</p>
                                        <p class="mt-3 text-xs font-black text-emerald-600">Estimasi: Rp {{ number_format($entry['total'], 0, ',', '.') }}</p>
                                    </td>
                                    <td class="px-5 py-7">
                                        <div class="max-w-md space-y-2">
                                            @foreach ($entry['items'] as $item)
                                                <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                                    <div>
                                                        <p class="text-sm font-black text-slate-900">{{ $item['name'] }}</p>
                                                        <p class="mt-1 text-[10px] font-black uppercase text-slate-400">Ref: {{ $entry['order']['number'] }}</p>
                                                    </div>
                                                    <p class="text-xs font-black text-emerald-600">Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-7 text-right">
                                        <a href="{{ route('invoices.create', ['id' => $entry['order']['id'], 'supplier' => $entry['supplier']]) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-[10px] font-black uppercase tracking-wider text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700">
                                            Buat Invoice
                                            <span class="text-sm">›</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm font-bold text-slate-400">Belum ada tagihan yang siap direkap.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($pendingInvoices->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $pendingInvoices->links() }}
                    </div>
                @endif
            </section>
        @else
            <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Tagihan Beredar</p>
                    <p class="mt-3 text-2xl font-black text-slate-950">Rp {{ number_format($stats['total'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Lunas</p>
                    <p class="mt-3 text-2xl font-black text-emerald-600">Rp {{ number_format($stats['paid'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Belum Dibayar</p>
                    <p class="mt-3 text-2xl font-black text-rose-600">Rp {{ number_format($stats['unpaid'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Dokumen</p>
                    <p class="mt-3 text-2xl font-black text-slate-950">{{ $stats['count'] }} Invoice</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
                <div class="overflow-x-auto">
                    <table class="min-w-[1050px] divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-14 px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No Invoice</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Supplier</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Kepada</th>
                                <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total</th>
                                <th class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Status</th>
                                <th class="px-5 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($historyInvoices as $entry)
                                @php($invoice = $entry['invoice'])
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-6 text-center text-xs font-black text-slate-400">{{ ($historyInvoices->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-5 py-6">
                                        <p class="text-sm font-black text-slate-950">{{ $invoice['number'] }}</p>
                                        <p class="mt-1 text-xs font-black text-slate-500">Tgl: {{ date('m/d/Y', strtotime($invoice['date'])) }}</p>
                                        <span class="mt-2 inline-flex rounded border border-blue-100 bg-blue-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-blue-600">Ref PO: {{ $entry['order']['number'] }}</span>
                                    </td>
                                    <td class="px-5 py-6 text-sm font-black uppercase text-slate-800">{{ $invoice['supplier'] }}</td>
                                    <td class="px-5 py-6 text-sm font-black uppercase text-slate-500">{{ $entry['order']['sppg'] }}</td>
                                    <td class="px-5 py-6 text-right text-sm font-black text-slate-950">Rp {{ number_format($invoice['total_amount'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-6">
                                        @if ($currentUser['role'] === 'ADMIN')
                                            <form method="POST" action="{{ route('invoices.status.update', $entry['order']['id']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="invoice_no" value="{{ $invoice['number'] }}">
                                                <select name="status" onchange="this.form.submit()" class="{{ $invoice['status'] === 'PAID' ? 'border-emerald-200 bg-emerald-50 text-emerald-600' : 'border-blue-200 bg-blue-50 text-blue-600' }} rounded border px-3 py-2 text-[10px] font-black uppercase tracking-wider outline-none">
                                                    <option value="UNPAID" @selected($invoice['status'] === 'UNPAID')>Belum Bayar</option>
                                                    <option value="PAID" @selected($invoice['status'] === 'PAID')>Lunas</option>
                                                </select>
                                            </form>
                                        @else
                                            @include('partials.status-badge', ['status' => $invoice['status']])
                                        @endif
                                    </td>
                                    <td class="px-5 py-6 text-right">
                                        <a href="{{ route('invoices.preview', ['id' => $entry['order']['id'], 'invoice' => $invoice['number'], 'supplier' => $invoice['supplier']]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                                            Cetak
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-sm font-bold text-slate-400">Belum ada riwayat invoice.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($historyInvoices->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $historyInvoices->links() }}
                    </div>
                @endif
            </section>
        @endif
    </section>
@endsection
