<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catatan Harian - Digital Diary NAS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#bae0fd',
                            300: '#7cc8fc',
                            400: '#36abf8',
                            500: '#0c8ee9',
                            600: '#0270c7',
                            700: '#0359a1',
                            800: '#074c85',
                            900: '#0c406e',
                            950: '#082849',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">
    <!-- Navbar Header -->
    <header class="bg-gradient-to-r from-brand-900 via-brand-800 to-brand-700 text-white shadow-lg sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-white shadow-inner">
                    <i class="fa-solid fa-book-bookmark text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        Catatan Harian
                        <span class="text-xs bg-brand-500/40 text-brand-100 px-2 py-0.5 rounded-full border border-brand-300/30 font-medium">Digital Diary</span>
                    </h1>
                    <p class="text-xs text-brand-200">Sistem Jurnal Pribadi Terintegrasi Storage NAS</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 text-xs bg-brand-950/40 px-3 py-1.5 rounded-lg border border-white/10 text-brand-200">
                <i class="fa-solid fa-hard-drive text-brand-400"></i>
                <span>Storage status: <strong class="text-emerald-400">NAS Connected</strong></span>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>&copy; {{ date('Y') }} Catatan Harian (Digital Diary). Powered by Laravel & NAS Mount Storage.</p>
            <div class="flex items-center gap-4 text-slate-500">
                <span class="flex items-center gap-1"><i class="fa-solid fa-shield-halved text-brand-400"></i> Local Network</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-database text-brand-400"></i> MySQL DB</span>
            </div>
        </div>
    </footer>
</body>
</html>
