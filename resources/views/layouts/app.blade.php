<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'PO Management SPPG' }}</title>
        <link rel="icon" href="{{ asset('logo-procurement.jpeg') }}">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            window.AppAlerts = {
                success: @json(session('success')),
                error: @json($errors->any() ? $errors->first() : null),
            };
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans text-slate-800 antialiased">
        <div class="flex h-dvh overflow-hidden">
            @include('partials.sidebar', ['currentUser' => $currentUser])

            <div class="flex min-w-0 flex-1 flex-col">
                @include('partials.navbar', ['title' => $title ?? 'Dashboard', 'currentUser' => $currentUser])

                <main class="flex-1 overflow-y-auto px-3 py-4 sm:px-6 lg:px-8 lg:py-6">
                    <div class="mx-auto max-w-7xl space-y-6">
                        @yield('content')
                    </div>
                </main>

                <footer class="flex min-h-10 flex-col items-center justify-center gap-1 border-t border-slate-200 bg-slate-50 px-4 py-2 text-center text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 md:flex-row md:justify-between md:px-8">
                    <span>Operator: {{ $currentUser['name'] }} / {{ now()->format('Y-m-d') }}</span>
                    @include('partials.copyright')
                    <span>Versi: 2.1.0-STABLE</span>
                </footer>
            </div>
        </div>
    </body>
</html>
