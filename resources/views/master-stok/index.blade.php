@extends('layouts.app', ['title' => 'Master Stok'])

@section('content')
    <section class="mx-auto max-w-[1500px] bg-slate-50 p-2 sm:p-8">
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-md shadow-slate-200/70 sm:mb-8 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('master-stok.index') }}" class="relative w-full lg:max-w-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                    </svg>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cari nama barang..." class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-14 pr-4 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">
                </form>

                @if ($currentUser['role'] === 'ADMIN')
                    <a href="{{ route('master-stok.index', ['mode' => 'create']) }}" class="inline-flex h-11 w-full items-center justify-center gap-3 rounded-lg bg-blue-600 px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 sm:h-12 sm:w-auto sm:px-8">
                        <span class="text-2xl font-light leading-none">+</span>
                        Tambah Barang
                    </a>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-4 py-4 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">No</th>
                            <th class="w-16 px-4 py-4 text-center text-xs font-black uppercase tracking-[0.2em] text-slate-500">Foto</th>
                            <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Nama Barang</th>
                            <th class="w-28 px-4 py-4 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Satuan</th>
                            @if ($currentUser['role'] === 'ADMIN')
                                <th class="w-28 px-4 py-4 text-right text-xs font-black uppercase tracking-[0.2em] text-slate-500">Qty</th>
                                <th class="w-36 px-4 py-4 text-right text-xs font-black uppercase tracking-[0.2em] text-slate-500">HET</th>
                            @endif
                            <th class="w-36 px-4 py-4 text-center text-xs font-black uppercase tracking-[0.2em] text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if ($isCreating && $currentUser['role'] === 'ADMIN')
                            <tr class="bg-blue-50/40">
                                <form method="POST" action="{{ route('master-stok.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <td class="px-4 py-3 text-center text-sm font-black text-slate-400">-</td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="cursor-pointer" id="create-img-label">
                                            <span id="create-img-preview" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-dashed border-blue-300 bg-blue-50 text-xs text-blue-500">📷</span>
                                            <input type="file" name="image" accept="image/*" class="hidden" onchange="previewImg(this,'create-img-preview')">
                                        </label>
                                    </td>
                                    <td class="px-6 py-3">
                                        <input name="name" value="{{ old('name') }}" autofocus placeholder="Nama Barang..." class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="unit" value="{{ old('unit') }}" placeholder="KG" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold uppercase text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="qty" type="number" min="0" step="0.01" value="{{ old('qty', 0) }}" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="het" type="number" min="0" value="{{ old('het', 0) }}" placeholder="0" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition hover:bg-blue-600 hover:text-white" title="Simpan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5h11l3 3v11H5zM8 5v6h8M8 19v-5h8v5" /></svg>
                                            </button>
                                            <a href="{{ route('master-stok.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition hover:bg-slate-300" title="Batal">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                            </a>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        @endif

                        @forelse ($items as $item)
                            @php($isEditing = ($editItem['id'] ?? null) === $item['id'] && $currentUser['role'] === 'ADMIN')
                            <tr class="transition hover:bg-slate-50/80">
                                @if ($isEditing)
                                    <form method="POST" action="{{ route('master-stok.update', $item['id']) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <td class="px-4 py-3 text-center text-sm font-black text-slate-400">{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <label class="cursor-pointer" id="edit-img-label-{{ $item['id'] }}">
                                                @if (!empty($item['image']))
                                                    <img id="edit-img-preview-{{ $item['id'] }}" src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['name'] }}" class="h-10 w-10 rounded-lg object-cover border border-blue-300">
                                                @else
                                                    <span id="edit-img-preview-{{ $item['id'] }}" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-dashed border-blue-300 bg-blue-50 text-xs text-blue-500">📷</span>
                                                @endif
                                                <input type="file" name="image" accept="image/*" class="hidden" onchange="previewImg(this,'edit-img-preview-{{ $item['id'] }}')">
                                            </label>
                                        </td>
                                        <td class="px-6 py-3">
                                            <input name="name" value="{{ old('name', $item['name']) }}" autofocus class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input name="unit" value="{{ old('unit', $item['unit']) }}" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold uppercase text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input name="qty" type="number" min="0" step="0.01" value="{{ old('qty', $item['qty']) }}" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input name="het" type="number" min="0" value="{{ old('het', $item['het']) }}" class="h-10 w-full rounded border border-blue-300 bg-white px-3 text-sm font-semibold text-slate-800 outline-none focus:ring-2 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition hover:bg-blue-600 hover:text-white" title="Simpan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5h11l3 3v11H5zM8 5v6h8M8 19v-5h8v5" /></svg>
                                                </button>
                                                <a href="{{ route('master-stok.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition hover:bg-slate-300" title="Batal">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </form>
                                @else
                                    <td class="px-4 py-4 text-center text-sm font-black text-slate-400">{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-4 py-4 text-center">
                                        @if (!empty($item['image']))
                                            <button type="button" onclick="openImageModal('{{ asset('storage/'.$item['image']) }}', '{{ $item['name'] }}')" class="mx-auto flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-blue-50 hover:text-blue-600" title="Lihat foto">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-base font-black text-slate-950">{{ $item['name'] }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{{ $item['unit'] }}</span>
                                    </td>
                                    @if ($currentUser['role'] === 'ADMIN')
                                        <td class="px-4 py-4 text-right text-sm font-bold text-slate-700">
                                            {{ $item['qty'] > 0 ? number_format($item['qty'], 0, ',', '.') : '—' }}
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm font-bold text-slate-700">
                                            {{ $item['het'] > 0 ? 'Rp '.number_format($item['het'], 0, ',', '.') : '—' }}
                                        </td>
                                    @endif
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-3">
                                            @if ($currentUser['role'] === 'ADMIN')
                                                <a href="{{ route('master-stok.index', ['edit' => $item['id']]) }}" class="text-slate-400 transition hover:text-blue-600" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.582 18.07 4.5 19.5l1.43-4.082z" /></svg>
                                                </a>
                                                <form method="POST" action="{{ route('master-stok.destroy', $item['id']) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 transition hover:text-rose-600" title="Hapus">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9M9.606 18 9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.977L4.772 5.79m14.456 0H4.772m3.477 0 .384-2.306A1.125 1.125 0 0 1 9.743 2.5h4.514c.55 0 1.02.398 1.11.984l.384 2.306" /></svg>
                                                    </button>
                                                </form>
                                            @else
                                                @if (!empty($item['image']))
                                                    <button type="button" onclick="openImageModal('{{ asset('storage/'.$item['image']) }}', '{{ $item['name'] }}')" class="text-slate-400 transition hover:text-blue-600" title="Lihat foto">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $currentUser['role'] === 'ADMIN' ? 7 : 5 }}" class="px-8 py-12 text-center text-sm font-bold text-slate-400">Barang tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-slate-100 px-4 py-4 sm:px-8">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </section>

    {{-- Modal Foto --}}
    <div id="image-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/80 p-4 backdrop-blur-sm" onclick="closeImageModal()">
        <div class="relative max-h-[90vh] max-w-2xl" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg hover:bg-slate-100">&times;</button>
            <img id="image-modal-img" src="" alt="" class="max-h-[85vh] w-auto rounded-xl object-contain shadow-2xl">
            <p id="image-modal-name" class="mt-3 text-center text-sm font-bold text-white"></p>
        </div>
    </div>

    <script>
        function openImageModal(src, name) {
            const modal = document.getElementById('image-modal');
            document.getElementById('image-modal-img').src = src;
            document.getElementById('image-modal-name').textContent = name;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeImageModal() {
            const modal = document.getElementById('image-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function previewImg(input, targetId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const target = document.getElementById(targetId);
                    if (target.tagName === 'IMG') {
                        target.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.id = targetId;
                        img.src = e.target.result;
                        img.className = 'h-10 w-10 rounded-lg object-cover border border-blue-300';
                        target.replaceWith(img);
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageModal();
        });
    </script>
@endsection
