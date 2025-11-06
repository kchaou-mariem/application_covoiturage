<?php
// =============================================
// AUTHENTICATION FUNCTIONS
// =============================================

/**
 * Validate login form data
 */
function validateLoginForm($email, $password) {
    $errors = [];
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address";
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }
    
    return $errors;
}

/**
 * Connect to database
 */
function connectToDatabase() {
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "covoiturages";
    
    $mysqli = mysqli_connect($server, $username, $password);
    if (!$mysqli) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    if (!mysqli_select_db($mysqli, $database)) {
        throw new Exception("Database not found: " . mysqli_error($mysqli));
    }
    
    return $mysqli;
}

/**
 * Authenticate user credentials
 */
function authenticateUser($mysqli, $email, $password) {
    $sql = "SELECT cin, email, password, firstName, lastName FROM users WHERE email = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    // Check if user exists
    if ($stmt->num_rows === 0) {
        $stmt->close();
        return [
            'success' => false,
            'message' => "Invalid email or password"
        ];
    }
    
    // Get user data
    $stmt->bind_result($id, $db_email, $db_password, $firstName, $lastName);
    $stmt->fetch();
    $stmt->close();
    
    // Verify password
    if (password_verify($password, $db_password)) {
        return [
            'success' => true,
            'user' => [
                'id' => $id,
                'email' => $db_email,
                'firstName' => $firstName,
                'lastName' => $lastName
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => "Invalid email or password"
        ];
    }
}

/**
 * Start user session
 */
/*
function startUserSession($userData) {
    session_start();
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_name'] = $userData['firstName'] . ' ' . $userData['lastName'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
}
*/

// =============================================
// MAIN AUTHENTICATION LOGIC
// =============================================

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Step 1: Validate form data
    $validationErrors = validateLoginForm($email, $password);
    
    if (!empty($validationErrors)) {
        // Display validation errors
        echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
        echo "<h3>Login errors:</h3>";
        echo "<ul>";
        foreach ($validationErrors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        
    } else {
        try {
            // Step 2: Connect to database
            $mysqli = connectToDatabase();
            
            // Step 3: Authenticate user
            $authResult = authenticateUser($mysqli, $email, $password);
            
            if ($authResult['success']) {
                /*
                // Step 4: Start session
                startUserSession($authResult['user']);
                */

                // Step 5: Success message and redirect
                echo "<div style='color: green; border: 1px solid green; padding: 10px; margin: 10px 0;'>";
                echo "<h3>✅ Login successful!</h3>";
                echo "<p>Welcome back, " . htmlspecialchars($authResult['user']['firstName']) . "!</p>";
                echo "<p>Redirecting to your dashboard...</p>";
                echo "</div>";
                
                // Redirect to dashboard (in a real application)
                // header('Location: dashboard.php');
                // exit;
                
            } else {
                // Authentication failed
                echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
                echo "<h3>❌ Login failed</h3>";
                echo "<p>" . htmlspecialchars($authResult['message']) . "</p>";
                echo "</div>";
            }
            
            // Close database connection
            mysqli_close($mysqli);
            
        } catch (Exception $e) {
            // Handle database errors
            echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
            echo "<h3>❌ System error</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Please try again later.</p>";
            echo "</div>";
        }
    }
    
} else {
    // If not a POST request, show login form message
    echo "<div style='color: blue; border: 1px solid blue; padding: 10px; margin: 10px 0;'>";
    echo "<p>Please submit the login form.</p>";
    echo "</div>";
}

// =============================================
// SECURITY DISPLAY (for demonstration)
// =============================================

// Only for demonstration - don't show this in production
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff;'>";
    echo "<h4>Security Information:</h4>";
    echo "<p><strong>Email received:</strong> " . htmlspecialchars($email) . "</p>";
    echo "<p><strong>Password length:</strong> " . strlen($password) . " characters</p>";
    echo "<p><strong>Safe display:</strong> " . htmlspecialchars($email) . "</p>";
    echo "</div>";
}
?>