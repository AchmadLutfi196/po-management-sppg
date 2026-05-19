<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Preview Surat Jalan - {{ $order['delivery']['number'] ?? $order['number'] }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @page {
                size: A4;
                margin: 12mm;
            }

            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased print:bg-white">
        @php
            $delivery = $order['delivery'] ?? [];
            $sjNumber = $delivery['number'] ?? $order['delivery_suggested_number'];
            $deliveryDate = $delivery['date'] ?? now()->format('Y-m-d');
            $driver = $delivery['driver'] ?? 'Nama Pengirim';
            $kepada = $delivery['kepada'] ?? str_replace('SPPG-', '', $order['sppg']);
            $kdSppg = $delivery['kd_sppg'] ?? $order['sppg_code'];
            $namaSppg = $delivery['nama_sppg'] ?? str_replace('SPPG-', '', $order['sppg']);
            $pjSppg = $delivery['pj_sppg'] ?? '-';
            $whatsapp = $delivery['whatsapp'] ?? '-';
            $notes = $delivery['notes'] ?? '-';
            $supplierText = collect($order['items'])->pluck('supplier')->unique()->implode(', ');
            $receiverName = $order['sppg_pic_name'] ?: $pjSppg;
            $formattedDeliveryDate = \Illuminate\Support\Carbon::parse($deliveryDate)->format('d/m/Y');
        @endphp

        <header class="print:hidden sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-4 shadow-sm">
            <div class="flex items-center gap-5">
                <a href="{{ route('surat-jalan.show', $order['id']) }}" class="text-3xl leading-none text-slate-500 hover:text-slate-900">&times;</a>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Preview Surat Jalan</h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm">Print</button>
                <button type="button" onclick="window.print()" class="rounded-lg bg-red-600 px-5 py-3 text-sm font-black text-white shadow-sm shadow-red-600/20">Download PDF</button>
            </div>
        </header>

        <main class="mx-auto max-w-[820px] px-4 py-8 print:max-w-none print:p-0">
            <article class="min-h-[1120px] bg-white p-10 shadow-2xl shadow-slate-300/60 print:min-h-0 print:p-0 print:shadow-none">
                <section class="flex items-start justify-between gap-10 border-b-2 border-slate-900 pb-8">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.28em] text-blue-600">Dokumen Pengiriman</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">SURAT JALAN</h1>
                        <p class="mt-2 text-base font-black uppercase tracking-[0.22em] text-slate-700">{{ $sjNumber }}</p>
                    </div>
                    <div class="min-w-48 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-right">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-400">Tanggal Kirim</p>
                        <p class="mt-1 text-base font-black text-slate-950">{{ $formattedDeliveryDate }}</p>
                        <p class="mt-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">No PO</p>
                        <p class="mt-1 text-xs font-black text-blue-700">{{ $order['number'] }}</p>
                    </div>
                </section>

                <section class="mt-10 grid grid-cols-2 gap-8">
                    <div class="rounded-lg border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Tujuan Pengiriman</p>
                        </div>
                        <div class="space-y-2 px-3 py-2.5 text-sm">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Kepada</p>
                                <p class="mt-1 font-black text-slate-950">{{ $kepada }}</p>
                            </div>
                            {{-- <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Data SPPG</p>
                                <p class="mt-1 font-black text-slate-950">{{ $kdSppg }}  ({{ $namaSppg }})</p>
                            </div> --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">PJ SPPG</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $receiverName }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">No. WA</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $whatsapp }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-3 py-1.5">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Informasi Pengiriman</p>
                        </div>
                        <div class="space-y-2 px-3 py-2.5 text-sm">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Kurir</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $driver }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Supplier</p>
                                    <p class="mt-1 font-black text-slate-950">{{ $supplierText }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Catatan</p>
                                <p class="mt-1 font-semibold leading-relaxed text-slate-700">{{ $notes }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-10 overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full table-fixed">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="w-10 px-3 py-3 text-center text-[10px] font-black uppercase tracking-[0.18em]">No</th>
                                <th class="px-3 py-3 text-left text-[10px] font-black uppercase tracking-[0.18em]">Nama Barang</th>
                                <th class="w-24 px-3 py-3 text-center text-[10px] font-black uppercase tracking-[0.18em]">Volume</th>
                                <th class="w-40 px-3 py-3 text-left text-[10px] font-black uppercase tracking-[0.18em]">Supplier</th>
                                <th class="w-36 px-3 py-3 text-left text-[10px] font-black uppercase tracking-[0.18em]">Keterangan</th>
                                <th class="w-28 px-3 py-3 text-center text-[10px] font-black uppercase tracking-[0.18em]">Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($order['items'] as $item)
                                <tr>
                                    <td class="px-3 py-4 text-center text-xs font-black text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-4 text-sm font-black uppercase text-slate-950">{{ $item['name'] }}</td>
                                    <td class="px-3 py-4 text-center text-sm font-black text-slate-950">{{ $item['qty'] }} {{ strtoupper($item['unit']) }}</td>
                                    <td class="px-3 py-4 text-xs font-black uppercase leading-relaxed text-blue-700">{{ $item['supplier'] }}</td>
                                    <td class="px-3 py-4 text-sm font-semibold text-slate-600">{{ $item['request'] ?? '-' }}</td>
                                    <td class="px-3 py-4 text-center text-[10px] font-black uppercase text-slate-400">Terlampir</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <section class="mt-20 grid grid-cols-3 gap-10 text-center">
                    <div>
                        <p class="mb-16 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Dibuat Oleh,</p>
                        <div class="border-t border-slate-900 pt-3 text-sm font-black">Supplier (Admin)</div>
                    </div>
                    <div>
                        <p class="mb-16 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Dikirim Oleh,</p>
                        <div class="border-t border-slate-900 pt-3 text-sm font-black">{{ $driver }}</div>
                    </div>
                    <div>
                        <p class="mb-16 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Diterima Oleh,</p>
                        <div class="border-t border-slate-900 pt-3 text-sm font-black">{{ $receiverName }}</div>
                    </div>
                </section>

                <footer class="mt-24 border-t border-slate-200 pt-5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                    Dokumen ini dihasilkan secara otomatis melalui sistem manajemen PO CV. SPPG
                </footer>
            </article>
        </main>
    </body>
</html>
