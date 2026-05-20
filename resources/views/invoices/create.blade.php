@extends('layouts.app', ['title' => 'Invoice'])

@section('content')
    @php
        $theme = $supplier['theme'];
        $total = old('items')
            ? collect(old('items'))->sum(fn ($item) => ((float) ($item['qty'] ?? 0)) * ((int) preg_replace('/\D+/', '', (string) ($item['price'] ?? 0))))
            : 0;
    @endphp

    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-3 backdrop-blur-sm sm:p-6">
        <form method="POST" action="{{ route('invoices.store', $order['id']) }}" class="mx-auto my-0 max-w-[1100px] overflow-hidden rounded-xl bg-slate-50 shadow-2xl sm:rounded-2xl" data-invoice-form>
            @csrf
            <input type="hidden" name="supplier" value="{{ $supplier['name'] }}">
            <input type="hidden" name="invoice_no" value="{{ $invoiceNumber }}">
            <input type="hidden" name="invoice_date" value="{{ now()->format('Y-m-d') }}">

            {{-- Header --}}
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h3M5 3h14v18H5z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-black tracking-tight text-slate-950 sm:text-lg">Rekap Tagihan (Invoice)</h1>
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $supplier['name'] }} · {{ $invoiceNumber }}</p>
                    </div>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-2xl leading-none text-slate-400 hover:text-slate-700">&times;</a>
            </header>

            <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                {{-- Top: Ringkasan + Rekening (horizontal) --}}
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Item</p>
                            <p class="mt-0.5 text-sm font-black text-slate-950" data-invoice-item-count>{{ $items->count() }} Barang</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Tagihan</p>
                            <p class="mt-0.5 text-lg font-black text-blue-600">Rp <span data-invoice-total>{{ number_format($total, 0, ',', '.') }}</span></p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 lg:col-span-5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-blue-600">Informasi Rekening — AN. {{ $supplier['bank_account_name'] }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5">
                            @foreach ($supplier['bank_accounts'] as $account)
                                <p class="text-[11px] font-bold text-blue-700">{{ $account['bank'] }}: {{ $account['number'] }}</p>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 lg:col-span-3">
                        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-blue-600">Simpan & Terbitkan</button>
                        <button type="submit" formmethod="GET" formtarget="_blank" formaction="{{ route('invoices.preview', ['id' => $order['id'], 'supplier' => $supplier['name']]) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">Preview PDF</button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600">
                        Lengkapi harga setiap barang sebelum menerbitkan invoice.
                    </div>
                @endif

                        <button type="submit" class="w-full rounded-xl bg-slate-800 px-5 py-4 text-sm font-black uppercase tracking-[0.12em] text-white shadow-lg shadow-slate-300 transition hover:bg-blue-600">
                            Simpan & Terbitkan Invoice
                        </button>
                        <button type="submit" formmethod="GET" formaction="{{ route('invoices.preview', ['id' => $order['id'], 'supplier' => $supplier['name']]) }}" class="flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm font-black uppercase tracking-[0.16em] text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">
                            Preview PDF
                        </button>
                    </div>

                    {{-- Datalist autocomplete --}}
                    <datalist id="invoice-stock-items-list">
                        @foreach ($stockItems as $stock)
                            <option value="{{ $stock['name'] }}" data-unit="{{ $stock['unit'] ?? 'KG' }}"></option>
                        @endforeach
                    </datalist>
                </section>
            </div>

            <footer class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:px-6">
                <span>Ref PO: {{ $order['number'] }}</span>
                <span>{{ now()->format('Y-m-d') }}</span>
            </footer>
        </form>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-invoice-form]');
            if (!form) return;

            const tbody = document.getElementById('invoice-items-tbody');
            const addBtn = document.getElementById('add-invoice-item-btn');
            const emptyState = document.getElementById('invoice-empty-state');
            const itemsCountLabel = document.getElementById('invoice-items-count');
            let itemIndex = {{ $items->count() }};

            const onlyDigits = (value) => String(value).replace(/[^\d]/g, '');
            const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value);

            // Stock items map for autocomplete unit fill
            const stockItemsByName = {};
            @foreach ($stockItems as $stock)
                stockItemsByName['{{ strtoupper($stock['name']) }}'] = '{{ $stock['unit'] ?? 'KG' }}';
            @endforeach

            const refreshTotals = () => {
                let total = 0;
                const rows = tbody.querySelectorAll('.invoice-item-row');
                if (itemsCountLabel) itemsCountLabel.textContent = rows.length;

                rows.forEach((row, index) => {
                    // Update row number
                    const numCell = row.querySelector('td:first-child');
                    if (numCell) numCell.textContent = index + 1;

                    const qty = Number(row.querySelector('.invoice-qty')?.value || 0);
                    const price = Number(onlyDigits(row.querySelector('.invoice-price')?.value || 0));
                    const subtotal = qty * price;
                    const subtotalEl = row.querySelector('.invoice-subtotal');
                    if (subtotalEl) subtotalEl.textContent = formatNumber(subtotal);
                    total += subtotal;
                });

                const totalEl = form.querySelector('[data-invoice-total]');
                if (totalEl) totalEl.textContent = formatNumber(total);

                const countEl = form.querySelector('[data-invoice-item-count]');
                if (countEl) countEl.textContent = rows.length + ' Barang';
            };

            const buildNewRow = (idx) => {
                return `<tr class="invoice-item-row hover:bg-blue-50/30">
                    <td class="px-3 py-2 text-xs font-bold text-slate-400"></td>
                    <td class="px-3 py-2">
                        <input type="hidden" name="items[${idx}][id]" value="">
                        <input type="text" name="items[${idx}][name]" list="invoice-stock-items-list" autocomplete="off" placeholder="Ketik / pilih barang..." required class="invoice-item-name w-full min-w-[160px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="items[${idx}][unit]" value="KG" required class="item-unit-hidden w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold uppercase text-slate-800 outline-none focus:border-blue-500">
                    </td>
                    <td class="px-2 py-2">
                        <input name="items[${idx}][qty]" type="number" min="0.01" step="0.01" value="1" required class="invoice-qty w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none">
                    </td>
                    <td class="px-2 py-2">
                        <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                            <span class="mr-1 text-[9px] font-bold text-slate-400">Rp</span>
                            <input name="items[${idx}][price]" type="text" inputmode="numeric" value="0" placeholder="0" required class="invoice-price min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-800 outline-none">
                        </div>
                    </td>
                    <td class="px-2 py-2 text-right text-xs font-bold text-slate-900">Rp <span class="invoice-subtotal">0</span></td>
                    <td class="px-2 py-2 text-center">
                        <button type="button" class="remove-invoice-item rounded p-1 text-slate-300 transition-colors hover:bg-red-50 hover:text-red-500" title="Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </td>
                </tr>`;
            };

            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    if (emptyState) emptyState.remove();
                    tbody.insertAdjacentHTML('beforeend', buildNewRow(itemIndex));
                    itemIndex++;
                    const newRow = tbody.lastElementChild;
                    newRow.querySelectorAll('.invoice-qty, .invoice-price').forEach(input => {
                        input.addEventListener('input', refreshTotals);
                    });
                    refreshTotals();
                });
            }

            // Remove item
            tbody.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.remove-invoice-item');
                if (removeBtn) {
                    removeBtn.closest('.invoice-item-row').remove();
                    refreshTotals();
                }
            });

            // Autocomplete unit
            tbody.addEventListener('input', (e) => {
                if (e.target.classList.contains('invoice-item-name')) {
                    const typed = String(e.target.value || '').trim().toUpperCase();
                    const matchedUnit = stockItemsByName[typed];
                    if (matchedUnit) {
                        const row = e.target.closest('.invoice-item-row');
                        const unitInput = row.querySelector('.item-unit-hidden');
                        if (unitInput) unitInput.value = matchedUnit;
                    }
                }
            });

            // Initial listeners
            form.querySelectorAll('.invoice-qty, .invoice-price').forEach((input) => {
                input.addEventListener('input', refreshTotals);
            });

            refreshTotals();
        })();
    </script>
@endsection
