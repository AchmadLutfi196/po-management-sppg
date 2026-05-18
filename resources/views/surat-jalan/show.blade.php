@extends('layouts.app', ['title' => 'Surat Jalan (Delivery)'])

@section('content')
    @php
        $delivery    = $order['delivery'] ?? [];
        $hasDelivery = ! empty($order['delivery']);

        $firstSupplier = collect($order['items'])->pluck('supplier')->filter(fn ($s) => $s !== '-')->first() ?? '-';
        $sjNumber      = $delivery['number'] ?? 'SJ/'.now()->format('Y').'/'.random_int(100, 999);

        // Prioritas: data delivery yg sudah tersimpan → data SPPG dari database → fallback
        $kepada       = $delivery['kepada']    ?? $sppg['name'];          // ← nama SPPG, bukan location
        $kdSppg       = $delivery['kd_sppg']   ?? $sppg['code'];
        $namaSppg     = $delivery['nama_sppg'] ?? $sppg['name'];
        $pjSppg       = $delivery['pj_sppg']   ?? $sppg['pic_name'];
        $whatsapp     = $delivery['whatsapp']  ?? $sppg['whatsapp'];

        // Tanggal kirim default dari droping_date PO, bukan hari ini
        $deliveryDate = $delivery['date'] ?? ($order['droping_date'] ?? now()->format('Y-m-d'));
        $driver       = $delivery['driver'] ?? '';
        $notes        = $delivery['notes']  ?? '';

        $title = $hasDelivery ? 'Detail Surat Jalan: '.$sjNumber : 'Buat Surat Jalan: '.$order['number'];
    @endphp


    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/45 p-4 backdrop-blur-sm">
        <form method="POST" action="{{ route('surat-jalan.update', $order['id']) }}" enctype="multipart/form-data" class="mx-auto max-h-[calc(100vh-2rem)] max-w-[1020px] overflow-y-auto rounded-2xl bg-slate-100 shadow-2xl">
            @csrf
            @method('PATCH')

            <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-4">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h11v9H3zM14 10h3l3 3v2h-6zM6 18a2 2 0 104 0M16 18a2 2 0 104 0" />
                        </svg>
                    </span>
                    <h1 class="text-2xl font-black tracking-tight text-slate-950">{{ $title }}</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('surat-jalan.preview', $order['id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-black text-slate-600">Cetak PDF</a>
                    <a href="{{ route('surat-jalan.index') }}" class="text-3xl leading-none text-slate-400 hover:text-slate-700">×</a>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-8 px-8 py-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md shadow-slate-200/70">
                    <div class="mb-7 flex flex-wrap gap-2">
                        <p class="w-full text-xs font-black uppercase tracking-[0.16em] text-slate-400">Detail Pengiriman & Rekap Supplier</p>
                        @foreach (collect($order['items'])->pluck('supplier')->unique() as $supplier)
                            <span class="rounded border border-blue-100 bg-blue-50 px-2 py-1 text-[10px] font-black uppercase text-blue-600">Unit Pelaksana: {{ $supplier }}</span>
                        @endforeach
                    </div>

                    <div class="space-y-6">
                        <label class="block">
                            <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Kepada</span>
                            <input name="kepada" value="{{ $kepada }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">KD SPPG</span>
                                <input name="kd_sppg" value="{{ $kdSppg }}" placeholder="Kode SPPG" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Nama SPPG</span>
                                <input name="nama_sppg" value="{{ $namaSppg }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">PJ SPPG</span>
                                <input name="pj_sppg" value="{{ $pjSppg }}" placeholder="Penanggung Jawab" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">No. WhatsApp</span>
                                <input name="whatsapp" value="{{ $whatsapp }}" placeholder="0812..." class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                        </div>
                        <label class="block">
                            <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">No Surat Jalan</span>
                            <input name="surat_jalan_no" value="{{ $sjNumber }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Tanggal Kirim</span>
                                <input name="delivery_date" type="date" value="{{ $deliveryDate }}" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Nama Driver/Kurir</span>
                                <input name="driver" value="{{ $driver }}" placeholder="Nama Pengirim" class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                            </label>
                        </div>
                        <label class="block">
                            <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Keterangan Barang Terkirim</span>
                            <textarea name="notes" rows="4" placeholder="Contoh: Barang telah diterima lengkap sesuai PO..." class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">{{ $notes }}</textarea>
                        </label>
                    </div>
                </section>

                <section class="space-y-7">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-md shadow-slate-200/70">
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.16em] text-slate-400">Upload Foto Bukti/Drop Barang</p>
                        @php
                            $proofPhoto = $order['delivery']['proof_photo'] ?? null;
                        @endphp
                        
                        <div id="main-proof-container" class="{{ $proofPhoto ? 'mx-auto h-56 max-w-sm overflow-hidden rounded-xl bg-gradient-to-br from-slate-200 via-blue-100 to-emerald-100 p-2 relative group' : 'mx-auto relative group h-24 w-24' }}">
                            <img id="main-proof-preview" src="{{ $proofPhoto ? asset('storage/'.$proofPhoto) : '#' }}" alt="Foto Bukti" class="{{ $proofPhoto ? 'h-full w-full object-cover rounded-lg shadow-sm' : 'hidden h-full w-full object-cover rounded-2xl shadow-sm' }}" data-original="{{ $proofPhoto ? asset('storage/'.$proofPhoto) : '' }}">
                            
                            @if (!$proofPhoto)
                                <div id="main-proof-placeholder" class="flex h-full w-full items-center justify-center rounded-2xl bg-slate-100 text-4xl text-slate-300">□</div>
                            @else
                                <div id="main-proof-placeholder" class="hidden h-full w-full items-center justify-center rounded-xl bg-slate-100 text-4xl text-slate-300">□</div>
                            @endif
                            
                            <button type="button" id="btn-remove-main" class="hidden absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 focus:outline-none" title="Batal Pilih Foto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        
                        <div id="main-proof-texts" class="{{ $proofPhoto ? 'hidden' : 'block' }}">
                            <p class="mt-4 text-base font-black text-slate-900">Belum ada foto bukti</p>
                            <p class="mt-2 text-xs font-semibold text-slate-500">Gunakan foto dokumen fisik SJ atau foto barang saat di drop.</p>
                        </div>

                        <label id="lbl-upload-main" class="{{ $proofPhoto ? 'mt-4 w-full rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-500 inline-flex cursor-pointer items-center justify-center transition hover:bg-rose-100' : 'mt-5 inline-flex cursor-pointer items-center justify-center rounded-lg bg-blue-600 px-7 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700' }}">
                            <span id="lbl-upload-main-text">{{ $proofPhoto ? 'Hapus & Ganti Foto' : 'Ambil / Pilih Foto' }}</span>
                            <input type="file" name="proof_photo" accept="image/*" class="hidden" id="main-photo-input">
                        </label>
                        <span id="lbl-main-filename" class="mt-2 block w-full truncate text-xs font-semibold text-emerald-600"></span>
                    </div>

                    <div class="rounded-2xl bg-emerald-600 p-7 text-white shadow-2xl shadow-emerald-500/20">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 text-xl font-black">OK</span>
                            <div>
                                <p class="text-lg font-black uppercase">Siap Kirim</p>
                                <p class="text-xs font-black text-emerald-100">Status PO akan berubah menjadi dikirim otomatis.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="mx-8 mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
                <div class="border-b border-slate-100 px-7 py-5">
                    <h2 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900">Daftar Barang Dalam Surat Jalan</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1080px] divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Nama Barang</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Qty Aktual</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Satuan</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Harga Satuan</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Supplier</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Catatan / Request</th>
                                <th class="px-7 py-4 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Foto Barang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order['items'] as $itemIdx => $item)
                                @php $existingPhoto = $order['delivery']['item_photos'][$itemIdx] ?? null; @endphp
                                <tr>
                                    <td class="px-7 py-5 text-base font-black text-slate-900">{{ $item['name'] }}</td>
                                    <td class="px-7 py-5"><input name="qty_actual[]" type="number" value="{{ $item['qty'] }}" class="w-24 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-800 outline-none focus:border-blue-500"></td>
                                    <td class="px-7 py-5 text-xs font-black uppercase text-slate-500">{{ $item['unit'] }}</td>
                                    <td class="px-7 py-5">
                                        <div class="flex w-36 items-center rounded-lg border border-slate-200 bg-white px-3 py-2 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10">
                                            <span class="mr-2 text-xs font-black text-slate-400">Rp.</span>
                                            <input name="prices[]" type="text" inputmode="numeric" data-currency-input value="{{ $item['price'] }}" class="min-w-0 flex-1 bg-transparent text-sm font-black text-slate-800 outline-none">
                                        </div>
                                    </td>
                                    <td class="px-7 py-5">
                                        <select name="suppliers[]" class="w-52 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black uppercase text-slate-800 outline-none focus:border-blue-500">
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier }}" @selected($item['supplier'] === $supplier)>{{ $supplier }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-7 py-5 text-sm font-semibold text-slate-500">{{ $item['request'] ?? '-' }}</td>
                                    <td class="px-7 py-5">
                                        <div class="flex flex-col items-start gap-2">
                                            <div class="relative h-14 w-16 shrink-0">
                                                @if ($existingPhoto)
                                                    <img id="img-preview-{{ $itemIdx }}" src="{{ asset('storage/'.$existingPhoto) }}" alt="Foto" class="h-full w-full rounded-lg object-cover border border-slate-200 shadow-sm" data-original="{{ asset('storage/'.$existingPhoto) }}">
                                                    <div id="img-placeholder-{{ $itemIdx }}" class="hidden h-full w-full items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-[10px] font-black text-slate-300">FOTO</div>
                                                @else
                                                    <img id="img-preview-{{ $itemIdx }}" src="#" alt="Preview" class="hidden h-full w-full rounded-lg object-cover border border-slate-200 shadow-sm" data-original="">
                                                    <div id="img-placeholder-{{ $itemIdx }}" class="flex h-full w-full items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-[10px] font-black text-slate-300">FOTO</div>
                                                @endif
                                                <button type="button" id="btn-remove-{{ $itemIdx }}" class="hidden absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white shadow-md hover:bg-red-600 focus:outline-none" title="Batal Pilih Foto">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                </button>
                                            </div>
                                            <label id="lbl-upload-{{ $itemIdx }}" class="inline-flex cursor-pointer items-center justify-center gap-1 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-blue-600 transition hover:border-blue-200 hover:bg-blue-100">
                                                {{ $existingPhoto ? 'Ganti' : 'Upload' }}
                                                <input type="file" name="item_photos[{{ $itemIdx }}]" accept="image/*" class="hidden photo-input" data-idx="{{ $itemIdx }}">
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach


                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="sticky bottom-0 flex items-center justify-between border-t border-slate-200 bg-white px-8 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Pastikan data barang sudah benar sebelum disimpan.</p>
                <div class="flex items-center gap-6">
                    <a href="{{ route('surat-jalan.index') }}" class="text-sm font-black text-slate-700">Batal</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-9 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">Simpan & Update Status Pengiriman</button>
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
                btn.addEventListener('click', function() {
                    const idx = this.id.replace('btn-remove-', '');
                    const input = document.querySelector('input[data-idx="'+idx+'"]');
                    const imgPreview = document.getElementById('img-preview-' + idx);
                    const imgPlaceholder = document.getElementById('img-placeholder-' + idx);
                    const lblUpload = document.getElementById('lbl-upload-' + idx);

                    input.value = ''; // clear input file

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

            // Handle main proof photo upload
            const mainPhotoInput = document.getElementById('main-photo-input');
            if (mainPhotoInput) {
                const mainPreview = document.getElementById('main-proof-preview');
                const mainPlaceholder = document.getElementById('main-proof-placeholder');
                const btnRemoveMain = document.getElementById('btn-remove-main');
                const lblUploadMain = document.getElementById('lbl-upload-main');
                const mainTexts = document.getElementById('main-proof-texts');
                const container = document.getElementById('main-proof-container');
                const filenameSpan = document.getElementById('lbl-main-filename');

                mainPhotoInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            mainPreview.src = e.target.result;
                            mainPreview.classList.remove('hidden');
                            mainPlaceholder.classList.add('hidden');
                            mainPlaceholder.classList.remove('flex');
                            btnRemoveMain.classList.remove('hidden');
                            lblUploadMain.classList.add('hidden');
                            
                            if(mainTexts) mainTexts.classList.add('hidden');
                            filenameSpan.textContent = mainPhotoInput.files[0].name;

                            // Adjust container style to match active state
                            container.className = 'mx-auto h-56 max-w-sm overflow-hidden rounded-xl bg-gradient-to-br from-slate-200 via-blue-100 to-emerald-100 p-2 relative group';
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
                        container.className = 'mx-auto h-56 max-w-sm overflow-hidden rounded-xl bg-gradient-to-br from-slate-200 via-blue-100 to-emerald-100 p-2 relative group';
                    } else {
                        mainPreview.src = '#';
                        mainPreview.classList.add('hidden');
                        mainPlaceholder.classList.remove('hidden');
                        mainPlaceholder.classList.add('flex');
                        container.className = 'mx-auto relative group h-24 w-24';
                        if(mainTexts) mainTexts.classList.remove('hidden');
                    }

                    this.classList.add('hidden');
                    lblUploadMain.classList.remove('hidden');
                });
            }
        });
    </script>
@endsection
