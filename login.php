<?php
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']); // Clear error after displaying it
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="">

    <section class="relative flex flex-wrap lg:h-screen lg:items-center">
        <div class="w-full px-4 py-12 sm:px-6 sm:py-16 lg:w-1/2 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-lg text-center">
                <h1 class="text-2xl font-bold sm:text-3xl text-blue-500 mb-4"> <span id="wish"></span></h1>
                <h1 class="text-2xl font-bold sm:text-3xl"> Get started today!</h1>


                <p class="mt-4 text-gray-500">
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Et libero nulla eaque error neque
                    ipsa culpa autem, at itaque nostrum!
                </p>
            </div>

            <form action="auth.php" method="POST">
                <!-- Email Input -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                    <input type="email" id="email" name="email" class="w-full rounded-lg border-gray-200 p-4 pe-12 text-sm shadow-sm border-2" placeholder="admin@example.com" required>
                    <span class="absolute inset-y-0 end-0 grid place-content-center px-4">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="size-4 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </span>
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                    <input type="password" id="password" name="password" class="w-full rounded-lg border-gray-200 p-4 pe-12 text-sm shadow-sm border-2" placeholder="••••••••" required>
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
        </div>

        <div class="relative h-64 w-full sm:h-96 lg:h-full lg:w-1/2">
            <img
                alt=""
                src="https://images.unsplash.com/photo-1630450202872-e0829c9d6172?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=774&q=80"
                class="absolute inset-0 h-full w-full object-cover" />
        </div>
    </section>
    <script>
        var d = new Date();
        var time = d.getHours();

        if (time < 12) {
           document.getElementById("wish").innerHTML = "Good morning!";
        }
        if (time > 12) {
           document.getElementById("wish").innerHTML = "Good afternoon!";
        }
        if (time == 12) {
          document.getElementById("wish").innerHTML = "Good noon!";
        }
    </script>
</body>

</html>