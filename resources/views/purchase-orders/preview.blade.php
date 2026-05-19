<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Purchase Order - {{ $order['number'] }}</title>
        <link rel="icon" href="{{ asset('logo-procurement.jpeg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @page {
                size: A4;
                margin: 10mm 15mm;
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
            $suppliers = collect($order['items'])->pluck('supplier')->unique()->values();
            $dropDate = $order['droping_date'] ?: '-';
            $dropTime = $order['droping_time'] ?: '-';
        @endphp

        <header class="print:hidden sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-4 shadow-sm">
            <div class="flex items-center gap-5">
                <a href="{{ route('purchase-orders.show', $order['id']) }}" class="text-3xl leading-none text-slate-500 hover:text-slate-900">&times;</a>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Preview Purchase Order</h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Print</button>
            </div>
        </header>

        <main class="mx-auto max-w-[850px] px-4 py-10 print:max-w-none print:p-0">
            <article class="min-h-[1120px] bg-white p-12 shadow-xl shadow-slate-200 print:min-h-0 print:p-0 print:shadow-none">
                <!-- Header Dokumen -->
                <header class="mb-8 flex items-start justify-between border-b-2 border-slate-900 pb-6">
                    <div>
                        <h2 class="text-3xl font-black uppercase tracking-widest text-slate-900">Procurement SPPG</h2>
                        <p class="mt-1 text-sm font-medium text-slate-600">Sistem Manajemen Purchase Order</p>
                    </div>
                    <div class="text-right">
                        <h1 class="text-3xl font-black uppercase tracking-tight text-slate-900">Purchase Order</h1>
                        <p class="mt-2 text-base font-bold text-slate-700">{{ $order['number'] }}</p>
                    </div>
                </header>

                <!-- Informasi Utama -->
                <div class="mb-8 grid grid-cols-2 gap-8">
                    <!-- Detail PO -->
                    <table class="w-full text-sm">
                        <tbody>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Tanggal PO</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ date('d F Y', strtotime($order['date'])) }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Status</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ $order['status'] }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Dibuat Oleh</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ $order['created_by'] }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Detail Pengiriman -->
                    <table class="w-full text-sm">
                        <tbody>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Kode SPPG</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ $order['sppg_code'] }} ({{ $order['sppg'] }})</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Tgl Drop</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ $dropDate }}</td>
                            </tr>
                            <tr>
                                <td class="py-1.5 w-32 font-bold text-slate-500 uppercase text-xs tracking-wider">Jam Drop</td>
                                <td class="py-1.5 font-bold text-slate-900">: {{ $dropTime }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Informasi Supplier (jika ada lebih dari 1, tampilkan sebagai info tambahan) -->
                <div class="mb-6">
                    <p class="mb-1 text-xs font-bold uppercase tracking-wider text-slate-500">Supplier Tujuan:</p>
                    <p class="text-sm font-bold text-slate-900">{{ $suppliers->implode(', ') }}</p>
                </div>

                <!-- Tabel Barang -->
                <table class="w-full border-collapse border border-slate-400">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="border border-slate-400 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-800 w-12">No</th>
                            <th class="border border-slate-400 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-800">Nama Barang / Deskripsi</th>
                            <th class="border border-slate-400 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-800 w-24">Qty</th>
                            <th class="border border-slate-400 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-800 w-24">Satuan</th>
                            <th class="border border-slate-400 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-800 w-40">Supplier</th>
                            <th class="border border-slate-400 px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-800 w-40">Request / Grade</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach ($order['items'] as $item)
                            <tr>
                                <td class="border border-slate-400 py-3 text-center">{{ $loop->iteration }}</td>
                                <td class="border border-slate-400 px-4 py-3">
                                    <div class="font-bold uppercase text-slate-900">{{ $item['name'] }}</div>
                                </td>
                                <td class="border border-slate-400 py-3 text-center font-bold text-slate-900">{{ $item['qty'] }}</td>
                                <td class="border border-slate-400 py-3 text-center uppercase">{{ $item['unit'] }}</td>
                                <td class="border border-slate-400 px-4 py-3 uppercase text-slate-700">{{ $item['supplier'] }}</td>
                                <td class="border border-slate-400 px-4 py-3">
                                    @if(!empty($item['grade']))
                                        <div class="text-xs font-bold uppercase text-slate-800">Grade: {{ $item['grade'] }}</div>
                                    @endif
                                    @if(!empty($item['request']))
                                        <div class="mt-0.5 text-xs italic text-slate-600">{{ $item['request'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Kolom Tanda Tangan -->
                <div class="mt-20 grid grid-cols-2 text-center text-sm font-bold">
                    <div>
                        <p class="mb-20 uppercase text-slate-600">Dibuat Oleh,</p>
                        <p class="border-b border-slate-400 mx-16 pb-1">Procurement SPPG</p>
                    </div>
                    <div>
                        <p class="mb-20 uppercase text-slate-600">Menyetujui,</p>
                        <p class="border-b border-slate-400 mx-16 pb-1 text-transparent">.</p>
                        <p class="mt-1 text-xs font-normal text-slate-500">( Tanda Tangan & Nama Terang )</p>
                    </div>
                </div>

                <!-- Footer Dokumen -->
                <footer class="mt-16 border-t border-slate-300 pt-4 text-center text-xs text-slate-400">
                    <p>Dokumen ini dicetak otomatis dari Sistem PO Manajemen Procurement SPPG pada {{ now()->format('d/m/Y H:i') }}</p>
                </footer>
            </article>
        </main>
    </body>
</html>
