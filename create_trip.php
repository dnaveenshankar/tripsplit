<?php
// Start session to manage user sessions
session_start();

// Include database connection
include 'db_connection.php';

// Check if username is set in the session or passed in the URL
if (isset($_GET['username'])) {
    $username = htmlspecialchars($_GET['username']);
} else {
    header("Location: login.php"); // Redirect to login if username is not provided
    exit();
}

$query = "SELECT username, name FROM users"; // Adjust table name as needed
$result = $conn->query($query);
$usernames = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usernames[] = [
            'username' => $row['username'],
            'name' => $row['name'] ?? 'No Name Provided', // Default value if name is not present
        ];
    }
} else {
    echo "<script>alert('No users found in the database. Please add users.');</script>";
}

// Handle form submission
$errorMessages = []; // Initialize an array for error messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_name = htmlspecialchars($_POST['trip_name']);
    $date_from = htmlspecialchars($_POST['date_from']);
    $date_to = htmlspecialchars($_POST['date_to']);
    $number_of_persons = intval($_POST['number_of_persons']);
    $valid_usernames = array_column($usernames, 'username'); // Get valid usernames

    // Collect names and usernames entered
    $participants = [];
    for ($i = 1; $i <= $number_of_persons; $i++) {
        $name = htmlspecialchars($_POST["name_$i"]);
        $username_input = htmlspecialchars($_POST["username_$i"]) ?: null; // Set to null if not provided
        
        // Validate username
        if ($username_input && !in_array($username_input, $valid_usernames)) {
            $errorMessages[$i] = "Invalid username: $username_input. Please choose a valid username.";
        } else {
            $participants[] = ['name' => $name, 'username' => $username_input];
        }
    }

    // Check date validation
    if ($date_from > $date_to) {
        $errorMessages['date'] = "The 'Date To' must be after 'Date From'.";
    }

    // If there are no errors, proceed with the trip creation
    if (empty($errorMessages)) {
        // Insert trip into database
        $insert_trip_query = "INSERT INTO trips (trip_name, date_from, date_to, number_of_persons, status, created_by) VALUES (?, ?, ?, ?, 'on', ?)";
        $stmt_trip = $conn->prepare($insert_trip_query);
        $stmt_trip->bind_param("sssis", $trip_name, $date_from, $date_to, $number_of_persons, $username);
        
        if ($stmt_trip->execute()) {
            $trip_id = $stmt_trip->insert_id; // Get the ID of the inserted trip

            // Insert participants into database
            foreach ($participants as $participant) {
                $insert_participant_query = "INSERT INTO participants (trip_id, name, username) VALUES (?, ?, ?)";
                $stmt_participant = $conn->prepare($insert_participant_query);
                $stmt_participant->bind_param("iss", $trip_id, $participant['name'], $participant['username']);
                $stmt_participant->execute();
            }

            // Trip and participants created successfully
            echo "<script>
                    alert('Trip created successfully!');
                    setTimeout(function() {
                        window.location.href = 'dashboard.php?username=" . urlencode($username) . "&name=" . urlencode($_SESSION['name']) . "';
                    }, 3000); // Redirect after 3 seconds
                  </script>";
        } else {
            // Redirect to errors page for unhandled error
            header("Location: errors.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Trip - TripSplit</title>
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
            max-width: 600px;
            margin: auto;
            overflow: auto;
            max-height: 100vh; 
        }
        input {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            border: none;
            width: calc(100% - 22px);
        }
        .dynamic-input {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
            position: relative;
        }
        .suggestions {
            position: absolute;
            background: white;
            color: black;
            border-radius: 5px;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            width: calc(100% - 22px);
            display: none; /* Initially hidden */
        }
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
        }
        .suggestion-item:hover {
            background: #f0f0f0;
        }
        .back-button {
            margin-top: 20px;
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-button:hover {
            background: #e55d5d;
        }
        .validation-message {
            color: red;
            font-size: 0.8em;
            margin-top: 5px;
        }
    </style>
    <script>
        const usernames = <?php echo json_encode($usernames); ?>; // Pass PHP data to JavaScript

        function addUsernameFields() {
            const numberOfPersons = document.getElementById('number_of_persons').value;
            const container = document.getElementById('dynamic_fields');
            container.innerHTML = ''; // Clear previous fields

            for (let i = 1; i <= numberOfPersons; i++) {
                const inputDiv = document.createElement('div');
                inputDiv.className = 'dynamic-input';
                
                // Create a unique identifier for error messages
                const errorId = `error_${i}`;

                inputDiv.innerHTML = `
                    <input type="text" name="name_${i}" placeholder="Name ${i}" required>
                    <input type="text" name="username_${i}" placeholder="Username ${i} (optional)" oninput="showSuggestions(this, ${i})" autocomplete="off">
                    <div class="suggestions" id="suggestions_${i}"></div>
                    <div class="validation-message" id="${errorId}"></div>
                `;
                container.appendChild(inputDiv);
            }
        }

        function showSuggestions(input, index) {
            const suggestionsContainer = document.getElementById(`suggestions_${index}`);
            const inputValue = input.value.toLowerCase();
            suggestionsContainer.innerHTML = ''; // Clear previous suggestions

            if (inputValue) {
                const filteredUsernames = usernames.filter(user => 
                    user.username.toLowerCase().includes(inputValue)
                );

                if (filteredUsernames.length > 0) {
                    suggestionsContainer.style.display = 'block'; // Show suggestions

                    filteredUsernames.forEach(user => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'suggestion-item';
                        suggestionItem.innerHTML = `${user.username} (${user.name})`;
                        suggestionItem.onclick = function() {
                            input.value = user.username; // Set input value to the selected username
                            suggestionsContainer.innerHTML = ''; // Clear suggestions
                            suggestionsContainer.style.display = 'none'; // Hide suggestions
                        };
                        suggestionsContainer.appendChild(suggestionItem);
                    });
                } else {
                    suggestionsContainer.style.display = 'none'; // Hide if no suggestions
                }
            } else {
                suggestionsContainer.style.display = 'none'; // Hide if input is empty
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Create Trip</h2>
        <form method="POST" action="">
            <input type="text" name="trip_name" placeholder="Trip Name" required>
            <label for="date_from">Date From:</label>
            <input type="date" id="date_from" name="date_from" required>
            <label for="date_to">Date To:</label>
            <input type="date" id="date_to" name="date_to" required>
            <input type="number" id="number_of_persons" name="number_of_persons" min="1" oninput="addUsernameFields()" placeholder="Number of Persons" required>
            <div id="dynamic_fields"></div>
            <button type="submit" class="back-button">Create Trip</button>
            <button type="button" class="back-button" onclick="window.location.href='dashboard.php?username=<?php echo urlencode($username); ?>'">Back to Dashboard</button>
        </form>

        <!-- Display validation messages -->
        <?php if (!empty($errorMessages)): ?>
            <div class="validation-message">
                <?php foreach ($errorMessages as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

