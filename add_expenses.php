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

// Retrieve trip participants from the database
$query = "
    SELECT p.name, p.username 
    FROM participants p 
    WHERE p.trip_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$participants_result = $stmt->get_result();
$participants = [];
while ($row = $participants_result->fetch_assoc()) {
    $participants[] = $row; // Store participant names and usernames
}

// Handle form submission for adding expenses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_type = htmlspecialchars($_POST['expense_type']);
    $total_amount = htmlspecialchars($_POST['total_amount']);
    $paid_by = htmlspecialchars($_POST['paid_by']);
    $included_users = $_POST['included_users'] ?? [];

    // Check if 'Other' is selected
    if ($expense_type === 'Other') {
        $expense_type = htmlspecialchars($_POST['other_expense']);
    }

    // Insert expense into the database
    $insert_query = "
        INSERT INTO expenses (trip_id, expense_type, amount, paid_by, created_by) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issss", $trip_id, $expense_type, $total_amount, $paid_by, $username);
    
    if ($stmt->execute()) {
        $expense_id = $stmt->insert_id; // Get the inserted expense ID
        
        // Calculate share for each included participant and insert into expense_shares
        $num_included = count($included_users);
        $share_per_person = $num_included > 0 ? $total_amount / $num_included : 0;

        // Format share_per_person to 2 decimal places as a float
        $share_per_person = number_format($share_per_person, 2, '.', '');

        foreach ($included_users as $included_user) {
            // Store the participant name instead of the username
            $participant_name = htmlspecialchars($included_user);
            $stmt = $conn->prepare("
                INSERT INTO expense_shares (expense_id, participant_name, share) 
                VALUES (?, ?, ?)
            ");
            // Bind as string to store formatted decimal
            $stmt->bind_param("ssd", $expense_id, $participant_name, $share_per_person);
            $stmt->execute();
        }

        echo "<script>alert('Expense added successfully!'); window.location.href = 'add_expenses.php?trip_id=$trip_id&username=$username';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to add expense. Please try again.');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expenses - TripSplit</title>
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
            overflow: auto; /* Enables scrolling */
            max-height: 100vh; /* Limits the height of the container */
        }

        input, select {
            width: calc(100% - 22px); /* Ensures all input fields are of the same size */
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: black; /* Ensures text is visible */
            box-sizing: border-box; /* Ensures padding is included in total width */
        }

        input::placeholder {
            color: #aaa; /* Placeholder color */
        }

        button {
            width: 48%; /* Makes buttons half width */
            padding: 10px;
            margin: 10px 1%;
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

        .toggle {
            appearance: none;
            width: 40px;
            height: 20px;
            background: gray;
            border-radius: 10px;
            position: relative;
            outline: none;
            cursor: pointer;
        }

        .toggle:checked {
            background: green;
        }

        .toggle:before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            left: 0;
            bottom: 0;
            background: white;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .toggle:checked:before {
            transform: translateX(20px);
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
        }

        th {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Expense</h1>
        <form method="POST">
            <label for="expense_type">Expense Type:</label>
            <select name="expense_type" id="expense_type" onchange="toggleOtherInput()">
                <option value="Food">Food</option>
                <option value="Travel">Travel</option>
                <option value="Stay">Stay</option>
                <option value="Entry Tickets">Entry Tickets</option>
                <option value="Other">Other</option>
            </select>

            <div id="other-input" style="display: none;">
                <input type="text" name="other_expense" id="other_expense" placeholder="Please specify">
            </div>

            <label for="total_amount">Total Amount:</label>
            <input type="number" name="total_amount" id="total_amount" placeholder="Enter total amount" required>

            <label for="paid_by">Paid By:</label>
            <select name="paid_by" id="paid_by">
                <?php foreach ($participants as $participant): ?>
                    <option value="<?php echo htmlspecialchars($participant['name']); ?>">
                        <?php echo htmlspecialchars($participant['name']) . " (" . htmlspecialchars($participant['username']) . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <h3>Persons Included:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Include</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Select All</strong></td>
                        <td><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></td>
                    </tr>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($participant['name']) . " (" . htmlspecialchars($participant['username']) . ")"; ?></td>
                            <td>
                                <label>
                                    <input type="checkbox" name="included_users[]" value="<?php echo htmlspecialchars($participant['name']); ?>" class="toggle"> <!-- Store the name instead of username -->
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" onclick="window.history.back()">Back</button>
            <button type="submit">Add</button>
        </form>
    </div>

    <script>
        function toggleOtherInput() {
            const expenseType = document.getElementById('expense_type').value;
            document.getElementById('other-input').style.display = expenseType === 'Other' ? 'block' : 'none';
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('input[name="included_users[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        }
    </script>
</body>
</html>
