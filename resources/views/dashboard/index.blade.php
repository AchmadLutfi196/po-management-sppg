@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    @php
        $summary = [
            ['label' => 'TOTAL PO', 'value' => $stats['total_po'].' Dokumen', 'class' => 'text-slate-900'],
            ['label' => 'MENUNGGU (VALID)', 'value' => $stats['valid'].' Dokumen', 'class' => 'text-orange-600'],
            ['label' => 'SELESAI/INVOICE', 'value' => ($stats['completed'] + $stats['invoiced']).' Dokumen', 'class' => 'text-emerald-600'],
        ];

        $financeCards = [
            [
                'label' => 'TOTAL NILAI PEMBELANJAAN',
                'value' => 'Rp '.number_format($stats['total_value'], 0, ',', '.'),
                'icon' => 'cart',
                'box' => 'bg-white text-slate-900 border-slate-200',
                'iconBox' => 'bg-slate-50 text-slate-400',
                'caption' => null,
            ],
            [
                'label' => 'ESTIMASI BELUM TAGIH',
                'value' => 'Rp '.number_format($stats['estimated_unbilled'], 0, ',', '.'),
                'icon' => 'clock',
                'box' => 'bg-white text-slate-900 border-slate-200',
                'iconBox' => 'bg-amber-50 text-amber-500',
                'caption' => null,
                'labelClass' => 'text-amber-600',
            ],
            [
                'label' => 'TOTAL BELUM DIBAYAR',
                'value' => 'Rp '.number_format($stats['unpaid'], 0, ',', '.'),
                'icon' => 'alert',
                'box' => 'bg-rose-600 text-white border-rose-600 shadow-2xl shadow-rose-500/25',
                'iconBox' => 'bg-white/20 text-white',
                'caption' => 'INVOICE: Rp '.number_format($stats['invoice_unpaid'], 0, ',', '.').' / PIUTANG: Rp '.number_format($stats['debt_unpaid'], 0, ',', '.'),
                'labelClass' => 'text-rose-100',
            ],
            [
                'label' => 'TOTAL CAIR (LUNAS)',
                'value' => 'Rp '.number_format($stats['paid'], 0, ',', '.'),
                'icon' => 'check-circle',
                'box' => 'bg-emerald-600 text-white border-emerald-600 shadow-2xl shadow-emerald-500/25',
                'iconBox' => 'bg-white/20 text-white',
                'caption' => 'INVOICE: Rp '.number_format($stats['invoice_paid'], 0, ',', '.').' / PIUTANG: Rp '.number_format($stats['debt_paid'], 0, ',', '.'),
                'labelClass' => 'text-emerald-100',
            ],
        ];

        $chartData = [
            ['label' => 'Valid', 'value' => $stats['valid'], 'color' => 'bg-orange-500'],
            ['label' => 'Proses', 'value' => $stats['processing'], 'color' => 'bg-blue-500'],
            ['label' => 'Selesai', 'value' => $stats['completed'], 'color' => 'bg-emerald-500'],
            ['label' => 'Tertagih', 'value' => $stats['invoiced'], 'color' => 'bg-indigo-500'],
        ];
        $poNumberTones = [
            'bg-violet-50 text-violet-600',
            'bg-emerald-50 text-emerald-600',
            'bg-blue-50 text-blue-600',
            'bg-orange-50 text-orange-600',
            'bg-rose-50 text-rose-600',
            'bg-cyan-50 text-cyan-600',
            'bg-indigo-50 text-indigo-600',
            'bg-amber-50 text-amber-600',
        ];
        $maxChart = max(1, collect($chartData)->max('value'));
    @endphp

    <section class="mx-auto max-w-[1200px] space-y-7">
        <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-start">
            <div class="pt-1">
                <h2 class="text-2xl font-black tracking-tight text-slate-950">Ringkasan Operasional</h2>
                <p class="mt-2 text-sm font-medium text-slate-500">Pantau status pengadaan dan finansial secara real-time.</p>
            </div>

            <div class="flex w-full overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-md shadow-slate-200/70 xl:w-auto">
                @foreach ($summary as $item)
                    <div class="min-w-36 flex-1 border-r border-slate-100 px-4 py-3 last:border-r-0 xl:min-w-36">
                        <p class="text-[9px] font-black uppercase tracking-[0.22em] text-slate-400">{{ $item['label'] }}</p>
                        <p class="mt-1 whitespace-nowrap text-sm font-black {{ $item['class'] }}">{{ $item['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($financeCards as $card)
                <article class="{{ $card['box'] }} min-h-36 rounded-2xl border p-5 shadow-md shadow-slate-200/70 sm:min-h-40 sm:rounded-3xl sm:p-6">
                    <div class="{{ $card['iconBox'] }} mb-5 flex h-10 w-10 items-center justify-center rounded-xl">
                        @include('partials.icon', ['name' => $card['icon'], 'class' => 'h-5 w-5'])
                    </div>
                    <p class="{{ $card['labelClass'] ?? 'text-slate-400' }} text-[10px] font-black uppercase tracking-[0.2em]">{{ $card['label'] }}</p>
                    <p class="mt-5 break-words text-xl font-black tracking-tight sm:mt-6 sm:text-2xl">{{ $card['value'] }}</p>
                    @if ($card['caption'])
                        <p class="mt-4 text-[9px] font-black uppercase tracking-[0.18em] opacity-80">{{ $card['caption'] }}</p>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/70 sm:rounded-3xl sm:p-8 xl:col-span-8">
                <div class="mb-7 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <h3 class="text-sm font-black uppercase tracking-[0.18em] text-slate-950">Status Dokumen PO</h3>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($chartData as $item)
                            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-500">
                                <span class="{{ $item['color'] }} h-2 w-2 rounded-full"></span>
                                {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="grid h-[310px] grid-cols-[36px_1fr] gap-4">
                    <div class="flex flex-col justify-between pb-8 pt-1 text-[10px] font-black text-slate-400">
                        @for ($tick = $maxChart; $tick >= 0; $tick--)
                            <span>{{ $tick }}</span>
                        @endfor
                    </div>
                    <div class="relative">
                        <div class="absolute inset-x-0 top-0 bottom-8 flex flex-col justify-between">
                            @for ($tick = 0; $tick <= $maxChart; $tick++)
                                <span class="border-t border-dashed border-slate-100"></span>
                            @endfor
                        </div>
                        <div class="relative z-10 grid h-full grid-cols-4 items-end gap-3 pb-8 sm:gap-8">
                            @foreach ($chartData as $item)
                                <div class="flex h-full flex-col items-center justify-end gap-3">
                                    <div class="{{ $item['color'] }} w-10 rounded-t-lg shadow-sm" style="height: {{ $item['value'] === 0 ? 2 : max(22, ($item['value'] / $maxChart) * 250) }}px"></div>
                                    <span class="text-[10px] font-black text-slate-400">{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-200/70 sm:rounded-3xl sm:p-6 xl:col-span-4">
                <h3 class="mb-6 text-sm font-black uppercase tracking-[0.18em] text-slate-950">Aktivitas Terakhir</h3>
                <div class="space-y-5">
                    @foreach ($orders->take(4) as $order)
                        @php
                            $baseNumber = is_string($order['number'] ?? null) ? (int) strtok($order['number'], '/') : 0;
                            $tone       = $baseNumber > 0
                                ? $poNumberTones[($baseNumber - 1) % count($poNumberTones)]
                                : 'bg-orange-50 text-orange-600';
                        @endphp
                        <a href="{{ route('purchase-orders.show', $order['id']) }}" class="group flex gap-4">
                            <span class="relative flex flex-col items-center">
                                <span class="{{ $tone }} flex h-10 w-10 items-center justify-center rounded-2xl transition group-hover:scale-105">
                                    @include('partials.icon', ['name' => 'file-text', 'class' => 'h-5 w-5'])
                                </span>
                                @unless ($loop->last)
                                    <span class="mt-1 h-9 w-px bg-slate-100"></span>
                                @endunless
                            </span>
                            <span class="min-w-0 flex-1 pt-0.5">
                                <span class="block truncate text-xs font-black text-slate-950">{{ $order['number'] }}</span>
                                <span class="mt-1 block truncate text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $order['sppg'] }} / {{ $order['date'] }}</span>
                                <span class="mt-2 inline-flex rounded-full border border-slate-100 bg-slate-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] text-slate-500">{{ $order['status'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>
@endsection
