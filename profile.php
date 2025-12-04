<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.php');
    exit();
}

$cin = $_SESSION['user_cin'];
$message = '';
$error = '';

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT cin, email, firstName, lastName, gender, phone FROM users WHERE cin = ?");
$stmt->bind_param("s", $cin);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: logout.php');
    exit();
}

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($phone) && !preg_match('/^[23459][0-9]{7}$/', $phone)) {
        $error = "Phone number must be exactly 8 digits starting with 2,3,4,5 or 9.";
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!empty($newPassword) && strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $checkStmt = $conn->prepare("SELECT cin FROM users WHERE email = ? AND cin != ?");
        $checkStmt->bind_param("ss", $email, $cin);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "This email is already used by another user.";
        } else {
            // Mise à jour des informations
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ?, gender = ?, password = ? WHERE cin = ?");
                $updateStmt->bind_param("sssssss", $firstName, $lastName, $email, $phone, $gender, $hashedPassword, $cin);
            } else {
                $updateStmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ?, gender = ? WHERE cin = ?");
                $updateStmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $gender, $cin);
            }
            
            if ($updateStmt->execute()) {
                $message = "Profile updated successfully!";
                $_SESSION['user_firstName'] = $firstName;
                $_SESSION['user_lastName'] = $lastName;
                // Recharger les données
                $user['firstName'] = $firstName;
                $user['lastName'] = $lastName;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['gender'] = $gender;
            } else {
                $error = "Error updating profile.";
            }
            $updateStmt->close();
        }
        $checkStmt->close();
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container my-4">
    <div class="card profile-card">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">
                <i class="fas fa-user-cog"></i> Personal Information
            </h2>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-check-circle"></i> Success:</strong> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-card"></i> CIN
                        </label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['cin']) ?>" disabled>
                        <small class="text-muted">CIN cannot be modified</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-envelope"></i> Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user"></i> First Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="firstName" value="<?= htmlspecialchars($user['firstName']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user"></i> Last Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="lastName" value="<?= htmlspecialchars($user['lastName']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-phone"></i> Phone
                        </label>
                        <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="93492906">
                        <small class="text-muted">8 digits starting with 2,3,4,5 or 9</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-venus-mars"></i> Gender
                        </label>
                        <select class="form-select" name="gender">
                            <option value="">Select gender</option>
                            <option value="male" <?= ($user['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($user['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                
                <h5 class="mb-3">
                    <i class="fas fa-lock"></i> Change Password
                    <small class="text-muted">(Optional)</small>
                </h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">New Password</label>
                        <input type="password" class="form-control" name="newPassword" placeholder="Leave blank to keep current password">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirmPassword" placeholder="Confirm new password">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="trajets.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
