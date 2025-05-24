<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Library Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: url('assets/images/libraryPU.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        header {
            background-color: #A6192E;
            padding: 25px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            color: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #ffffff;
        }

        .card p {
            font-size: 1.1em;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.85);
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            background-color: #A6192E;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #7e1323;
        }

        footer {
            text-align: center;
            background-color: whitesmoke;
            padding: 15px;
            font-size: 0.9em;
        }
    </style>
</head>

<body>

    <header>
        <h1>📚 Library Management System</h1>
    </header>

    <main>
        <div class="card">
            <h2>Welcome to Our Library</h2>
            <p>Manage books, members, and borrowing with ease. Please login or register to continue.</p>
            <a href="login.php" class="btn">Login</a>
            <a href="register.php" class="btn">Register</a>
        </div>
    </main>

    <footer>
        &copy; <?= date("Y") ?> Library Management System. All rights reserved.
    </footer>

</body>

</html>