@extends('layouts.app', ['title' => 'Surat Jalan (Delivery)'])

@section('content')
    @php
        $delivery    = $order['delivery'] ?? [];
        $hasDelivery = ! empty($order['delivery']);
        $isAdmin     = ($currentUser['role'] ?? null) === 'ADMIN';
        $canEditItemValues = $isAdmin && ! $hasDelivery;

        $firstSupplier = collect($order['items'])->pluck('supplier')->filter(fn ($s) => $s !== '-')->first() ?? '-';
        $sjNumber      = old('surat_jalan_no', $delivery['number'] ?? $order['delivery_suggested_number']);

        $kepada       = old('kepada', $delivery['kepada'] ?? $sppg['name']);
        $kdSppg       = old('kd_sppg', $delivery['kd_sppg'] ?? $sppg['code']);
        $namaSppg     = old('nama_sppg', $delivery['nama_sppg'] ?? $sppg['name']);
        $pjSppg       = old('pj_sppg', $delivery['pj_sppg'] ?? $sppg['pic_name']);
        $whatsapp     = old('whatsapp', $delivery['whatsapp'] ?? $sppg['whatsapp']);

        $deliveryDate = old('delivery_date', $order['droping_date'] ?? now()->format('Y-m-d'));
        $deliveryTime = old('delivery_time', $order['droping_time'] ?? '');
        $receivedDate = $delivery['date'] ?? null;
        $receivedTime = $delivery['time'] ?? null;
        $driver       = old('driver', $delivery['driver'] ?? '');
        $notes        = old('notes', $delivery['notes'] ?? '');

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
                    @else
                        <a href="{{ route('surat-jalan.preview', $order['id']) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100">Cetak PDF</a>
                    @endif
                    <a href="{{ route('surat-jalan.index') }}" class="text-2xl leading-none text-slate-400 hover:text-slate-700">×</a>
                </div>
            </header>

            <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                {{-- Top: Tujuan + Pengiriman + Foto Bukti (horizontal) --}}
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    {{-- Detail Tujuan --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:col-span-5">
                        <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
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
                        <div class="space-y-2">
                            <label class="block">
                                <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Kepada</span>
                                <input name="kepada" value="{{ $kepada }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">KD SPPG</span>
                                    <input name="kd_sppg" value="{{ $kdSppg }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Nama SPPG</span>
                                    <input name="nama_sppg" value="{{ $namaSppg }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">PJ SPPG</span>
                                    <input name="pj_sppg" value="{{ $pjSppg }}" @readonly(! $isAdmin) placeholder="Penanggung Jawab" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">No. WhatsApp</span>
                                    <input name="whatsapp" value="{{ $whatsapp }}" @readonly(! $isAdmin) placeholder="0812..." class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                            </div>
                        </div>
                    </section>

                    {{-- Detail Pengiriman --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:col-span-4">
                        <h2 class="mb-2.5 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                            <span class="h-3 w-0.5 rounded-full bg-orange-500"></span>
                            Detail Pengiriman
                        </h2>
                        <div class="space-y-2">
                            <label class="block">
                                <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">No Surat Jalan</span>
                                <input name="surat_jalan_no" value="{{ $sjNumber }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Tanggal Kirim</span>
                                    <input name="delivery_date" type="date" value="{{ $deliveryDate }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Jam Kirim</span>
                                    <input name="delivery_time" type="time" value="{{ $deliveryTime }}" @readonly(! $isAdmin) class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Driver</span>
                                    <input name="driver" value="{{ $driver }}" @readonly(! $isAdmin) placeholder="Nama Pengirim" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                                <label class="block">
                                    <span class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Keterangan</span>
                                    <input name="notes" value="{{ $notes }}" @readonly(! $isAdmin) placeholder="Contoh: Aman, Lengkap..." class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                </label>
                            </div>
                            @if ($receivedDate)
                                <p class="mt-0.5 text-[9px] italic font-bold text-green-600">Tgl Diterima: {{ \Illuminate\Support\Carbon::parse($receivedDate)->translatedFormat('d/m/Y') }}@if ($receivedTime), {{ $receivedTime }}@endif</p>
                            @endif
                        </div>
                    </section>

                    {{-- Foto Bukti --}}
                    <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:col-span-3">
                        <h2 class="mb-2.5 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                            <span class="h-3 w-0.5 rounded-full bg-emerald-500"></span>
                            Foto Bukti Drop
                        </h2>
                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-2">
                            <a
                                id="main-proof-preview-link"
                                href="{{ $proofPhoto ? asset('storage/'.$proofPhoto) : '#' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="{{ $proofPhoto ? 'inline-flex' : 'hidden' }} w-full items-center justify-center gap-2 rounded-lg border border-blue-100 bg-white px-3 py-2 text-xs font-black uppercase tracking-wide text-blue-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50"
                            >
                                @include('partials.icon', ['name' => 'eye', 'class' => 'h-4 w-4'])
                                Preview Foto
                            </a>
                            <div id="main-proof-empty" class="{{ $proofPhoto ? 'hidden' : 'flex' }} h-9 items-center justify-center rounded-lg text-xs font-bold text-slate-400">
                                Belum ada foto bukti
                            </div>
                        </div>
                        @if ($isAdmin)
                            <label id="lbl-upload-main" class="mt-2 inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700">
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
                                        <th class="w-44 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Catatan</th>
                                        <th class="w-20 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Qty</th>
                                        <th class="w-16 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Satuan</th>
                                        <th class="w-32 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Harga</th>
                                        <th class="px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Supplier</th>
                                        <th class="w-24 px-2 py-2.5 text-left text-[10px] font-bold uppercase tracking-wide text-slate-400">Foto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($order['items'] as $itemIdx => $item)
                                        @php $existingPhoto = $order['delivery']['item_photos'][$itemIdx] ?? null; @endphp
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-3 py-2 text-sm font-bold text-slate-900">{{ $item['name'] }}</td>
                                            <td class="px-2 py-2 text-xs text-slate-500">{{ $item['request'] ?? '-' }}</td>
                                            <td class="px-2 py-2"><input name="qty_actual[]" type="number" value="{{ old("qty_actual.$itemIdx", $item['qty']) }}" @readonly(! $canEditItemValues) class="w-full rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs font-semibold text-slate-800 outline-none focus:border-blue-500 disabled:cursor-not-allowed disabled:text-slate-400"></td>
                                            <td class="px-2 py-2 text-xs font-bold uppercase text-slate-500">{{ $item['unit'] }}</td>
                                            <td class="px-2 py-2">
                                                <div class="flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 focus-within:border-blue-500">
                                                    <span class="mr-1 text-[9px] font-bold text-slate-400">Rp</span>
                                                    <input name="prices[]" type="text" inputmode="numeric" data-currency-input value="{{ old("prices.$itemIdx", $item['price']) }}" @readonly(! $canEditItemValues) class="min-w-0 flex-1 bg-transparent text-xs font-semibold text-slate-800 outline-none disabled:cursor-not-allowed disabled:text-slate-400">
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="hidden" name="suppliers[]" value="{{ $item['supplier'] }}">
                                                <select @disabled(true) class="w-full min-w-[140px] cursor-not-allowed rounded-md border border-slate-200 bg-slate-100 px-2 py-1.5 text-xs font-semibold uppercase text-slate-500 outline-none">
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier }}" @selected($item['supplier'] === $supplier)>{{ $supplier }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <div class="flex items-center gap-2">
                                                    <a
                                                        id="photo-preview-link-{{ $itemIdx }}"
                                                        href="{{ $existingPhoto ? asset('storage/'.$existingPhoto) : '#' }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="{{ $existingPhoto ? 'inline-flex' : 'hidden' }} h-8 w-8 shrink-0 items-center justify-center rounded-md border border-blue-100 bg-blue-50 text-blue-600 transition hover:bg-blue-100"
                                                        title="Preview foto"
                                                        aria-label="Preview foto {{ $item['name'] }}"
                                                    >
                                                        @include('partials.icon', ['name' => 'eye', 'class' => 'h-4 w-4'])
                                                    </a>
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
            document.querySelectorAll('.photo-input').forEach((input) => {
                input.addEventListener('change', function () {
                    const idx = this.getAttribute('data-idx');
                    const previewLink = document.getElementById('photo-preview-link-' + idx);
                    const uploadLabel = document.getElementById('lbl-upload-' + idx);

                    if (this.files && this.files[0] && previewLink) {
                        previewLink.href = URL.createObjectURL(this.files[0]);
                        previewLink.classList.remove('hidden');
                        previewLink.classList.add('inline-flex');

                        if (uploadLabel) {
                            uploadLabel.childNodes[0].textContent = 'Ganti';
                        }
                    }
                });
            });

            const mainPhotoInput = document.getElementById('main-photo-input');
            if (mainPhotoInput) {
                const previewLink = document.getElementById('main-proof-preview-link');
                const emptyState = document.getElementById('main-proof-empty');
                const uploadLabelText = document.getElementById('lbl-upload-main-text');
                const filenameSpan = document.getElementById('lbl-main-filename');

                mainPhotoInput.addEventListener('change', function () {
                    if (this.files && this.files[0] && previewLink) {
                        previewLink.href = URL.createObjectURL(this.files[0]);
                        previewLink.classList.remove('hidden');
                        previewLink.classList.add('inline-flex');
                        emptyState?.classList.add('hidden');

                        if (uploadLabelText) {
                            uploadLabelText.textContent = 'Ganti Foto';
                        }

                        if (filenameSpan) {
                            filenameSpan.textContent = this.files[0].name;
                        }
                    }
                });
            }
        });
    </script>
@endsection
