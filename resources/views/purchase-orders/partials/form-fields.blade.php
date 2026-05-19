@php
    $isEdit = isset($order);
    $items = $order['items'] ?? [
        ['name' => '', 'qty' => 0, 'unit' => 'KG', 'grade' => 'A', 'price' => 0, 'supplier' => '', 'request' => null],
        ['name' => '', 'qty' => 0, 'unit' => 'KG', 'grade' => 'A', 'price' => 0, 'supplier' => '', 'request' => null],
    ];
    $total = collect($items)->sum(fn ($item) => ($item['qty'] ?? 0) * ($item['price'] ?? 0));
@endphp

<div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
    {{-- Top: Info Dokumen + Logistik + Estimasi Total (horizontal) --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
        {{-- Informasi Dokumen --}}
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-5">
            <h2 class="mb-3 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                <span class="h-3 w-0.5 rounded-full bg-blue-600"></span>
                Informasi Dokumen
            </h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Nomor PO</span>
                    <input value="{{ $order['number'] ?? 'Akan Diterbitkan' }}" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-500 outline-none">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tanggal PO</span>
                    <input name="date" type="date" value="{{ old('date', $order['date'] ?? now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Dibuat Oleh</span>
                    <input name="created_by" value="{{ old('created_by', $order['created_by'] ?? $currentUser['name']) }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                </label>
            </div>
        </section>

        {{-- Logistik --}}
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-5">
            <h2 class="mb-3 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                <span class="h-3 w-0.5 rounded-full bg-orange-500"></span>
                Logistik
            </h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="block sm:col-span-1">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">SPPG</span>
                    @if ($currentUser['role'] === 'SPPG')
                        <input value="{{ $currentUser['id'] }} ({{ $currentUser['name'] }})" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 outline-none">
                        <input type="hidden" name="sppg_code" value="{{ $currentUser['id'] }}">
                    @else
                        <select name="sppg_code" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            @foreach ($sppgs as $sppg)
                                <option value="{{ $sppg->code }}" @selected(old('sppg_code', $order['sppg_code'] ?? 'M1101') === $sppg->code)>{{ $sppg->code }} — {{ $sppg->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </label>
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tgl Drop</span>
                    <input name="droping_date" type="date" value="{{ old('droping_date', $order['droping_date'] ?? '') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                </label>
                <label class="block">
                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Jam Drop</span>
                    <input name="droping_time" type="time" value="{{ old('droping_time', $order['droping_time'] ?? '') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                </label>
            </div>
        </section>

        {{-- Estimasi Total --}}
        <section class="flex flex-col justify-center rounded-xl bg-slate-900 p-4 text-white shadow-lg lg:col-span-2">
            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Estimasi Total</p>
            <p class="mt-1 break-words text-xl font-black tracking-tight" id="total-display">Rp {{ number_format($total, 0, ',', '.') }}</p>
        </section>
    </div>

    {{-- Daftar Barang --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-tight text-slate-700">
                <span class="text-lg text-slate-300">◇</span>
                Daftar Barang (<span id="items-count">{{ count($items) }}</span>)
            </h2>
            <button type="button" id="add-item-btn" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-blue-600 shadow-sm transition-colors hover:bg-blue-50">＋ Tambah</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="w-10 px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">#</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Barang</th>
                            <th class="w-40 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Catatan</th>
                            <th class="w-16 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Grade</th>
                            <th class="w-24 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Qty</th>
                            <th class="w-16 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Satuan</th>
                            <th class="w-28 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Harga</th>
                            @if (($currentUser['role'] ?? '') !== 'SPPG')
                                <th class="px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier</th>
                            @endif
                            <th class="w-10 px-2 py-2.5 text-center text-[10px] font-bold uppercase tracking-wide text-slate-400"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="items-tbody">
                        @foreach ($items as $item)
                            @php($itemIndex = $loop->index)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-2 text-xs font-bold text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item['id'] ?? '' }}">
                                    <input type="hidden" name="items[{{ $itemIndex }}][stock_item_id]" class="stock-item-id-input" value="{{ old("items.$itemIndex.stock_item_id", $item['stock_item_id'] ?? '') }}">
                                    <input
                                        type="text"
                                        name="items[{{ $itemIndex }}][name]"
                                        list="stock-items-list"
                                        autocomplete="off"
                                        placeholder="Ketik / pilih barang..."
                                        value="{{ old("items.$itemIndex.name", $item['name'] ?? '') }}"
                                        class="stock-item-input w-full min-w-[160px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                                    >
                                </td>
                                <td class="px-2 py-2"><input name="items[{{ $itemIndex }}][request]" value="{{ old("items.$itemIndex.request", $item['request'] ?? '') }}" placeholder="-" class="w-full min-w-[120px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-normal text-slate-600 outline-none"></td>
                                <td class="px-2 py-2">
                                    <select name="items[{{ $itemIndex }}][grade]" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500">
                                        <option value="A" @selected(old("items.$itemIndex.grade", $item['grade'] ?? 'A') === 'A')>A</option>
                                        <option value="B" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'B')>B</option>
                                        <option value="C" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'C')>C</option>
                                        <option value="REJECT" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'REJECT')>REJ</option>
                                    </select>
                                </td>
                                <td class="px-2 py-2"><input name="items[{{ $itemIndex }}][qty]" type="number" min="0.01" step="0.01" value="{{ old("items.$itemIndex.qty", $item['qty'] ?? 0) }}" class="w-full min-w-[88px] rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-800 outline-none"></td>
                                <td class="px-2 py-2"><input name="items[{{ $itemIndex }}][unit]" value="{{ old("items.$itemIndex.unit", strtoupper($item['unit'] ?? 'KG')) }}" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none"></td>
                                <td class="px-2 py-2">
                                    <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                        <span class="mr-1 text-[9px] font-bold text-slate-400">Rp</span>
                                        <input name="items[{{ $itemIndex }}][price]" type="text" inputmode="numeric" data-currency-input value="{{ old("items.$itemIndex.price", $item['price'] ?? 0) }}" class="min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-800 outline-none">
                                    </div>
                                </td>
                                @if (($currentUser['role'] ?? '') !== 'SPPG')
                                    <td class="px-2 py-2">
                                        <select name="items[{{ $itemIndex }}][supplier]" class="w-full min-w-[120px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500">
                                            <option value="">—</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier }}" @selected(old("items.$itemIndex.supplier", $item['supplier'] ?? '') === $supplier)>{{ $supplier }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endif
                                <td class="px-2 py-2 text-center">
                                    <button type="button" class="remove-item-btn rounded p-1 text-slate-300 transition-colors hover:bg-red-50 hover:text-red-500" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Datalist global untuk autocomplete nama barang --}}
        <datalist id="stock-items-list">
            @foreach ($stockItems as $stock)
                <option value="{{ $stock['name'] }}" data-id="{{ $stock['id'] }}" data-unit="{{ $stock['unit'] ?? 'KG' }}"></option>
            @endforeach
        </datalist>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('items-tbody');
        const btnAdd = document.getElementById('add-item-btn');
        const itemsCountLabel = document.getElementById('items-count');
        let itemIndexCounter = {{ count($items) }};

        const stockItems = @json($stockItems);
        @if (($currentUser['role'] ?? '') !== 'SPPG')
        const suppliers = @json($suppliers);
        @else
        const suppliers = [];
        @endif
        const isSppg = {{ ($currentUser['role'] ?? '') === 'SPPG' ? 'true' : 'false' }};

        // Map nama → {id, unit} (case-insensitive) untuk lookup cepat saat autocomplete
        const stockByName = {};
        stockItems.forEach(function (s) {
            stockByName[String(s.name).toUpperCase()] = { id: s.id, unit: s.unit ?? 'KG' };
        });

        function buildSupplierOptions() {
            let html = '<option value="">—</option>';
            suppliers.forEach(function (s) {
                html += `<option value="${s}">${s}</option>`;
            });
            return html;
        }

        function buildNewRow(idx) {
            const supplierCol = isSppg ? '' : `
                <td class="px-2 py-2">
                    <select name="items[${idx}][supplier]" class="w-full min-w-[120px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500">
                        ${buildSupplierOptions()}
                    </select>
                </td>`;

            return `<tr class="hover:bg-slate-50/50">
                <td class="px-3 py-2 text-xs font-bold text-slate-400"></td>
                <td class="px-3 py-2">
                    <input type="hidden" name="items[${idx}][id]" value="">
                    <input type="hidden" name="items[${idx}][stock_item_id]" class="stock-item-id-input" value="">
                    <input
                        type="text"
                        name="items[${idx}][name]"
                        list="stock-items-list"
                        autocomplete="off"
                        placeholder="Ketik / pilih barang..."
                        value=""
                        class="stock-item-input w-full min-w-[160px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                    >
                </td>
                <td class="px-2 py-2"><input name="items[${idx}][request]" value="" placeholder="-" class="w-full min-w-[120px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-normal text-slate-600 outline-none"></td>
                <td class="px-2 py-2">
                    <select name="items[${idx}][grade]" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="REJECT">REJ</option>
                    </select>
                </td>
                <td class="px-2 py-2"><input name="items[${idx}][qty]" type="number" min="0.01" step="0.01" value="0" class="w-full min-w-[88px] rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-800 outline-none"></td>
                <td class="px-2 py-2"><input name="items[${idx}][unit]" value="KG" class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none"></td>
                <td class="px-2 py-2">
                    <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                        <span class="mr-1 text-[9px] font-bold text-slate-400">Rp</span>
                        <input name="items[${idx}][price]" type="text" inputmode="numeric" value="0" class="min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-800 outline-none">
                    </div>
                </td>
                ${supplierCol}
                <td class="px-2 py-2 text-center">
                    <button type="button" class="remove-item-btn rounded p-1 text-slate-300 transition-colors hover:bg-red-50 hover:text-red-500" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </td>
            </tr>`;
        }

        function updateRowNumbersAndButtons() {
            const rows = tbody.querySelectorAll('tr');
            if (itemsCountLabel) itemsCountLabel.textContent = rows.length;
            rows.forEach(function (row, index) {
                row.querySelector('td:first-child').textContent = index + 1;
                const removeBtn = row.querySelector('.remove-item-btn');
                if (removeBtn) {
                    if (rows.length === 1) {
                        removeBtn.classList.add('opacity-30', 'cursor-not-allowed');
                        removeBtn.disabled = true;
                    } else {
                        removeBtn.classList.remove('opacity-30', 'cursor-not-allowed');
                        removeBtn.disabled = false;
                    }
                }
            });
        }

        updateRowNumbersAndButtons();

        if (btnAdd) {
            btnAdd.addEventListener('click', function () {
                const newRowHtml = buildNewRow(itemIndexCounter);
                tbody.insertAdjacentHTML('beforeend', newRowHtml);
                itemIndexCounter++;
                updateRowNumbersAndButtons();
            });
        }

        tbody.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.remove-item-btn');
            if (removeBtn && !removeBtn.disabled) {
                const tr = removeBtn.closest('tr');
                if (tbody.querySelectorAll('tr').length > 1) {
                    tr.remove();
                    updateRowNumbersAndButtons();
                }
            }
        });

        tbody.addEventListener('input', function (e) {
            if (e.target.classList.contains('stock-item-input')) {
                const tr = e.target.closest('tr');
                const typed = String(e.target.value || '').trim();
                const match = stockByName[typed.toUpperCase()];

                const idInput = tr.querySelector('.stock-item-id-input');
                const unitInput = tr.querySelector('input[name$="[unit]"]');

                if (match) {
                    if (idInput) idInput.value = match.id;
                    if (unitInput && match.unit) unitInput.value = match.unit;
                } else {
                    if (idInput) idInput.value = '';
                    // unit dibiarkan agar user bisa input manual untuk barang custom
                }
            }
        });
    });
</script>
