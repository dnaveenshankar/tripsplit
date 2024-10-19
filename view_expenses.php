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

// Retrieve expenses for the trip
$expenses_query = "
    SELECT e.id, e.expense_type, e.amount, e.paid_by 
    FROM expenses e 
    WHERE e.trip_id = ?
";
$stmt = $conn->prepare($expenses_query);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$expenses_result = $stmt->get_result();

// Prepare an array to store participant shares
$participant_shares = [];
$total_expense = 0;

// Retrieve participants for the trip
$participants_query = "
    SELECT p.name 
    FROM participants p 
    WHERE p.trip_id = ?
";
$participants_stmt = $conn->prepare($participants_query);
$participants_stmt->bind_param("i", $trip_id);
$participants_stmt->execute();
$participants_result = $participants_stmt->get_result();

$participants = [];
while ($row = $participants_result->fetch_assoc()) {
    $participants[] = htmlspecialchars($row['name']);
}

// Fetch all expenses and their shares
while ($expense = $expenses_result->fetch_assoc()) {
    $total_expense += $expense['amount'];
    
    // Get shares for this expense
    $shares_query = "
        SELECT es.participant_name, es.share 
        FROM expense_shares es 
        WHERE es.expense_id = ?
    ";
    $shares_stmt = $conn->prepare($shares_query);
    $shares_stmt->bind_param("i", $expense['id']);
    $shares_stmt->execute();
    $shares_result = $shares_stmt->get_result();

    while ($share = $shares_result->fetch_assoc()) {
        $participant_name = htmlspecialchars($share['participant_name']);
        $share_amount = (float)$share['share'];

        if (!isset($participant_shares[$participant_name])) {
            $participant_shares[$participant_name] = ['share' => 0, 'expenses' => []];
        }
        
        $participant_shares[$participant_name]['share'] += $share_amount;
        $participant_shares[$participant_name]['expenses'][] = htmlspecialchars($expense['expense_type']);
    }

    // Close the shares statement to avoid "commands out of sync" error
    $shares_stmt->close();
}

// Calculate totals for each participant
$participants_totals = [];
foreach ($participants as $participant_name) {
    $participants_totals[$participant_name] = $participant_shares[$participant_name]['share'] ?? 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses - TripSplit</title>
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
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 20px;
            max-width: 800px;
            margin: auto;
            position: relative; 
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: rgba(255, 255, 255, 0.1);
        }

        button {
            padding: 10px 20px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            background: #f7b733;
            color: black;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #ff6b6b;
            color: white;
        }

        .settlement-button {
            background: green;
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
    <button class="settlement-button" onclick="window.location.href='settlement.php?trip_id=<?php echo $trip_id; ?>&username=<?php echo $username; ?>'">Settlement</button>

        
        <h1>View Expenses</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Expense Type</th>
                    <?php foreach ($participants as $participant_name): ?>
                        <th><?php echo htmlspecialchars($participant_name); ?></th>
                    <?php endforeach; ?>
                    <th>Expense</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Reset the pointer of the expenses result
                $expenses_result->data_seek(0);
                
                foreach ($expenses_result as $expense):
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($expense['expense_type']) . "</td>";

                    foreach ($participants as $participant_name) {
                        // Get share for each participant, if exists, else show 0
                        $share = $participant_shares[$participant_name]['share'] ?? 0;
                        echo "<td>" . htmlspecialchars($share) . "</td>";
                    }
                    echo "<td>" . htmlspecialchars($expense['amount']) . "</td>";
                    echo "</tr>";
                endforeach;
                ?>
                <tr>
                    <td><strong>Total</strong></td>
                    <?php foreach ($participants_totals as $total): ?>
                        <td><?php echo htmlspecialchars($total); ?></td>
                    <?php endforeach; ?>
                    <td><strong><?php echo htmlspecialchars($total_expense); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <button onclick="window.history.back()">Back</button>
    </div>
</body>
</html>
