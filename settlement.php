<?php
session_start();
include 'db_connection.php';

// Check if username and trip_id are set in GET parameters
if (!isset($_GET['username']) || !isset($_GET['trip_id'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

$username = htmlspecialchars($_GET['username']);
$trip_id = htmlspecialchars($_GET['trip_id']);

// Retrieve trip information to check if the current user is the trip creator
$query = "SELECT created_by FROM trips WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();
$trip_creator = $trip['created_by'];

// Retrieve participants and their expenses for the specific trip
$query = "
    SELECT 
        p.name, 
        p.username, 
        COALESCE(total_paid.total_paid, 0) AS total_paid, 
        COALESCE(SUM(es.share), 0) AS total_share,
        s.status
    FROM participants p
    LEFT JOIN (
        SELECT paid_by, SUM(amount) AS total_paid
        FROM expenses
        WHERE trip_id = ?
        GROUP BY paid_by
    ) AS total_paid ON total_paid.paid_by = p.name
    LEFT JOIN expense_shares es ON es.expense_id IN (SELECT id FROM expenses WHERE trip_id = ?) AND es.participant_name = p.name
    LEFT JOIN settlements s ON s.trip_id = p.trip_id AND s.participant_name = p.name
    WHERE p.trip_id = ?
    GROUP BY p.name, p.username, s.status
";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $trip_id, $trip_id, $trip_id);
$stmt->execute();
$participants_result = $stmt->get_result();
$participants = [];

// Calculate balances for each participant
while ($row = $participants_result->fetch_assoc()) {
    $total_paid = (float)$row['total_paid']; // Cast to float for precision
    $total_share = (float)$row['total_share']; // Cast to float for precision
    $row['balance'] = number_format($total_paid - $total_share, 2, '.', ''); // Calculate balance and format to 2 decimal places
    $participants[] = $row;
}

// Handle settlement action to insert or update settlement records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($participants as $participant) {
        $participant_name = $participant['name'];
        $total_paid = (float)$participant['total_paid']; // Cast to float for precision
        $total_share = (float)$participant['total_share']; // Cast to float for precision
        $balance = $total_paid - $total_share;

        // Calculate to pay or to return
        $to_return = $balance > 0 ? number_format($balance, 2, '.', '') : 0; // Format to 2 decimal places
        $to_pay = $balance < 0 ? abs(number_format($balance, 2, '.', '')) : 0; // Format to 2 decimal places

        // Check if settlement record exists
        $stmt = $conn->prepare("SELECT id FROM settlements WHERE trip_id = ? AND participant_name = ?");
        $stmt->bind_param("is", $trip_id, $participant_name);
        $stmt->execute();
        $settlement_result = $stmt->get_result();

        // Determine status based on whether the checkbox is checked
        $status = isset($_POST['settle'][$participant_name]) ? 'settled' : 'not settled';

        if ($settlement_result->num_rows > 0) {
            // Update existing settlement
            $stmt = $conn->prepare("UPDATE settlements SET to_pay = ?, to_return = ?, status = ? WHERE trip_id = ? AND participant_name = ?");
            $stmt->bind_param("ddsis", $to_pay, $to_return, $status, $trip_id, $participant_name);
        } else {
            // Insert new settlement
            $stmt = $conn->prepare("INSERT INTO settlements (trip_id, participant_name, to_pay, to_return, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issdd", $trip_id, $participant_name, $to_pay, $to_return, $status);
        }
        $stmt->execute();
    }

    echo "<script>alert('Settlement updated successfully!'); window.location.href = 'settlement.php?trip_id=$trip_id&username=$username';</script>";
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement - TripSplit</title>
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
            max-height: 100vh;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            color: white;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background: rgba(255, 255, 255, 0.1);
        }

        .settlement-button {
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
        }

        button[type="submit"] {
            background-color: #ff6b6b;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .back-button {
            background-color: #333;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Settlement for Trip</h1>
        
        <?php if ($username === $trip_creator): ?>
            <button class="settlement-button" onclick="document.getElementById('settle-form').submit();">Update to DB</button>
        <?php endif; ?>

        <form id="settle-form" method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Total Paid</th>
                        <th>Total Share</th>
                        <th>To Pay</th>
                        <th>To Return</th>
                        <th>Status</th>
                        <?php if ($username === $trip_creator): ?>
                            <th>Settled</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($participants as $participant): ?>
    <tr>
        <td><?php echo htmlspecialchars($participant['name']); ?></td>
        <td><?php echo number_format($participant['total_paid'], 2); ?></td>
        <td><?php echo number_format($participant['total_share'], 2); ?></td>
        <td><?php echo number_format(max(0, $participant['total_share'] - $participant['total_paid']), 2); ?></td>
        <td><?php echo number_format(max(0, $participant['total_paid'] - $participant['total_share']), 2); ?></td>

        <!-- Display Status -->
        <td>
            <?php echo $participant['status'] === 'settled' ? 'Settled' : 'Not Settled'; ?>
        </td>

        <!-- Action: Checkbox for the creator to mark as settled/unsettled -->
        <?php if ($username === $trip_creator): ?>
            <td>
                <input type="checkbox" name="settle[<?php echo htmlspecialchars($participant['name']); ?>]" value="settled" 
                <?php echo $participant['status'] === 'settled' ? 'checked' : ''; ?>>
            </td>
        <?php endif; ?>
    </tr>
<?php endforeach; ?>

                </tbody>
            </table>
        </form>

        <button class="back-button" onclick="window.history.back()">Back</button>
    </div>
</body>
</html>
