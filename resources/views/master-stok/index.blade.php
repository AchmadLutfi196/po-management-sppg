@extends('layouts.app', ['title' => 'Master Stok'])

@section('content')
    <section class="mx-auto max-w-[1500px] bg-slate-50 p-8">
        <div class="mb-8 rounded-xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/70">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('master-stok.index') }}" class="relative w-full lg:max-w-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                    </svg>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cari nama barang..." class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-14 pr-4 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">
                </form>

                @if ($currentUser['role'] === 'ADMIN')
                    <a href="{{ route('master-stok.index', ['mode' => 'create']) }}" class="inline-flex h-12 items-center justify-center gap-3 rounded-lg bg-blue-600 px-8 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700">
                        <span class="text-2xl font-light leading-none">+</span>
                        Tambah Barang
                    </a>
                @endif
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-[920px] w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-24 px-8 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">No</th>
                            <th class="px-8 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Nama Barang</th>
                            <th class="w-56 px-8 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Satuan</th>
                            <th class="w-40 px-8 py-5 text-center text-xs font-black uppercase tracking-[0.2em] text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if ($isCreating && $currentUser['role'] === 'ADMIN')
                            <tr class="bg-blue-50/40">
                                <form method="POST" action="{{ route('master-stok.store') }}">
                                    @csrf
                                    <td class="px-8 py-4 text-center text-sm font-black text-slate-400">-</td>
                                    <td class="px-8 py-4">
                                        <input name="name" value="{{ old('name') }}" autofocus placeholder="Nama Barang..." class="h-11 w-full rounded border border-blue-300 bg-white px-4 text-sm font-semibold text-slate-800 outline-none focus:ring-4 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-8 py-4">
                                        <input name="unit" value="{{ old('unit') }}" placeholder="SATUAN" class="h-11 w-28 rounded border border-blue-300 bg-white px-4 text-sm font-semibold uppercase text-slate-800 outline-none focus:ring-4 focus:ring-blue-500/10">
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="flex items-center justify-center gap-3">
                                            <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition hover:bg-blue-600 hover:text-white" title="Simpan">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h11l3 3v11H5zM8 5v6h8M8 19v-5h8v5" />
                                                </svg>
                                            </button>
                                            <a href="{{ route('master-stok.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition hover:bg-slate-300" title="Batal">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
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
                                    <form method="POST" action="{{ route('master-stok.update', $item['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <td class="px-8 py-4 text-center text-sm font-black text-slate-400">{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                                        <td class="px-8 py-4">
                                            <input name="name" value="{{ old('name', $item['name']) }}" autofocus class="h-11 w-full rounded border border-blue-300 bg-white px-4 text-sm font-semibold text-slate-800 outline-none focus:ring-4 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-8 py-4">
                                            <input name="unit" value="{{ old('unit', $item['unit']) }}" class="h-11 w-28 rounded border border-blue-300 bg-white px-4 text-sm font-semibold uppercase text-slate-800 outline-none focus:ring-4 focus:ring-blue-500/10">
                                        </td>
                                        <td class="px-8 py-4">
                                            <div class="flex items-center justify-center gap-3">
                                                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition hover:bg-blue-600 hover:text-white" title="Simpan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h11l3 3v11H5zM8 5v6h8M8 19v-5h8v5" />
                                                    </svg>
                                                </button>
                                                <a href="{{ route('master-stok.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-600 transition hover:bg-slate-300" title="Batal">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </form>
                                @else
                                    <td class="px-8 py-5 text-center text-sm font-black text-slate-400">{{ ($items->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-8 py-5 text-base font-black text-slate-950">{{ $item['name'] }}</td>
                                    <td class="px-8 py-5">
                                        <span class="inline-flex rounded border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{{ $item['unit'] }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-center gap-4">
                                            @if ($currentUser['role'] === 'ADMIN')
                                                <a href="{{ route('master-stok.index', ['edit' => $item['id']]) }}" class="text-slate-400 transition hover:text-blue-600" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.582 18.07 4.5 19.5l1.43-4.082z" />
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('master-stok.destroy', $item['id']) }}" onsubmit="return confirm('Hapus barang ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 transition hover:text-rose-600" title="Hapus">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9M9.606 18 9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.977L4.772 5.79m14.456 0H4.772m3.477 0 .384-2.306A1.125 1.125 0 0 1 9.743 2.5h4.514c.55 0 1.02.398 1.11.984l.384 2.306" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('master-stok.show', $item['id']) }}" class="text-xs font-black uppercase tracking-wider text-blue-600">View</a>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-12 text-center text-sm font-bold text-slate-400">Barang tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-slate-100 px-8 py-4">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </section>
@endsection
