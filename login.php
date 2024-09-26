<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <style>
        /* Custom styles for a modern look */
        .bg-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .input-field {
            background-color: #f9fafb;
            border: 2px solid transparent;
            transition: border-color 0.3s ease-in-out;
        }

        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .form-container {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Password eye toggle styles */
        .password-toggle:hover {
            color: #667eea;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .company-name {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }

        .greeting {
            color: white;
            font-size: 1.25rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        i{
            margin-top: 20px;
        }
    </style>
</head>

<body class="bg-gradient min-h-screen flex flex-col items-center justify-center">

    <!-- Logo and Company Name -->
    <div class="text-center mb-6">
        <!-- <img src="https://scontent.fluh3-3.fna.fbcdn.net/v/t39.30808-6/312219580_538157488310903_5690662163397803386_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=KT436HGYxLUQ7kNvgGxhVri&_nc_ht=scontent.fluh3-3.fna&oh=00_AYBDrcrHEkbBa_HHsV2bubdckkZvj9AGfvGU9ykldEW5lQ&oe=66FAE947" alt="Techcadd Logo" class="logo mx-auto"> -->
        <h1 class="company-name" style="font-size:xx-large">Techcadd Computer Education Pvt. Ltd.</h1>
    </div>

    <!-- Greeting message -->
    <!-- <div class="greeting" id="wish"></div> -->

    <div class="w-full max-w-md bg-white p-8 rounded-lg form-container">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome Back!</h1>
            <p class="text-gray-500 mt-2">Please login to your account</p>
        </div>

        <form action="auth.php" method="POST">
            <!-- Email Input -->
            <div class="mb-6 relative">
                <label for="email" class="block text-sm font-medium text-gray-600">Email</label>
                <input type="email" id="email" name="email" class="w-full input-field rounded-lg p-3 text-sm shadow-sm focus:outline-none focus:ring focus:ring-indigo-500" placeholder="admin@example.com" required>
                <span class="absolute inset-y-0 right-4 flex items-center">
                    <i class="fas fa-envelope text-gray-400"></i>
                </span>
            </div>

            <!-- Password Input -->
            <div class="mb-6 relative">
                <label for="password" class="block text-sm font-medium text-gray-600">Password</label>
                <input type="password" id="password" name="password" class="w-full input-field rounded-lg p-3 text-sm shadow-sm focus:outline-none focus:ring focus:ring-indigo-500" placeholder="••••••••" required>
                <span class="absolute inset-y-0 right-4 flex items-center cursor-pointer password-toggle" onclick="togglePasswordVisibility()">
                    <i id="eyeIcon" class="fas fa-eye text-gray-400"></i>
                </span>
            </div>

            <!-- Login Button -->
            <div class="mb-6">
                <button type="submit" class="w-full p-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition-all">
                    LOG IN
                </button>
            </div>
        </form>
    </div>

    <!-- Toggle Password Visibility -->
    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye-slash');
        }

        // Greeting message based on the time of day
        var d = new Date();
        var time = d.getHours();

        if (time < 12) {
            document.getElementById("wish").innerHTML = "Good morning!";
        } else if (time >= 12 && time < 18) {
            document.getElementById("wish").innerHTML = "Good afternoon!";
        } else {
            document.getElementById("wish").innerHTML = "Good evening!";
        }
    </script>

</body>

</html>
