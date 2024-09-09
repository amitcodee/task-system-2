<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto bg-gray-800 p-8 rounded-lg shadow-lg">
        <div class="text-center mb-8">
            <h1 class="text-2xl text-white font-semibold">Spack</h1>
        </div>

        <form action="authenticate.php" method="POST">
            <!-- Email Input -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                <input type="email" id="email" name="email" class="w-full mt-1 p-3 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500" placeholder="admin@example.com" required>
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                <input type="password" id="password" name="password" class="w-full mt-1 p-3 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500" placeholder="••••••••" required>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center text-sm text-gray-400">
                    <input type="checkbox" name="remember" class="form-checkbox bg-gray-700 text-blue-500 focus:ring-blue-500">
                    <span class="ml-2">Remember me</span>
                </label>
                <a href="#" class="text-sm text-blue-500 hover:underline">Forgot your password?</a>
            </div>

            <!-- Login Button -->
            <div class="mb-6">
                <button type="submit" class="w-full p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">LOG IN</button>
            </div>
        </form>

        <!-- Demo Credentials -->
        <div class="text-gray-400">
            <p><strong>Admin</strong>: admin@example.com / admin</p>
            <p><strong>User</strong>: user@example.com / password</p>
        </div>
    </div>
</body>
</html>
