@php
    $isEdit = isset($order);
    $items = $order['items'] ?? [
        ['name' => '', 'qty' => 0, 'unit' => 'KG', 'grade' => 'A', 'price' => 0, 'supplier' => '', 'request' => null],
        ['name' => '', 'qty' => 0, 'unit' => 'KG', 'grade' => 'A', 'price' => 0, 'supplier' => '', 'request' => null],
    ];
    $total = collect($items)->sum(fn ($item) => ($item['qty'] ?? 0) * ($item['price'] ?? 0));
@endphp

<div class="grid grid-cols-1 gap-10 px-8 py-10 lg:grid-cols-[400px_1fr] xl:px-20">
    <aside class="space-y-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
            <div class="mb-8 flex items-center gap-3 border-b border-slate-100 pb-6">
                <span class="h-4 w-1 rounded-full bg-blue-600"></span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-xs font-black text-slate-400">i</span>
                <h2 class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Informasi Dokumen</h2>
            </div>
            <div class="space-y-7">
                <label class="block">
                    <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Nomor PO</span>
                    <input value="{{ $order['number'] ?? 'Akan Diterbitkan' }}" readonly class="w-full rounded-lg border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-black text-slate-500 outline-none">
                </label>
                <label class="block">
                    <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Tanggal PO</span>
                    <input name="date" type="date" value="{{ old('date', $order['date'] ?? now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                </label>
                <label class="block">
                    <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Dibuat Oleh</span>
                    <input name="created_by" value="{{ old('created_by', $order['created_by'] ?? $currentUser['name']) }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
            <div class="mb-8 flex items-center gap-3 border-b border-slate-100 pb-6">
                <span class="h-4 w-1 rounded-full bg-orange-500"></span>
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-xs font-black text-slate-400">⛟</span>
                <h2 class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Logistik</h2>
            </div>
            <div class="space-y-7">
                <label class="block">
                    <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Nomor SPPG</span>
                    @if ($currentUser['role'] === 'SPPG')
                        <input value="{{ $currentUser['id'] }} ({{ $currentUser['name'] }})" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 outline-none">
                        <input type="hidden" name="sppg_code" value="{{ $currentUser['id'] }}">
                    @else
                        <select name="sppg_code" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            @foreach ($sppgs as $sppg)
                                <option value="{{ $sppg->code }}" @selected(old('sppg_code', $order['sppg_code'] ?? 'M1101') === $sppg->code)>{{ $sppg->code }} ({{ $sppg->name }})</option>
                            @endforeach
                        </select>
                    @endif
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="block">
                        <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Tgl Drop</span>
                        <input name="droping_date" type="date" value="{{ old('droping_date', $order['droping_date'] ?? '') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Jam Drop</span>
                        <input name="droping_time" type="time" value="{{ old('droping_time', $order['droping_time'] ?? '') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </label>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-2xl shadow-slate-400/40">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Estimasi Total</p>
            <p class="mt-4 text-3xl font-black tracking-tight">Rp {{ number_format($total, 0, ',', '.') }}</p>
        </section>
    </aside>

    <section class="min-w-0">
        <div class="mb-8 flex items-center justify-between gap-4">
            <h2 class="flex items-center gap-3 text-lg font-black uppercase tracking-tight text-slate-800">
                <span class="text-2xl text-slate-400">◇</span>
                Daftar Barang (<span id="items-count">{{ count($items) }}</span>)
            </h2>
            <button type="button" id="add-item-btn" class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-black text-blue-600 shadow-md shadow-slate-200/70 hover:bg-blue-50 transition-colors">＋ Tambah Barang</button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-[840px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">#</th>
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Barang</th>
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Grade</th>
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Qty</th>
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Satuan</th>
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Harga Satuan</th>
                            @if (($currentUser['role'] ?? '') !== 'SPPG')
                                <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Supplier</th>
                            @endif
                            <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-[0.16em] text-slate-400">Catatan</th>
                            <th class="px-5 py-4 text-center text-xs font-black uppercase tracking-[0.16em] text-slate-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="items-tbody">
                        @foreach ($items as $item)
                            @php($itemIndex = $loop->index)
                            <tr>
                                <td class="px-5 py-4 text-xs font-black text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-5 py-4">
                                    <input type="hidden" name="items[{{ $itemIndex }}][id]" value="{{ $item['id'] ?? '' }}">
                                    <input type="hidden" name="items[{{ $itemIndex }}][name]" value="{{ old("items.$itemIndex.name", $item['name'] ?? '') }}">
                                    <select name="items[{{ $itemIndex }}][stock_item_id]" class="stock-item-select w-52 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                                        <option value="" data-unit="KG" data-name="">Pilih...</option>
                                        @foreach ($stockItems as $stock)
                                            <option value="{{ $stock['id'] }}" data-unit="{{ $stock['unit'] ?? 'KG' }}" data-name="{{ $stock['name'] }}" @selected((string) old("items.$itemIndex.stock_item_id", $item['stock_item_id'] ?? '') === (string) $stock['id'] || (($item['name'] ?? '') === $stock['name'] && empty($item['stock_item_id'])))>{{ $stock['name'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-5 py-4">
                                    <select name="items[{{ $itemIndex }}][grade]" class="w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                                        <option value="A" @selected(old("items.$itemIndex.grade", $item['grade'] ?? 'A') === 'A')>A</option>
                                        <option value="B" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'B')>B</option>
                                        <option value="C" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'C')>C</option>
                                        <option value="REJECT" @selected(old("items.$itemIndex.grade", $item['grade'] ?? '') === 'REJECT')>REJECT</option>
                                    </select>
                                </td>
                                <td class="px-5 py-4"><input name="items[{{ $itemIndex }}][qty]" type="number" min="0.01" step="0.01" value="{{ old("items.$itemIndex.qty", $item['qty'] ?? 0) }}" class="w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none"></td>
                                <td class="px-5 py-4"><input name="items[{{ $itemIndex }}][unit]" value="{{ old("items.$itemIndex.unit", strtoupper($item['unit'] ?? 'KG')) }}" class="w-24 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none"></td>
                                <td class="px-5 py-4">
                                    <div class="flex w-32 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                                        <span class="mr-2 text-[10px] font-black text-slate-400">Rp.</span>
                                        <input name="items[{{ $itemIndex }}][price]" type="text" inputmode="numeric" data-currency-input value="{{ old("items.$itemIndex.price", $item['price'] ?? 0) }}" class="min-w-0 flex-1 bg-transparent text-sm font-black text-slate-800 outline-none">
                                    </div>
                                </td>
                                @if (($currentUser['role'] ?? '') !== 'SPPG')
                                    <td class="px-5 py-4">
                                        <select name="items[{{ $itemIndex }}][supplier]" class="w-36 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                                            <option value="">Supplier...</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier }}" @selected(old("items.$itemIndex.supplier", $item['supplier'] ?? '') === $supplier)>{{ $supplier }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endif
                                <td class="px-5 py-4"><input name="items[{{ $itemIndex }}][request]" value="{{ old("items.$itemIndex.request", $item['request'] ?? '') }}" placeholder="Catatan..." class="w-44 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-600 outline-none"></td>
                                <td class="px-5 py-4 text-center">
                                    <button type="button" class="remove-item-btn p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Barang">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('items-tbody');
        const btnAdd = document.getElementById('add-item-btn');
        const itemsCountLabel = document.getElementById('items-count');
        let itemIndexCounter = {{ count($items) }};

        // Data stock items untuk dipakai di template baris baru
        const stockItems = @json($stockItems);
        @if (($currentUser['role'] ?? '') !== 'SPPG')
        const suppliers = @json($suppliers);
        @else
        const suppliers = [];
        @endif
        const isSppg = {{ ($currentUser['role'] ?? '') === 'SPPG' ? 'true' : 'false' }};

        function buildStockOptions() {
            let html = '<option value="" data-unit="KG" data-name="">Pilih...</option>';
            stockItems.forEach(function (s) {
                html += `<option value="${s.id}" data-unit="${s.unit ?? 'KG'}" data-name="${s.name}">${s.name}</option>`;
            });
            return html;
        }

        function buildSupplierOptions() {
            let html = '<option value="">Supplier...</option>';
            suppliers.forEach(function (s) {
                html += `<option value="${s}">${s}</option>`;
            });
            return html;
        }

        function buildNewRow(idx) {
            const supplierCol = isSppg ? '' : `
                <td class="px-5 py-4">
                    <select name="items[${idx}][supplier]" class="w-36 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                        ${buildSupplierOptions()}
                    </select>
                </td>`;

            return `<tr>
                <td class="px-5 py-4 text-xs font-black text-slate-400"></td>
                <td class="px-5 py-4">
                    <input type="hidden" name="items[${idx}][id]" value="">
                    <input type="hidden" name="items[${idx}][name]" value="">
                    <select name="items[${idx}][stock_item_id]" class="stock-item-select w-52 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                        ${buildStockOptions()}
                    </select>
                </td>
                <td class="px-5 py-4">
                    <select name="items[${idx}][grade]" class="w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="REJECT">REJECT</option>
                    </select>
                </td>
                <td class="px-5 py-4"><input name="items[${idx}][qty]" type="number" min="0.01" step="0.01" value="0" class="w-20 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none"></td>
                <td class="px-5 py-4"><input name="items[${idx}][unit]" value="KG" class="w-24 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-black text-slate-800 outline-none"></td>
                <td class="px-5 py-4">
                    <div class="flex w-32 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <span class="mr-2 text-[10px] font-black text-slate-400">Rp.</span>
                        <input name="items[${idx}][price]" type="text" inputmode="numeric" value="0" class="min-w-0 flex-1 bg-transparent text-sm font-black text-slate-800 outline-none">
                    </div>
                </td>
                ${supplierCol}
                <td class="px-5 py-4"><input name="items[${idx}][request]" value="" placeholder="Catatan..." class="w-44 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-600 outline-none"></td>
                <td class="px-5 py-4 text-center">
                    <button type="button" class="remove-item-btn p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Barang">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
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

        tbody.addEventListener('change', function (e) {
            if (e.target.classList.contains('stock-item-select')) {
                const tr = e.target.closest('tr');
                const selectedOption = e.target.options[e.target.selectedIndex];

                const unitInput = tr.querySelector('input[name$="[unit]"]');
                if (unitInput && selectedOption.dataset.unit) {
                    unitInput.value = selectedOption.dataset.unit;
                }

                const nameInput = tr.querySelector('input[name$="[name]"]');
                if (nameInput && selectedOption.dataset.name !== undefined) {
                    nameInput.value = selectedOption.dataset.name;
                }
            }
        });
    });
</script>

