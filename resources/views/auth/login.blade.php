<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login - PO Management SPPG</title>
        <link rel="icon" href="{{ asset('logo-procurement.jpeg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
        @php($isAdmin = old('mode') === 'admin')

        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <section class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/60">
                <div class="p-8 sm:p-10">
                    <div class="mb-9 flex items-center justify-center gap-3">
                        @include('partials.app-logo', ['class' => 'h-16 w-16 rounded-2xl shadow-xl shadow-blue-500/20'])
                        <span class="text-2xl font-black tracking-tight text-slate-900">Procurement SPPG</span>
                    </div>

                    <div class="mb-8 text-center">
                        <h1 class="mb-2 text-2xl font-black tracking-tight text-slate-900">Selamat Datang</h1>
                        <p class="text-sm font-medium leading-relaxed text-slate-500">Silakan masuk untuk mengakses sistem Manajemen Purchase Order.</p>
                    </div>

                    <div class="mb-8 grid grid-cols-2 rounded-2xl bg-slate-100 p-1.5" data-login-tabs>
                        <button type="button" data-target="sppg" class="{{ $isAdmin ? 'text-slate-500' : 'bg-white text-blue-600 shadow-sm' }} rounded-xl py-3 text-xs font-black transition">PORTAL SPPG</button>
                        <button type="button" data-target="admin" class="{{ $isAdmin ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500' }} rounded-xl py-3 text-xs font-black transition">ADMIN SUPPLIER</button>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-600">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" data-login-panel="sppg" class="{{ $isAdmin ? 'hidden' : '' }} space-y-6">
                        @csrf
                        <input type="hidden" name="mode" value="sppg">
                        <div class="space-y-2">
                            <label for="sppg_code" class="ml-1 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Kode Unit SPPG</label>
                            <input id="sppg_code" name="sppg_code" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-bold uppercase outline-none transition placeholder:text-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" placeholder="Contoh: M1101">
                        </div>
                        {{-- <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4 text-[10px] font-bold leading-relaxed text-blue-700">
                            Kode M1101 akan masuk sebagai SPPG-Balongsari. Role SPPG hanya dapat membuat PO dan memantau menu lain.
                        </div> --}}
                        <button type="submit" class="w-full rounded-2xl bg-blue-600 py-5 text-sm font-black text-white shadow-xl shadow-blue-600/20 transition hover:bg-blue-700">
                            MULAI PENGADAAN
                        </button>
                    </form>

                    <form method="POST" action="{{ route('login.store') }}" data-login-panel="admin" class="{{ $isAdmin ? '' : 'hidden' }} space-y-6">
                        @csrf
                        <input type="hidden" name="mode" value="admin">
                        {{-- <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                            <p class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Informasi Akses</p>
                            <div class="flex gap-6 border-t border-slate-800 pt-3">
                                <div>
                                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Username</p>
                                    <p class="text-xs font-black text-white">admin</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-500">Password</p>
                                    <p class="text-xs font-black text-white">admin123</p>
                                </div>
                            </div>
                        </div> --}}
                        <div class="space-y-4">
                            <input name="username" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-bold outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5" placeholder="Username">
                            <input name="password" type="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-bold outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5" placeholder="Password">
                        </div>
                        <button type="submit" class="w-full rounded-2xl bg-slate-900 py-5 text-sm font-black text-white shadow-xl shadow-slate-900/20 transition hover:bg-slate-800">
                            AUTENTIKASI SUPPLIER
                        </button>
                    </form>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 p-6 text-center text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">
                    @include('partials.copyright')
                </div>
            </section>
        </main>

        <script>
            document.querySelectorAll('[data-login-tabs] button').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.target;
                    document.querySelectorAll('[data-login-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.loginPanel !== target));
                    document.querySelectorAll('[data-login-tabs] button').forEach((tab) => {
                        tab.className = tab.dataset.target === target
                            ? 'rounded-xl bg-white py-3 text-xs font-black text-blue-600 shadow-sm transition'
                            : 'rounded-xl py-3 text-xs font-black text-slate-500 transition';
                    });
                });
            });
        </script>
    </body>
</html>
