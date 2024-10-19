<?php
session_start();
include 'db_connection.php';

// Check if username is set in GET parameters
if (!isset($_GET['username'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

$username = htmlspecialchars($_GET['username']);

// Retrieve the user's trips from the database
$query = "
    SELECT t.*, 
           (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
            FROM participants p 
            WHERE p.trip_id = t.id) AS participants 
    FROM trips t 
    LEFT JOIN participants p ON t.id = p.trip_id 
    WHERE p.username = ? OR t.created_by = ?
    GROUP BY t.id
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - TripSplit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            overflow: auto;
            max-height: 70vh; /* Limit the height of the container */
        }

        .trip-box {
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            position: relative;
        }

        .power-button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 24px;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .button-container {
            margin-top: 10px;
        }

        .trip-button {
            background: #f7b733;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .trip-button:hover {
            background: #ff6b6b;
            color: white;
        }

        .disabled {
            background: gray;
            cursor: not-allowed;
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7); /* Dark background with transparency */
            padding-top: 60px;
        }

        .modal-content {
            background: rgba(0, 0, 0, 0.8); /* Semi-transparent background */
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px; /* Rounded corners */
            width: 80%;
            max-width: 500px;
            text-align: center;
            color: white; /* White text color for visibility */
        }

        .modal button {
            margin: 10px;
            padding: 10px 20px;
            background: #f7b733; /* Background color for buttons */
            border: none;
            border-radius: 5px; /* Rounded corners */
            color: black; /* Text color for buttons */
            cursor: pointer;
            transition: background 0.3s;
        }

        .modal button:hover {
            background: #ff6b6b; /* Change color on hover */
        }
    </style>
    <script>
        let currentTripId = null;
        let currentStatus = null;

        function openModal(tripId, status) {
            currentTripId = tripId;
            currentStatus = status;
            document.getElementById('statusChangeText').innerText = status === 'on' ? 'Inactive' : 'Active'; // Set statusChangeText to display new status
            document.getElementById('myModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('myModal').style.display = 'none';
        }

        function confirmStatusChange() {
            const newStatus = currentStatus === 'on' ? 'off' : 'on';
            fetch('update_trip_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tripId: currentTripId, status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh the page on success
                } else {
                    alert("Failed to update trip status: " + data.error);
                }
                closeModal(); // Close modal after action
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred: " + error.message);
                closeModal(); // Close modal on error
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>My Trips</h1>
        <button onclick="window.history.back()" style="margin-bottom: 20px; padding: 10px 20px; background: #f7b733; border: none; border-radius: 5px; cursor: pointer;">Back</button>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="trip-box">
                <h2><?php echo htmlspecialchars($row['trip_name']); ?></h2>
                <p>Participants: <?php echo htmlspecialchars($row['participants']); ?></p>
                <p>Status: <?php echo $row['status'] === 'on' ? 'Active' : 'Inactive'; ?></p>

                <!-- Only show the power button for the creator of the trip -->
                <?php if ($row['created_by'] === $username): ?>
                    <button class="power-button" id="power-button-<?php echo $row['id']; ?>" onclick="openModal(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                        <i class="fas fa-power-off" style="color: <?php echo $row['status'] === 'on' ? 'green' : 'red'; ?>;"></i>
                    </button>
                <?php endif; ?>
                
                <div class="button-container">
                    <button id="add-expense-<?php echo $row['id']; ?>" class="trip-button <?php echo $row['status'] === 'off' ? 'disabled' : ''; ?>" <?php echo $row['status'] === 'off' ? 'disabled' : ''; ?> onclick="window.location.href='add_expenses.php?trip_id=<?php echo $row['id']; ?>&username=<?php echo urlencode($username); ?>'">
                        Add Expenses
                    </button>
                    <button class="trip-button" onclick="window.location.href='view_expenses.php?trip_id=<?php echo $row['id']; ?>&username=<?php echo urlencode($username); ?>'">
                        View Expenses
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
        
        <!-- Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <p>Are you sure you want to change the status to <span id="statusChangeText"></span>?</p> <!-- Displaying the new status -->
                <button onclick="confirmStatusChange()">Yes</button>
                <button onclick="closeModal()">No</button>
            </div>
        </div>
    </div>
</body>
</html>
