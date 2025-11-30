<?php
// =============================================
// VALIDATION FUNCTIONS
// =============================================

/**
 * Validate first name and last name
 */
function validateName($name, $fieldName) {
    if (empty($name)) {
        return "$fieldName is required";
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\-\s]{2,50}$/u', $name)) {
        return "$fieldName invalid: must be 2-50 characters, only letters, spaces and hyphens allowed";
    }
    return null;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    if (empty($email)) {
        return "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Email is not valid";
    }
    return null;
}

/**
 * Validate CIN number (8 digits starting with 0 or 1)
 */
function validateCIN($cin) {
    if (!empty($cin) && !preg_match('/^[01][0-9]{7}$/', $cin)) {
        return "CIN must be exactly 8 digits starting with 0 or 1";
    }
    return null;
}

/**
 * Validate phone number (8 digits starting with 2,3,4,5 or 9)
 */
function validatePhone($phone) {
    if (!empty($phone) && !preg_match('/^[23459][0-9]{7}$/', $phone)) {
        return "Phone number must be exactly 8 digits starting with 2,3,4,5 or 9";
    }
    return null;
}

/**
 * Validate gender selection
 */
function validateGender($gender) {
    $allowedGenders = ['male', 'female'];
    if (!empty($gender) && !in_array($gender, $allowedGenders)) {
        return "Selected gender is not valid";
    }
    return null;
}

/**
 * Validate password strength and confirmation
 */
function validatePassword($password, $confirmPassword) {
    $errors = [];
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } else {
        if (strlen($password) < 8) {
            $errors[] = "Password must contain at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    return $errors;
}

// =============================================
// DATABASE FUNCTIONS
// =============================================

/**
 * Establish database connection
 */
function connectToDatabase() {
    $server = "localhost";
    $username = "root";
    $password = "";
    
    $mysqli = mysqli_connect($server, $username, $password);
    if (!$mysqli) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // Select database
    if (!mysqli_select_db($mysqli, "covoiturage")) {
        die("Database not found: " . mysqli_error($mysqli));
    }
    
    echo "Database connection successful<br>";
    return $mysqli;
}

/**
 * Check for duplicate values in database
 */
function checkAllDuplicates($mysqli, $cin, $email, $phone) {
    $sql = "SELECT 
        SUM(cin = ?) as cin_count,
        SUM(email = ?) as email_count, 
        SUM(phone = ?) as phone_count
    FROM users";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $cin, $email, $phone);
    $stmt->execute();
    $stmt->bind_result($cin_count, $email_count, $phone_count);
    $stmt->fetch();
    $stmt->close();
    
    $errors = [];
    if ($cin_count > 0) $errors[] = "CIN already exists";
    if ($email_count > 0) $errors[] = "Email already registered"; 
    if ($phone_count > 0) $errors[] = "Phone number already registered";
    
    return $errors;
}

/**
 * Insert new user into database
 */
function insertUser($mysqli, $userData) {
    $sql = "INSERT INTO users (cin, email, firstName, lastName, gender, password, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "sssssss", 
        $userData['cin'],
        $userData['email'],
        $userData['firstName'],
        $userData['lastName'],
        $userData['gender'],
        $userData['password_hash'],
        $userData['phone']
    );
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// =============================================
// MAIN LOGIC
// =============================================

// If the script is reached without POST (GET), redirect to the HTML registration form
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: inscription.html');
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $userData = [
        'firstName' => $_POST['firstName'] ?? '',
        'lastName' => $_POST['lastName'] ?? '',
        'email' => $_POST['email'] ?? '',
        'cin' => $_POST['cin'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirmPassword'] ?? ''
    ];
    
    // Array to store validation errors
    $errors = [];
    
    // FIELD VALIDATIONS
    // Validate first name
    if ($error = validateName($userData['firstName'], "First name")) {
        $errors['firstName'] = $error;
    }
    
    // Validate last name
    if ($error = validateName($userData['lastName'], "Last name")) {
        $errors['lastName'] = $error;
    }
    
    // Validate email
    if ($error = validateEmail($userData['email'])) {
        $errors['email'] = $error;
    }
    
    // Validate CIN
    if ($error = validateCIN($userData['cin'])) {
        $errors['cin'] = $error;
    }
    
    // Validate phone
    if ($error = validatePhone($userData['phone'])) {
        $errors['phone'] = $error;
    }
    
    // Validate gender
    if ($error = validateGender($userData['gender'])) {
        $errors['gender'] = $error;
    }
    
    // Validate password
    $passwordErrors = validatePassword($userData['password'], $userData['confirmPassword']);
    if (!empty($passwordErrors)) {
        $errors['password'] = implode(', ', $passwordErrors);
    }
    
    // PROCESS IF NO ERRORS
    if (empty($errors)) {
        try {
            // Connect to database
            $mysqli = connectToDatabase();
            
            // Check for duplicate values
            $duplicateErrors = checkAllDuplicates($mysqli, $userData['cin'], $userData['email'], $userData['phone']);
            if (!empty($duplicateErrors)) {
                throw new Exception(implode(', ', $duplicateErrors));
            }
            
            // Hash password for security
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Insert user into database
            if (insertUser($mysqli, $userData)) {
                // SUCCESS MESSAGE
                echo "<div style='color: green; border: 1px solid green; padding: 10px; margin: 10px 0;'>";
                echo "<h3>✅ User created successfully!</h3>";
                echo "<p>Hello " . htmlspecialchars($userData['firstName'] ?: $userData['email']) . ".</p>";
                echo "<ul>";
                echo "<li>First Name: " . htmlspecialchars($userData['firstName']) . "</li>";
                echo "<li>Last Name: " . htmlspecialchars($userData['lastName']) . "</li>";
                echo "<li>CIN: " . htmlspecialchars($userData['cin']) . "</li>";
                echo "<li>Email: " . htmlspecialchars($userData['email']) . "</li>";
                echo "<li>Phone: " . htmlspecialchars($userData['phone']) . "</li>";
                echo "<li>Gender: " . htmlspecialchars($userData['gender']) . "</li>";
                echo "</ul>";
                
                // Mask password for display
                $masked = str_repeat('*', max(4, min(12, strlen($userData['password']))));
                echo "<p>Password: $masked (length: " . strlen($userData['password']) . " characters)</p>";
                echo "</div>";
                
            } else {
                throw new Exception("Error inserting into database");
            }
            
            // Close database connection
            mysqli_close($mysqli);
            
        } catch (Exception $e) {
            // ERROR HANDLING
            echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        
    } else {
        // DISPLAY VALIDATION ERRORS
        echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 10px 0;'>";
        echo "<h3>Validation errors:</h3>";
        echo "<ul>";
        foreach ($errors as $field => $error) {
            echo "<li><strong>" . htmlspecialchars($field) . ":</strong> " . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}
?>