<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - ConnectX</title>
</head>
<body>
    <h1>Welcome to ConnectX</h1>
    <p>Silakan pilih menu di bawah ini:</p>
    <ul>
        <li><a href="/login">Login</a></li>
        <li><a href="/register">Register</a></li>
    </ul>

    <script>
        // Check if token exists, redirect to dashboard if yes
        const token = localStorage.getItem('token');
        if (token) {
            window.location.href = '/dashboard';
        }
    </script>
</body>
</html>
