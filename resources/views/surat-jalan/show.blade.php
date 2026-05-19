@extends('layouts.app', ['title' => 'Surat Jalan (Delivery)'])

@section('content')
    @php
        $delivery    = $order['delivery'] ?? [];
        $hasDelivery = ! empty($order['delivery']);
        $isAdmin     = ($currentUser['role'] ?? null) === 'ADMIN';

        $firstSupplier = collect($order['items'])->pluck('supplier')->filter(fn ($s) => $s !== '-')->first() ?? '-';
        $sjNumber      = $delivery['number'] ?? $order['delivery_suggested_number'];

        $kepada       = $delivery['kepada']    ?? $sppg['name'];
        $kdSppg       = $delivery['kd_sppg']   ?? $sppg['code'];
        $namaSppg     = $delivery['nama_sppg'] ?? $sppg['name'];
        $pjSppg       = $delivery['pj_sppg']   ?? $sppg['pic_name'];
        $whatsapp     = $delivery['whatsapp']  ?? $sppg['whatsapp'];

        $deliveryDate = $delivery['date'] ?? ($order['droping_date'] ?? now()->format('Y-m-d'));
        $deliveryTime = $delivery['time'] ?? ($order['droping_time'] ?? '');
        $driver       = $delivery['driver'] ?? '';
        $notes        = $delivery['notes']  ?? '';

        $title = $hasDelivery || ! $isAdmin ? 'Detail Surat Jalan: '.$sjNumber : 'Buat Surat Jalan: '.$order['number'];
        $proofPhoto = $order['delivery']['proof_photo'] ?? null;
    @endphp

    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-3 backdrop-blur-sm sm:p-6">
        <form method="POST" action="{{ route('surat-jalan.update', $order['id']) }}" enctype="multipart/form-data" class="mx-auto my-0 max-w-[1280px] overflow-hidden rounded-xl bg-slate-50 shadow-2xl sm:rounded-2xl">
            @csrf
            @method('PATCH')

            {{-- Header --}}
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h11v9H3zM14 10h3l3 3v2h-6zM6 18a2 2 0 104 0M16 18a2 2 0 104 0" />
                        </svg>
                    </span>
                    <h1 class="min-w-0 truncate text-base font-black tracking-tight text-slate-950 sm:text-lg">{{ $title }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    @if ($isAdmin)
                        <button type="submit" formaction="{{ route('surat-jalan.preview.form', $order['id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">Cetak PDF</button>
                    @endif
                    <a href="{{ route('surat-jalan.index') }}" class="text-2xl leading-none text-slate-400 hover:text-slate-700">×</a>
                </div>
            </header>

            <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                {{-- Top: Tujuan + Pengiriman + Foto Bukti (horizontal) --}}
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    {{-- Detail Tujuan --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-5">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                                <span class="h-3 w-0.5 rounded-full bg-blue-600"></span>
                                Detail Tujuan
                            </h2>
                            <div class="flex flex-wrap gap-1">
                                @foreach (collect($order['items'])->pluck('supplier')->unique() as $supplier)
                                    <span class="rounded border border-blue-100 bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-600">{{ $supplier }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Kepada</span>
                                <input name="kepada" value="{{ $kepada }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">KD SPPG</span>
                                    <input name="kd_sppg" value="{{ $kdSppg }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Nama SPPG</span>
                                    <input name="nama_sppg" value="{{ $namaSppg }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">PJ SPPG</span>
                                    <input name="pj_sppg" value="{{ $pjSppg }}" @readonly(! $isAdmin) placeholder="Penanggung Jawab" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">No. WhatsApp</span>
                                    <input name="whatsapp" value="{{ $whatsapp }}" @readonly(! $isAdmin) placeholder="0812..." class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                            </div>
                        </div>
                    </section>

                    {{-- Detail Pengiriman --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-4">
                        <h2 class="mb-3 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                            <span class="h-3 w-0.5 rounded-full bg-orange-500"></span>
                            Detail Pengiriman
                        </h2>
                        <div class="space-y-3">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">No Surat Jalan</span>
                                <input name="surat_jalan_no" value="{{ $sjNumber }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tanggal Kirim</span>
                                    <input name="delivery_date" type="date" value="{{ $deliveryDate }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Jam Kirim</span>
                                    <input name="delivery_time" type="time" value="{{ $deliveryTime }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Driver</span>
                                    <input name="driver" value="{{ $driver }}" @readonly(! $isAdmin) placeholder="Nama Pengirim" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                            </div>
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Keterangan</span>
                                <textarea name="notes" rows="2" @readonly(! $isAdmin) placeholder="Contoh: Barang diterima lengkap..." class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">{{ $notes }}</textarea>
                            </label>
                        </div>
                    </section>

                    {{-- Foto Bukti --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-3">
                        <h2 class="mb-3 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                            <span class="h-3 w-0.5 rounded-full bg-emerald-500"></span>
                            Foto Bukti Drop
                        </h2>
                        <div id="main-proof-container" class="{{ $proofPhoto ? 'relative h-32 w-full overflow-hidden rounded-lg bg-slate-100' : 'relative h-32 w-full rounded-lg border-2 border-dashed border-slate-200 bg-slate-50' }}">
                            <img id="main-proof-preview" src="{{ $proofPhoto ? asset('storage/'.$proofPhoto) : '#' }}" alt="Foto Bukti" class="{{ $proofPhoto ? 'h-full w-full rounded-lg object-cover' : 'hidden h-full w-full rounded-lg object-cover' }}" data-original="{{ $proofPhoto ? asset('storage/'.$proofPhoto) : '' }}">
                            <div id="main-proof-placeholder" class="{{ $proofPhoto ? 'hidden' : 'flex' }} h-full w-full items-center justify-center text-3xl text-slate-300">📷</div>
                            @if ($isAdmin)
                                <button type="button" id="btn-remove-main" class="hidden absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            @endif
                        </div>
                        @if ($isAdmin)
                            <label id="lbl-upload-main" class="mt-3 inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
                                <span id="lbl-upload-main-text">{{ $proofPhoto ? 'Ganti Foto' : 'Pilih Foto' }}</span>
                                <input type="file" name="proof_photo" accept="image/*" class="hidden" id="main-photo-input">
                            </label>
                            <span id="lbl-main-filename" class="mt-1 block w-full truncate text-[10px] font-semibold text-emerald-600"></span>
                        @endif
                    </section>
                </div>

                {{-- Daftar Barang --}}
                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-black uppercase tracking-tight text-slate-700">
                        <span class="text-lg text-slate-300">◇</span>
                        Daftar Barang Surat Jalan
                    </h2>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Nama Barang</th>
                                        <th class="w-20 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Qty</th>
                                        <th class="w-16 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Satuan</th>
                                        <th class="w-32 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Harga</th>
                                        <th class="px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier</th>
                                        <th class="px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Catatan</th>
                                        <th class="w-24 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Foto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($order['items'] as $itemIdx => $item)
                                        @php $existingPhoto = $order['delivery']['item_photos'][$itemIdx] ?? null; @endphp
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-3 py-2 text-sm font-bold text-slate-900">{{ $item['name'] }}</td>
                                            <td class="px-2 py-2"><input name="qty_actual[]" type="number" value="{{ $item['qty'] }}" @readonly(! $isAdmin) class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500"></td>
                                            <td class="px-2 py-2 text-xs font-bold uppercase text-slate-500">{{ $item['unit'] }}</td>
                                            <td class="px-2 py-2">
                                                <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 focus-within:border-blue-500">
                                                    <span class="mr-1 text-[9px] font-bold text-slate-400">Rp</span>
                                                    <input name="prices[]" type="text" inputmode="numeric" data-currency-input value="{{ $item['price'] }}" @readonly(! $isAdmin) class="min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-800 outline-none">
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <select name="suppliers[]" @disabled(! $isAdmin) class="w-full min-w-[140px] rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold uppercase text-slate-800 outline-none focus:border-blue-500">
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier }}" @selected($item['supplier'] === $supplier)>{{ $supplier }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 text-xs text-slate-500">{{ $item['request'] ?? '-' }}</td>
                                            <td class="px-2 py-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="relative h-10 w-12 shrink-0">
                                                        @if ($existingPhoto)
                                                            <img id="img-preview-{{ $itemIdx }}" src="{{ asset('storage/'.$existingPhoto) }}" alt="Foto" class="h-full w-full rounded border border-slate-200 object-cover" data-original="{{ asset('storage/'.$existingPhoto) }}">
                                                            <div id="img-placeholder-{{ $itemIdx }}" class="hidden h-full w-full items-center justify-center rounded border border-dashed border-slate-300 bg-slate-50 text-[8px] font-bold text-slate-300">FOTO</div>
                                                        @else
                                                            <img id="img-preview-{{ $itemIdx }}" src="#" alt="Preview" class="hidden h-full w-full rounded border border-slate-200 object-cover" data-original="">
                                                            <div id="img-placeholder-{{ $itemIdx }}" class="flex h-full w-full items-center justify-center rounded border border-dashed border-slate-300 bg-slate-50 text-[8px] font-bold text-slate-300">FOTO</div>
                                                        @endif
                                                        @if ($isAdmin)
                                                            <button type="button" id="btn-remove-{{ $itemIdx }}" class="hidden absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white shadow hover:bg-red-600" title="Hapus">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @if ($isAdmin)
                                                        <label id="lbl-upload-{{ $itemIdx }}" class="cursor-pointer rounded-md border border-blue-100 bg-blue-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-blue-600 transition hover:bg-blue-100">
                                                            {{ $existingPhoto ? 'Ganti' : 'Upload' }}
                                                            <input type="file" name="item_photos[{{ $itemIdx }}]" accept="image/*" class="hidden photo-input" data-idx="{{ $itemIdx }}">
                                                        </label>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <footer class="flex flex-col items-center justify-between gap-2 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:px-6">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pastikan data sudah benar sebelum disimpan.</p>
                <p class="text-center text-[10px] font-bold uppercase tracking-wide text-slate-400">@include('partials.copyright')</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('surat-jalan.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700">Batal</a>
                    @if ($isAdmin)
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white shadow-md shadow-blue-600/20 hover:bg-blue-700">Simpan Surat Jalan</button>
                    @endif
                </div>
            </footer>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const photoInputs = document.querySelectorAll('.photo-input');
            photoInputs.forEach(input => {
                input.addEventListener('change', function () {
                    const idx = this.getAttribute('data-idx');
                    const imgPreview = document.getElementById('img-preview-' + idx);
                    const imgPlaceholder = document.getElementById('img-placeholder-' + idx);
                    const btnRemove = document.getElementById('btn-remove-' + idx);
                    const lblUpload = document.getElementById('lbl-upload-' + idx);

                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imgPreview.src = e.target.result;
                            imgPreview.classList.remove('hidden');
                            imgPlaceholder.classList.add('hidden');
                            imgPlaceholder.classList.remove('flex');
                            btnRemove.classList.remove('hidden');
                            lblUpload.classList.add('hidden');
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            });

            document.querySelectorAll('button[id^="btn-remove-"]').forEach(btn => {
                if (btn.id === 'btn-remove-main') return;
                btn.addEventListener('click', function() {
                    const idx = this.id.replace('btn-remove-', '');
                    const input = document.querySelector('input[data-idx="'+idx+'"]');
                    const imgPreview = document.getElementById('img-preview-' + idx);
                    const imgPlaceholder = document.getElementById('img-placeholder-' + idx);
                    const lblUpload = document.getElementById('lbl-upload-' + idx);

                    input.value = '';
                    const originalSrc = imgPreview.getAttribute('data-original');
                    if (originalSrc) {
                        imgPreview.src = originalSrc;
                        imgPreview.classList.remove('hidden');
                        imgPlaceholder.classList.add('hidden');
                        imgPlaceholder.classList.remove('flex');
                    } else {
                        imgPreview.src = '#';
                        imgPreview.classList.add('hidden');
                        imgPlaceholder.classList.remove('hidden');
                        imgPlaceholder.classList.add('flex');
                    }
                    this.classList.add('hidden');
                    lblUpload.classList.remove('hidden');
                });
            });

            // Main proof photo
            const mainPhotoInput = document.getElementById('main-photo-input');
            if (mainPhotoInput) {
                const mainPreview = document.getElementById('main-proof-preview');
                const mainPlaceholder = document.getElementById('main-proof-placeholder');
                const btnRemoveMain = document.getElementById('btn-remove-main');
                const lblUploadMain = document.getElementById('lbl-upload-main');
                const lblUploadMainText = document.getElementById('lbl-upload-main-text');
                const filenameSpan = document.getElementById('lbl-main-filename');
                const container = document.getElementById('main-proof-container');

                mainPhotoInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            mainPreview.src = e.target.result;
                            mainPreview.classList.remove('hidden');
                            mainPlaceholder.classList.add('hidden');
                            mainPlaceholder.classList.remove('flex');
                            btnRemoveMain.classList.remove('hidden');
                            container.className = 'relative h-32 w-full overflow-hidden rounded-lg bg-slate-100';
                            lblUploadMainText.textContent = 'Ganti Foto';
                            filenameSpan.textContent = mainPhotoInput.files[0].name;
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });

                btnRemoveMain.addEventListener('click', function() {
                    mainPhotoInput.value = '';
                    filenameSpan.textContent = '';
                    const originalSrc = mainPreview.getAttribute('data-original');
                    if (originalSrc) {
                        mainPreview.src = originalSrc;
                        mainPreview.classList.remove('hidden');
                        mainPlaceholder.classList.add('hidden');
                        mainPlaceholder.classList.remove('flex');
                        container.className = 'relative h-32 w-full overflow-hidden rounded-lg bg-slate-100';
                        lblUploadMainText.textContent = 'Ganti Foto';
                    } else {
                        mainPreview.src = '#';
                        mainPreview.classList.add('hidden');
                        mainPlaceholder.classList.remove('hidden');
                        mainPlaceholder.classList.add('flex');
                        container.className = 'relative h-32 w-full rounded-lg border-2 border-dashed border-slate-200 bg-slate-50';
                        lblUploadMainText.textContent = 'Pilih Foto';
                    }
                    this.classList.add('hidden');
                });
            }
        });
    </script>
@endsection
