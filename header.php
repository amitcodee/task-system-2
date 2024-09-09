<!-- header.php -->
<header class="flex items-center justify-between bg-white p-4 shadow">
    <h2 class="text-xl font-semibold">Dashboard</h2>
    <div class="flex items-center">
        <button class="text-gray-600 hover:text-gray-800 focus:outline-none">
            <span class="sr-only">Notifications</span>
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a6 6 0 00-6 6v5c0 .55-.45 1-1 1s-1-.45-1-1V8a8 8 0 1116 0v5c0 .55-.45 1-1 1s-1-.45-1-1V8a6 6 0 00-6-6zm-2 18h4a2 2 0 01-4 0z"></path></svg>
        </button>

        <div class="relative ml-4">
            <button class="flex items-center text-sm focus:outline-none" id="user-menu" aria-expanded="false" aria-haspopup="true">
                <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/150" alt="Admin Avatar">
                <span class="ml-2 text-gray-600">Admin</span>
            </button>
        </div>
    </div>
</header>
