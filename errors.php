<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - TripSplit</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #ff6b6b, #f7b733); /* Match the dashboard background */
            color: white;
            height: 100vh; 
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .error-container {
            text-align: center;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            position: relative;
            z-index: 1; /* Ensure this is above the animation */
        }
        .error-container h1 {
            margin-bottom: 20px;
        }
        .back-button {
            background: #f7b733;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #ff6b6b;
            color: white;
        }
        .logo {
            width: 100px; 
            margin-bottom: 20px;
            border-radius: 15px;
        }
        
        .ball {
            width: 30px; /* Ball size */
            height: 30px; /* Ball size */
            border-radius: 50%; /* Circle shape */
            display: inline-block; /* Allow them to be inline */
            margin: 0 5px; /* Spacing between balls */
            position: relative; /* Needed for animation */
            animation: bounce 0.8s infinite alternate; /* Combine bounce animation */
        }
        .ball:nth-child(1) {
            background-color: red; /* First ball color */
            animation-delay: 0s; /* Start immediately */
        }
        .ball:nth-child(2) {
            background-color: orange; /* Second ball color */
            animation-delay: 0.2s; /* Start slightly later */
        }
        .ball:nth-child(3) {
            background-color: yellow; /* Third ball color */
            animation-delay: 0.4s; /* Start slightly later */
        }

        @keyframes bounce {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-30px); /* Increased bounce height */
            }
        }

        .contact-message {
            margin-top: 20px;
            color: white;
        }
        .timer {
            margin-top: 15px;
            font-size: 18px;
            color: yellow; /* Timer color */
        }
    </style>
    <script>
        // Countdown timer function
        let timeLeft = 30; // Countdown time in seconds
        function startCountdown() {
            const timerElement = document.getElementById('timer');
            const countdown = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'login.php'; // Redirect to login page
                } else {
                    timerElement.textContent = `Redirecting in ${timeLeft} seconds...`;
                    timeLeft--;
                }
            }, 1000);
        }

        // Start the countdown on page load
        window.onload = startCountdown;
    </script>
</head>
<body>
    <div class="error-container">
        <img src="logo.png" alt="TripSplit Logo" class="logo"> <!-- Display the logo -->
        <h1>Oops! Something went wrong.</h1>
        <p>Please try again later.</p><br>
        
        <!-- Bouncing Colored Balls -->
        <div class="balls-container">
            <div class="ball"></div>
            <div class="ball"></div>
            <div class="ball"></div>
        </div>

        <br>
        <button class="back-button" onclick="window.location.href='login.php'">Go to Login</button>
        
        <div class="timer" id="timer">Redirecting in 30 seconds...</div> <!-- Timer Display -->
        
        <div class="contact-message">
            <p>If you are encountering this error many times, contact the developer: <a href="https://naveenshankar.in/#footer" target="_blank" style="color: #f7b733;">Naveen Shankar</a></p>
        </div>
    </div>
</body>
</html>
