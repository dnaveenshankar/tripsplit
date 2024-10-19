<?php
// Start session to manage user sessions
session_start();

// Include database connection
include 'db_connection.php';

// Check if username is set in GET parameters
if (!isset($_GET['username'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

$username = htmlspecialchars($_GET['username']);

// Retrieve the user's name from the database
$query = "SELECT name FROM users WHERE username = ?"; // Adjust table name and field as needed
$stmt = $conn->prepare($query);
if (!$stmt) {
    header("Location: errors.php"); // Redirect to errors page if prepare fails
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = htmlspecialchars($row['name']); // Get the name from the database
} else {
    // If no user is found, redirect to login
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TripSplit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome for icons -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #ff6b6b, #f7b733);
            color: white;
            height: 100vh; 
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 80%; 
            max-width: 800px; 
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .header {
            margin-bottom: 20px;
        }

        .logo {
            width: 100px; 
            margin-bottom: 20px;
            border-radius: 15px;
        }

        .logout {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .logout:hover::after {
            content: " Logout"; /* Display "Logout" text on hover */
        }

        .content {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .box {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            width: 150px; /* Width of the boxes */
            height: 150px; /* Height of the boxes */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 10px;
            transition: transform 0.3s;
            cursor: pointer;
            color: white;
        }

        .box:hover {
            transform: scale(1.05); /* Scale effect on hover */
        }

        .box i {
            font-size: 40px; /* Icon size */
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
        }

        .footer a {
            color: white;
            text-decoration: none;
        }
    </style>
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "logout.php"; // Redirect to logout script (create this file)
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo.png" alt="TripSplit Logo" class="logo"> <!-- Display the logo -->
            <h1>Welcome, <?php echo $name; ?>!</h1>
            <button class="logout" onclick="confirmLogout()">
                <i class="fas fa-power-off"></i> <!-- Power icon -->
            </button>
        </div>
        <div class="content">
            <div class="box" onclick="location.href='create_trip.php?username=<?php echo urlencode($username); ?>'">
                <i class="fas fa-plus-circle"></i>
                <span>Create Trips</span>
            </div>
            <div class="box" onclick="location.href='my_trips.php?username=<?php echo urlencode($username); ?>'">
                <i class="fas fa-folder-open"></i>
                <span>My Trips</span>
            </div>
            <div class="box" onclick="location.href='manage_trips.php?username=<?php echo urlencode($username); ?>'">
                <i class="fas fa-tasks"></i>
                <span>Manage Trips</span>
            </div>
        </div>
        <div class="footer">
            <p>TripSplit | Developed by <a href="https://naveenshankar.in" target="_blank">Naveen Shankar D</a></p>
        </div>
    </div>
</body>
</html>
