@extends('layouts.app', ['title' => 'Master SPPG'])

@section('content')
    <section class="mx-auto max-w-[1500px] bg-slate-50 p-2 sm:p-8">
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-100 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-600">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-600">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-md shadow-slate-200/70 sm:mb-8 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('master-sppg.index') }}" class="relative w-full lg:max-w-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
                    </svg>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cari kode, nama, lokasi, atau PIC..." class="h-12 w-full rounded-lg border border-slate-200 bg-slate-50 pl-14 pr-10 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10">
                    @if(!empty($filters['search']))
                        <a href="{{ route('master-sppg.index', ['clear' => 1]) }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 hover:text-slate-600" title="Reset">✕</a>
                    @endif
                </form>

                <a href="{{ route('master-sppg.index', ['mode' => 'create']) }}" class="inline-flex h-11 w-full items-center justify-center gap-3 rounded-lg bg-blue-600 px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 sm:h-12 sm:w-auto sm:px-8">
                    <span class="text-2xl font-light leading-none">+</span>
                    Tambah SPPG
                </a>
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md shadow-slate-200/70">
            <div class="overflow-x-auto">
                <table class="min-w-[1120px] w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-20 px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">No</th>
                            <th class="w-40 px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Kode</th>
                            <th class="px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Nama SPPG</th>
                            <th class="w-52 px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Lokasi</th>
                            <th class="w-52 px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">Nama PJ</th>
                            <th class="w-44 px-6 py-5 text-left text-xs font-black uppercase tracking-[0.2em] text-slate-500">WhatsApp</th>
                            <th class="w-36 px-6 py-5 text-center text-xs font-black uppercase tracking-[0.2em] text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if ($isCreating)
                            <tr class="bg-blue-50/40">
                                <form method="POST" action="{{ route('master-sppg.store') }}">
                                    @csrf
                                    <td class="px-6 py-4 text-center text-sm font-black text-slate-400">-</td>
                                    @include('master-sppg.partials.form-row', ['sppg' => null])
                                </form>
                            </tr>
                        @endif

                        @forelse ($sppgs as $sppg)
                            @php($isEditing = ($editSppg['id'] ?? null) === $sppg['id'])
                            <tr class="transition hover:bg-slate-50/80">
                                @if ($isEditing)
                                    <form method="POST" action="{{ route('master-sppg.update', $sppg['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <td class="px-6 py-4 text-center text-sm font-black text-slate-400">{{ ($sppgs->firstItem() ?? 1) + $loop->index }}</td>
                                        @include('master-sppg.partials.form-row', ['sppg' => $sppg])
                                    </form>
                                @else
                                    <td class="px-6 py-5 text-center text-sm font-black text-slate-400">{{ ($sppgs->firstItem() ?? 1) + $loop->index }}</td>
                                    <td class="px-6 py-5">
                                        <span class="inline-flex rounded border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-black uppercase text-blue-600">{{ $sppg['code'] }}</span>
                                    </td>
                                    <td class="px-6 py-5 text-base font-black text-slate-950">{{ $sppg['name'] }}</td>
                                    <td class="px-6 py-5 text-sm font-semibold text-slate-600">{{ $sppg['location'] ?? '-' }}</td>
                                    <td class="px-6 py-5 text-sm font-semibold text-slate-600">{{ $sppg['pic_name'] ?? '-' }}</td>
                                    <td class="px-6 py-5 text-sm font-semibold text-slate-600">{{ $sppg['whatsapp'] ?? '-' }}</td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-center gap-4">
                                            <a href="{{ route('master-sppg.index', ['edit' => $sppg['id']]) }}" class="text-slate-400 transition hover:text-blue-600" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.582 18.07 4.5 19.5l1.43-4.082z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('master-sppg.destroy', $sppg['id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 transition hover:text-rose-600" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9M9.606 18 9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.977L4.772 5.79m14.456 0H4.772m3.477 0 .384-2.306A1.125 1.125 0 0 1 9.743 2.5h4.514c.55 0 1.02.398 1.11.984l.384 2.306" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-8 py-12 text-center text-sm font-bold text-slate-400">SPPG tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sppgs->hasPages())
                <div class="border-t border-slate-100 px-4 py-4 sm:px-8">
                    {{ $sppgs->links() }}
                </div>
            @endif
        </section>
    </section>
@endsection
