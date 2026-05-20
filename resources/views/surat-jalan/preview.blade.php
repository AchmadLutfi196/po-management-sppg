<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Surat Jalan - {{ $order['delivery']['number'] ?? $order['number'] }}</title>
        <link rel="icon" href="{{ asset('logo-procurement.jpeg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @page {
                size: A5 landscape;
                margin: 8mm;
            }

            @media print {
                html, body {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    background: #fff !important;
                }
            }

            .sj-paper {
                width: 210mm;
                min-height: 148mm;
                font-size: 10.5px;
            }

            @media print {
                .sj-paper {
                    width: 100%;
                    min-height: 0;
                    box-shadow: none;
                    padding: 0;
                }
            }
        </style>
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased print:bg-white">
        @php
            $delivery     = $order['delivery'] ?? [];
            $sjNumber     = $delivery['number'] ?? $order['delivery_suggested_number'];
            $deliveryDate = $delivery['date'] ?? now()->format('Y-m-d');
            $deliveryTime = $delivery['time'] ?? ($order['droping_time'] ?? null);
            $driver       = $delivery['driver'] ?? 'Nama Pengirim';
            $kepada       = $delivery['kepada'] ?? str_replace('SPPG-', '', $order['sppg']);
            $kdSppg       = $delivery['kd_sppg'] ?? $order['sppg_code'];
            $namaSppg     = $delivery['nama_sppg'] ?? str_replace('SPPG-', '', $order['sppg']);
            $pjSppg       = $delivery['pj_sppg'] ?? '-';
            $whatsapp     = $delivery['whatsapp'] ?? '-';
            $notes        = $delivery['notes'] ?? '-';
            $supplierText = collect($order['items'])->pluck('supplier')->unique()->filter(fn ($s) => $s !== '-')->implode(', ') ?: '-';
            $receiverName = $order['sppg_pic_name'] ?: $pjSppg;
            $preparedByName = $preparedBy ?? 'Supplier';
            $formattedDate = \Illuminate\Support\Carbon::parse($deliveryDate)->translatedFormat('d F Y');
            $alamatSppg = $order['sppg_location'] ?? '-';
            $totalQty = collect($order['items'])->sum('qty');
        @endphp

        {{-- Toolbar (hidden saat print) --}}
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3 shadow-sm print:hidden">
            <div class="flex items-center gap-4">
                <a href="{{ route('surat-jalan.show', $order['id']) }}" class="text-2xl leading-none text-slate-500 hover:text-slate-900">&times;</a>
                <h1 class="text-base font-black tracking-tight text-slate-900">Preview Surat Jalan</h1>
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">A5 Landscape</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">Print</button>
                <button type="button" onclick="window.print()" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-red-600/20 hover:bg-red-700">Download PDF</button>
            </div>
        </header>

        <main class="mx-auto px-4 py-6 print:p-0">
            <article class="sj-paper mx-auto bg-white p-6 shadow-2xl shadow-slate-300/60 print:shadow-none">
                {{-- Logo Supplier + Title --}}
                <div class="flex items-start justify-between border-b-2 border-slate-900 pb-2">
                    <div class="flex items-center gap-3">
                        @if (!empty($supplier['logo']))
                            <img src="{{ asset($supplier['logo']) }}" alt="{{ $supplier['name'] }}" class="h-10 w-10 rounded object-contain">
                        @endif
                        <div class="text-[10px] leading-tight">
                            <p class="text-[12px] font-black uppercase">{{ $supplier['name'] }}</p>
                            <p class="font-semibold text-slate-600">{{ $supplier['address'] }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <h1 class="text-2xl font-black leading-none tracking-tight">SURAT JALAN</h1>
                    </div>
                </div>

                {{-- Info recipient + dokumen (2 kolom) --}}
                <div class="mt-2 grid grid-cols-2 gap-6">
                    {{-- Kiri: Data Penerima --}}
                    <div>
                        <p class="mb-1 text-[9px] font-bold uppercase tracking-wide text-slate-500">Kepada Yth.</p>
                        <div class="space-y-0.5 text-[11px] leading-snug">
                            <div class="flex">
                                <span class="w-16 font-bold">Nama</span>
                                <span class="font-semibold">: {{ $namaSppg }} ({{ $kdSppg }})</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 font-bold">PJ</span>
                                <span class="font-semibold">: {{ $receiverName }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 font-bold">No. Telp</span>
                                <span class="font-semibold">: {{ $whatsapp }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-16 font-bold">Alamat</span>
                                <span class="font-semibold">: {{ $alamatSppg }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Kanan: Data Dokumen --}}
                    <div class="space-y-0.5 text-[11px] leading-snug">
                        <div class="flex">
                            <span class="w-20 font-bold">No. SJ</span>
                            <span class="font-semibold">: {{ $sjNumber }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-20 font-bold">No. PO</span>
                            <span class="font-semibold">: {{ $order['number'] }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-20 font-bold">Tanggal</span>
                            <span class="font-semibold">: {{ $formattedDate }}@if ($deliveryTime), {{ $deliveryTime }} @endif</span>
                        </div>
                        <div class="flex">
                            <span class="w-20 font-bold">Driver</span>
                            <span class="font-semibold">: {{ $driver }}</span>
                        </div>
                        @if (!empty($delivery['date']))
                            <div class="flex">
                                <span class="w-20 font-bold">Tgl Terima</span>
                                <span class="font-semibold">: {{ \Illuminate\Support\Carbon::parse($delivery['date'])->translatedFormat('d F Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tabel Barang --}}
                <table class="mt-3 w-full border-collapse text-[11px]">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="border border-slate-700 px-2 py-1 text-center font-bold">No</th>
                            <th class="border border-slate-700 px-2 py-1 text-left font-bold">Nama Barang</th>
                            <th class="border border-slate-700 px-2 py-1 text-center font-bold">Qty</th>
                            <th class="border border-slate-700 px-2 py-1 text-center font-bold">Satuan</th>
                            <th class="border border-slate-700 px-2 py-1 text-center font-bold">Grade</th>
                            <th class="border border-slate-700 px-2 py-1 text-left font-bold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order['items'] as $item)
                            <tr>
                                <td class="border border-slate-400 px-2 py-1 text-center">{{ $loop->iteration }}</td>
                                <td class="border border-slate-400 px-2 py-1 font-semibold uppercase">{{ $item['name'] }}</td>
                                <td class="border border-slate-400 px-2 py-1 text-center">{{ $item['qty'] }}</td>
                                <td class="border border-slate-400 px-2 py-1 text-center uppercase">{{ $item['unit'] }}</td>
                                <td class="border border-slate-400 px-2 py-1 text-center">{{ $item['grade'] ?? 'A' }}</td>
                                <td class="border border-slate-400 px-2 py-1">{{ $item['request'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Total & Perhatian --}}
                <div class="mt-2 grid grid-cols-2 gap-3 text-[10px]">
                    <div class="border border-slate-700 p-2">
                        <p class="font-bold">Total Item: {{ count($order['items']) }} jenis</p>
                        <p class="mt-0.5"><span class="font-bold">Catatan:</span> {{ $notes }}</p>
                        <p class="mt-0.5"><span class="font-bold">Supplier:</span> {{ $supplierText }}</p>
                    </div>
                    <div class="border border-slate-700 p-2 leading-snug">
                        <p class="font-bold">INFORMASI PEMBAYARAN:</p>
                        <p class="mt-0.5">AN. {{ $supplier['bank_account_name'] }}</p>
                        @foreach ($supplier['bank_accounts'] as $account)
                            <p>{{ $account['bank'] }}: {{ $account['number'] }}</p>
                        @endforeach
                    </div>
                </div>

                {{-- Tanda tangan --}}
                <div class="mt-3 text-[10px] italic">BARANG SUDAH DITERIMA DALAM KEADAAN BAIK DAN CUKUP oleh:</div>
                <div class="mt-1 grid grid-cols-3 gap-4 text-center text-[10px]">
                    <div>
                        <p class="font-bold">Penerima / Pembeli</p>
                        <div class="mt-12 mx-4 border-t border-slate-900 pt-1 font-semibold">{{ $receiverName }}</div>
                    </div>
                    <div>
                        <p class="font-bold">Bagian Pengiriman</p>
                        <div class="mt-12 mx-4 border-t border-slate-900 pt-1 font-semibold">{{ $driver }}</div>
                    </div>
                    <div>
                        <p class="font-bold">Petugas Gudang</p>
                        <div class="mt-12 mx-4 border-t border-slate-900 pt-1 font-semibold">{{ $preparedByName }}</div>
                    </div>
                </div>
            </article>
        </main>
    </body>
</html>
