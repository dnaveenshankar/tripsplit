<?php
session_start();
include 'db_connection.php';

// Check if the user is logged in and if the username is set
if (!isset($_SESSION['username']) && !isset($_GET['username'])) {
    header("Location: login.php");
    exit();
}

// Get username from URL parameter or session
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : $_SESSION['username'];

// Fetch trips and calculate total expenses for each trip
$query = "
    SELECT 
        t.id, 
        t.trip_name, 
        t.date_from, 
        t.date_to, 
        t.number_of_persons, 
        COALESCE(SUM(e.amount), 0) AS total_expenses,
        t.created_by
    FROM trips t
    LEFT JOIN expenses e ON t.id = e.trip_id
    GROUP BY t.id, t.trip_name, t.date_from, t.date_to, t.number_of_persons, t.created_by
";
$result = $conn->query($query);

// Fetch all trips
$trips = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
}

// Handle delete action
if (isset($_POST['delete_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    // Delete the trip and related expenses
    $stmt = $conn->prepare("DELETE FROM trips WHERE id = ?");
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    // Optionally delete expenses associated with the trip
    $stmt = $conn->prepare("DELETE FROM expenses WHERE trip_id = ?");
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    
    echo "<script>alert('Trip deleted successfully!'); window.location.href = 'manage_trips.php?username=$username';</script>";
    exit();
}

// Fetch participants for each trip
$participants_query = "
    SELECT trip_id, GROUP_CONCAT(name SEPARATOR ', ') AS participant_names
    FROM participants
    GROUP BY trip_id
";
$participants_result = $conn->query($participants_query);
$participants = [];
while ($row = $participants_result->fetch_assoc()) {
    $participants[$row['trip_id']] = $row['participant_names'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trips</title>
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
            margin-top: 20px;
        }

        .header {
            margin-bottom: 20px;
        }

        .back-button {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid white;
        }

        th {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .delete-button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-button:hover {
            background-color: darkred;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this trip?');
        }
    </script>
</head>
<body>

<div class="container">
    <h1>Manage Trips</h1>
    
    <button class="back-button" onclick="window.location.href='dashboard.php?username=<?php echo urlencode($username); ?>'">Back</button>
    
    <table>
        <thead>
            <tr>
                <th>Trip Name</th>
                <th>Date From</th>
                <th>Date To</th>
                <th>Number of Participants</th>
                <th>Total Expenses</th>
                <th>Participants</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trips)): ?>
                <tr>
                    <td colspan="7">No trips found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($trips as $trip): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($trip['trip_name']); ?></td>
                        <td><?php echo htmlspecialchars($trip['date_from']); ?></td>
                        <td><?php echo htmlspecialchars($trip['date_to']); ?></td>
                        <td><?php echo htmlspecialchars($trip['number_of_persons']); ?></td>
                        <td><?php echo number_format($trip['total_expenses'], 2); ?></td>
                        <td><?php echo htmlspecialchars($participants[$trip['id']] ?? ''); ?></td>
                        <td>
                            <?php if ($trip['created_by'] === $username): ?>
                                <form method="POST" onsubmit="return confirmDelete();">
                                    <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                    <button type="submit" name="delete_trip" class="delete-button">Delete</button>
                                </form>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
