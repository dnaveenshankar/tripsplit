<?php
// Include database connection
include 'db_connection.php';

$message = ""; // For displaying messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Retrieve user data from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Redirect to dashboard.php with username and name
            header("Location: dashboard.php?username=" . urlencode($username) . "&name=" . urlencode($user['name']));
            exit();
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TripSplit</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #ff6b6b, #f7b733); /* Colorful gradient background */
            color: white;
            height: 100vh; /* Full height of the viewport */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 300px; /* Fixed width for the container */
            text-align: center;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .logo {
            width: 100px; /* Adjust based on your logo size */
            margin-bottom: 20px;
            border-radius: 15px; /* Smooth corners of the logo */
        }

        h1 {
            margin-bottom: 20px;
        }

        input {
            width: calc(100% - 20px); /* Match input box size to container */
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        #message {
            color: red;
            margin-top: 10px;
        }

        .signup-link {
            margin-top: 20px;
            color: white;
            text-decoration: none;
        }

        .signup-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="TripSplit Logo" class="logo"> <!-- Added logo here -->
        <h1>Login</h1>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <p id="message"><?php echo $message; ?></p>
        </form>
        <p>Don't have an account? <a href="signup.php" class="signup-link">Sign Up</a></p>
    </div>
</body>
</html>
