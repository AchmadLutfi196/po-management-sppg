<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice Preview - {{ $invoice['number'] }}</title>
        <link rel="icon" href="{{ asset('logo-procurement.jpeg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @page {
                size: A4;
                margin: 5mm;
            }
            @media print {
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .page-break-avoid {
                    page-break-inside: avoid;
                }
                .print-grid-2 {
                    display: grid !important;
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
                .print-flex-row {
                    display: flex !important;
                    flex-direction: row !important;
                    justify-content: space-between !important;
                }
                .print-w-72 {
                    width: 18rem !important; /* setara dengan w-72 di tailwind */
                }
            }
        </style>
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased print:bg-white print:text-xs">
        @php
            $total = $items->sum(fn ($item) => $item['qty'] * $item['price']);
            $theme = $supplier['theme'];
            $isPaid = $invoice['status'] === 'PAID';
            $statusColor = $isPaid ? '#16a34a' : '#dc2626';
        @endphp

        <header class="print:hidden sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-4 shadow-sm">
            <div class="flex items-center gap-5">
                <a href="{{ route('invoices.index', ['tab' => 'history']) }}" onclick="if (history.length > 1) { event.preventDefault(); history.back(); }" class="text-3xl leading-none text-slate-500 hover:text-slate-900">&times;</a>
                <h1 class="text-xl font-black tracking-tight text-slate-900">Invoice Preview</h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">Print / Download PDF</button>
            </div>
        </header>

        <main class="mx-auto max-w-[800px] px-4 py-8 print:max-w-none print:p-0">
            <article class="bg-white p-12 shadow-2xl shadow-slate-300/60 print:px-4 print:py-0 print:shadow-none">
                <!-- Header Info -->
                <section class="flex items-start justify-between gap-8 page-break-avoid">
                    <div>
                        <img src="{{ asset($supplier['logo']) }}" alt="{{ $supplier['name'] }}" class="mb-4 h-16 w-16 object-contain print:mb-2 print:h-12 print:w-12">
                        <h2 class="text-xl font-black uppercase tracking-tight text-slate-950 print:text-lg">{{ $supplier['name'] }}</h2>
                        <p class="mt-1 text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Distribusi tepat. Pangan berkualitas.</p>
                        <p class="mt-2 text-xs font-medium text-slate-500 print:mt-1">{{ $supplier['address'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-serif text-4xl font-black italic tracking-tight text-slate-950 print:text-3xl">INVOICE</p>
                        <span class="mt-4 inline-flex rounded-full px-5 py-1.5 text-[9px] font-black uppercase tracking-wider text-white" style="background-color: {{ $statusColor }}">
                            {{ $isPaid ? 'Lunas' : 'Belum Bayar' }}
                        </span>
                        <p class="mt-3 text-xs font-black uppercase tracking-wider text-slate-950">{{ $invoice['number'] }}</p>
                    </div>
                </section>

                <div class="mt-6 border-t-2 print:mt-2" style="border-color: {{ $theme }}"></div>

                <!-- Info Customer & Invoice Details -->
                <section class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 print-grid-2 print:mt-2 print:gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5" style="border-left: 4px solid {{ $theme }}">
                        <p class="mb-3 text-[9px] font-black uppercase tracking-[0.18em]" style="color: {{ $theme }}">Invoice To</p>
                        <p class="text-base font-black uppercase text-slate-900">{{ $order['sppg'] }}</p>
                        <p class="mt-1 text-[10px] font-medium text-slate-500">{{ $order['sppg_location'] ?? 'Mojokerto' }}</p>
                        <p class="mt-0.5 text-[10px] font-medium text-slate-500">{{ $order['sppg_whatsapp'] ?? '-' }}</p>
                    </div>
                    <div class="space-y-3 text-xs print:text-[10px]">
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Invoice No</span>
                            <span class="font-black text-slate-950">{{ $invoice['number'] }}</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Transaction</span>
                            <span class="text-right font-black" style="color: {{ $theme }}">Tagihan Pengadaan Barang<br><span class="text-[10px] text-slate-500">(PO: {{ $order['number'] }})</span></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-200 pb-2">
                            <span class="text-slate-500">Date</span>
                            <span class="font-black text-slate-950">{{ date('n/j/Y', strtotime($invoice['date'])) }}</span>
                        </div>
                    </div>
                </section>

                <!-- Tabel Item -->
                <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 print:mt-3">
                    <table class="w-full table-fixed">
                        <thead class="text-white print:text-slate-900" style="background-color: {{ $theme }};">
                            <tr>
                                <th class="w-12 px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider print:text-white">No</th>
                                <th class="px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider print:text-white">Description</th>
                                <th class="w-16 px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider print:text-white">Unit</th>
                                <th class="w-16 px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider print:text-white">Qty</th>
                                <th class="w-28 px-3 py-3 text-right text-[8px] font-black uppercase tracking-wider print:text-white">Price</th>
                                <th class="w-32 px-3 py-3 text-right text-[8px] font-black uppercase tracking-wider print:text-white">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-slate-50 print:bg-white">
                            @foreach ($items as $item)
                                <tr>
                                    <td class="px-3 py-3 text-center text-[10px] text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-3 text-[10px] font-black text-slate-950">
                                        {{ $item['name'] }}
                                        <p class="mt-0.5 text-[8px] font-black uppercase text-slate-400">PO Ref: {{ $order['number'] }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-center text-[10px] text-slate-600">{{ $item['unit'] }}</td>
                                    <td class="px-3 py-3 text-center text-[10px] font-black">{{ $item['qty'] }}</td>
                                    <td class="px-3 py-3 text-right text-[10px] text-slate-600">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right text-[10px] font-black text-slate-950">Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <!-- Subtotal & Total -->
                <section class="mt-8 flex justify-end print:mt-3">
                    <div class="w-full max-w-sm space-y-4 print-w-72">
                        <div class="flex justify-between text-sm print:text-xs">
                            <span class="text-slate-700">Sub Total</span>
                            <span class="font-black">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg px-4 py-3 text-white" style="background-color: {{ $theme }}">
                            <span class="text-[9px] font-black uppercase tracking-[0.18em]">Total Amount</span>
                            <span class="text-lg font-black print:text-base">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-right text-[9px] font-bold italic" style="color: {{ $theme }}">* Harga tidak menggunakan PPN</p>
                    </div>
                </section>

                <!-- Payment Info & Signature -->
                <section class="mt-8 space-y-8 print:mt-3 print:space-y-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 print:p-3">
                        <h3 class="text-xs font-black text-slate-900">Payment Information</h3>
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm print:p-3">
                            <p class="text-[8px] font-black uppercase tracking-[0.18em] text-slate-400">Transfer Bank</p>
                            <p class="mt-1 text-xs font-black text-slate-950">AN. {{ $supplier['bank_account_name'] }}</p>
                            <div class="mt-3 grid gap-2">
                                @foreach ($supplier['bank_accounts'] as $account)
                                    <div class="flex items-center justify-between gap-4 rounded-md bg-slate-50 px-3 py-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">{{ $account['bank'] }}</span>
                                        <span class="text-xs font-black tracking-wider" style="color: {{ $theme }}">{{ $account['number'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="ml-auto flex w-72 flex-col items-center justify-end text-center print:w-64">
                        <p class="text-xs italic text-slate-500">Managing Director</p>
                        <div class="mt-2 flex h-24 items-center justify-center print:h-16">
                            <img src="{{ asset($supplier['stamp']) }}" alt="Stamp {{ $supplier['name'] }}" class="h-24 w-24 object-contain opacity-80 print:h-16 print:w-16">
                        </div>
                        <div class="mt-2 w-full border-t border-slate-950 pt-2 text-[10px] font-black">{{ $supplier['managing_director_name'] }}</div>
                    </div>
                </section>

                <footer class="mt-10 border-t border-slate-200 pt-4 text-center print:mt-2">
                    <p class="text-[10px] text-slate-500">{{ $supplier['address'] }}</p>
                    <p class="mt-2 text-[8px] font-black uppercase tracking-[0.18em]" style="color: {{ $theme }}">{{ $supplier['name'] }}</p>
                </footer>
            </article>
        </main>
    </body>
</html>
