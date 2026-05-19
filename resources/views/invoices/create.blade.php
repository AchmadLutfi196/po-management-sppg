@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    @php
        $theme = $supplier['theme'];
        $total = old('items')
            ? collect(old('items'))->sum(fn ($item) => ((float) ($item['qty'] ?? 0)) * ((int) preg_replace('/\D+/', '', (string) ($item['price'] ?? 0))))
            : 0;
    @endphp

    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-4 backdrop-blur-sm">
        <form method="POST" action="{{ route('invoices.store', $order['id']) }}" class="mx-auto max-h-[calc(100vh-2rem)] max-w-[1280px] overflow-y-auto rounded-3xl bg-slate-50 shadow-2xl" data-invoice-form>
            @csrf
            <input type="hidden" name="supplier" value="{{ $supplier['name'] }}">
            <input type="hidden" name="invoice_no" value="{{ $invoiceNumber }}">
            <input type="hidden" name="invoice_date" value="{{ now()->format('Y-m-d') }}">

            <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-6">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h3M5 3h14v18H5z" />
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950">Rekap Tagihan (Invoice)</h1>
                        <p class="mt-1 text-xs font-black uppercase tracking-[0.22em] text-slate-400">{{ $supplier['name'] }}</p>
                    </div>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-3xl leading-none text-slate-400 hover:text-slate-700">&times;</a>
            </header>

            <div class="grid grid-cols-1 gap-10 px-8 py-10 lg:grid-cols-[1fr_365px]">
                <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                    <h2 class="mb-4 flex items-center gap-3 text-sm font-black uppercase tracking-[0.2em] text-slate-400">
                        <span class="text-slate-300">▣</span>
                        Input Harga Per Barang
                    </h2>

                    <div class="space-y-4">
                        @forelse ($items as $item)
                            @php
                                $itemIndex = $loop->index;
                                $price = old("items.$itemIndex.price", $item['price'] > 0 ? $item['price'] : '');
                                $qty = old("items.$itemIndex.qty", $item['qty']);
                                $unit = strtoupper($item['unit']);
                            @endphp
                            <article class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-5 md:grid-cols-[1fr_140px_180px_140px] md:items-center">
                                <input type="hidden" name="items[{{ $loop->index }}][name]" value="{{ $item['name'] }}">
                                <input type="hidden" name="items[{{ $loop->index }}][unit]" value="{{ $item['unit'] }}">
                                <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item['id'] ?? '' }}">

                                <div>
                                    <p class="text-lg font-black text-slate-950">{{ $item['name'] }}</p>
                                    <p class="mt-2 text-xs font-black uppercase tracking-wider text-slate-400">Ref: {{ $order['number'] }}</p>
                                    <p class="mt-1 text-xs font-black uppercase tracking-wider text-slate-400">* {{ number_format($item['qty'], 0, ',', '.') }} {{ strtoupper($item['unit']) }} Total</p>
                                </div>

                                <label>
                                    <span class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-400">Qty Tagihan ({{ $unit }})</span>
                                    <div class="flex items-center rounded-lg border border-slate-200 bg-white px-4 py-3 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10">
                                        <input name="items[{{ $loop->index }}][qty]" type="number" min="0.01" step="0.01" value="{{ $qty }}" class="invoice-qty min-w-0 flex-1 bg-transparent text-sm font-black text-slate-800 outline-none">
                                        <span class="ml-3 shrink-0 rounded bg-slate-100 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $unit }}</span>
                                    </div>
                                </label>

                                <label>
                                    <span class="mb-2 block text-[10px] font-black uppercase tracking-wider text-slate-400">Harga per {{ $unit }}</span>
                                    <div class="flex items-center rounded-lg border border-slate-200 bg-white px-4 py-3 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10">
                                        <span class="mr-2 text-xs font-black text-slate-400">Rp.</span>
                                        <input name="items[{{ $loop->index }}][price]" type="text" inputmode="numeric" data-currency-input value="{{ $price }}" placeholder="Input Harga" class="invoice-price min-w-0 flex-1 bg-transparent text-sm font-black text-slate-800 outline-none">
                                    </div>
                                </label>

                                <div class="text-right">
                                    <span class="block text-[10px] font-black uppercase tracking-wider text-slate-400">Subtotal</span>
                                    <p class="mt-3 text-lg font-black text-slate-950">Rp <span class="invoice-subtotal">0</span></p>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-10 text-center text-sm font-bold text-slate-400">
                                Tidak ada item yang siap dibuat invoice untuk supplier ini.
                            </div>
                        @endforelse
                    </div>
                </section>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-7 shadow-md shadow-slate-200/70">
                    <h2 class="mb-8 flex items-center gap-3 text-sm font-black uppercase tracking-[0.2em] text-slate-400">
                        <span class="text-slate-300">▭</span>
                        Ringkasan Pembayaran
                    </h2>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <span class="text-sm font-bold text-slate-500">Total Item</span>
                            <span class="text-sm font-black text-slate-950">{{ $items->count() }} Barang</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-slate-500">Total Tagihan</span>
                            <span class="text-2xl font-black text-blue-600">Rp <span data-invoice-total>{{ number_format($total, 0, ',', '.') }}</span></span>
                        </div>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Informasi Rekening</p>
                            <p class="mt-4 text-xs font-black uppercase text-blue-600">Transfer Bank</p>
                            <p class="mt-1 text-xs font-black uppercase text-blue-600">AN. {{ $supplier['bank_account_name'] }}</p>
                            <div class="mt-3 space-y-1.5">
                                @foreach ($supplier['bank_accounts'] as $account)
                                    <p class="text-xs font-black uppercase text-blue-600">{{ $account['bank'] }}: {{ $account['number'] }}</p>
                                @endforeach
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-xl border border-rose-100 bg-rose-50 p-4 text-xs font-bold text-rose-600">
                                Lengkapi harga setiap barang sebelum menerbitkan invoice.
                            </div>
                        @endif

                        <button type="submit" class="w-full rounded-xl bg-slate-800 px-5 py-4 text-sm font-black uppercase tracking-[0.12em] text-white shadow-lg shadow-slate-300 transition hover:bg-blue-600">
                            Simpan & Terbitkan Invoice
                        </button>
                        <button type="submit" formmethod="GET" formtarget="_blank" formaction="{{ route('invoices.preview', ['id' => $order['id'], 'supplier' => $supplier['name']]) }}" class="flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm font-black uppercase tracking-[0.16em] text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                            Preview PDF
                        </button>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-invoice-form]');

            if (!form) {
                return;
            }

            const onlyDigits = (value) => String(value).replace(/[^\d]/g, '');
            const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);

            const refreshTotals = () => {
                let total = 0;

                form.querySelectorAll('article').forEach((row) => {
                    const qty = Number(row.querySelector('.invoice-qty')?.value || 0);
                    const price = Number(onlyDigits(row.querySelector('.invoice-price')?.value || 0));
                    const subtotal = qty * price;
                    const subtotalTarget = row.querySelector('.invoice-subtotal');

                    if (subtotalTarget) {
                        subtotalTarget.textContent = formatNumber(subtotal);
                    }

                    total += subtotal;
                });

                form.querySelector('[data-invoice-total]').textContent = formatNumber(total);
            };

            form.querySelectorAll('.invoice-qty, .invoice-price').forEach((input) => {
                input.addEventListener('input', refreshTotals);
            });

            refreshTotals();
        })();
    </script>
@endsection
