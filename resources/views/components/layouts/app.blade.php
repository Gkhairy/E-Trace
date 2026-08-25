<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Dashboard' }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#f7f8fa',
                        darkcard: '#ffffff',
                    }
                }
            }
        }
    </script>
    
</head>

<body class="bg-slate-50 text-slate-900">

    <!-- HEADER -->
    <header class="w-full h-16 px-6 bg-white text-slate-900 flex items-center justify-between border-b border-slate-200 shadow-sm">

        <!-- LOGO -->
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-extrabold text-sm text-white">E</div>
            <span class="text-lg font-bold">E-<span class="text-blue-600">Trace</span></span>
        </div>

        <!-- MENU -->
        <nav class="hidden md:flex space-x-8 text-slate-600 text-sm">
            <a href="/products" class="hover:text-blue-600">Products</a>
            <a href="/orders" class="hover:text-blue-600">Orders</a>
        </nav>



        <!-- RIGHT ICON AREA -->
        <div class="flex items-center space-x-4">

            <!-- SEARCH BOX -->
            <div class="relative">
                <input
                    type="text"
                    placeholder="Search"
                    class="bg-slate-100 text-sm rounded-full pl-10 pr-4 py-1.5 outline-none text-slate-700 placeholder-slate-400 border border-slate-200 focus:border-blue-500 w-40 md:w-56"
                >
                <span class="absolute left-3 top-1.5 text-slate-400 text-xs">/</span>
            </div>

            <!-- BUTTON AI -->
            <button class="text-xs bg-blue-700 hover:bg-blue-800 px-3 py-1.5 rounded-full">
                CMC AI
            </button>

            <!-- LOGOUT -->
            <form action="/logout" method="POST">
                @csrf
                <button class="bg-red-600 hover:bg-red-700 p-2 rounded-lg text-xs">
                    Logout
                </button>
            </form>

        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="p-6">
        {{ $slot }}
    </main>

</body>
</html>
