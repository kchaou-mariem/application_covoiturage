<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_cin']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: authentification.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        // Création d'un nouvel utilisateur
        $cin = $_POST['cin'];
        $email = $_POST['email'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (cin, email, firstName, lastName, phone, gender, role, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->bind_param("ssssssss", $cin, $email, $firstName, $lastName, $phone, $gender, $role, $password);
        
        if ($stmt->execute()) {
            $message = "User created successfully";
        } else {
            $error = "Error creating user: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && isset($_POST['user_cin'])) {
        $cin = $_POST['user_cin'];
        $action = $_POST['action'];
        
        if ($action === 'block') {
            $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE cin = ?");
            $stmt->bind_param("s", $cin);
            $stmt->execute();
            $message = "User blocked successfully";
        } elseif ($action === 'unblock') {
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE cin = ?");
            $stmt->bind_param("s", $cin);
            $stmt->execute();
            $message = "User unblocked successfully";
        } elseif ($action === 'delete') {
            // Supprimer d'abord les réservations
            $stmt = $conn->prepare("DELETE FROM booking WHERE cinRequester = ?");
            $stmt->bind_param("s", $cin);
            $stmt->execute();
            
            // Supprimer les trajets
            $stmt = $conn->prepare("DELETE FROM journey WHERE cinRequester = ?");
            $stmt->bind_param("s", $cin);
            $stmt->execute();
            
            // Supprimer l'utilisateur
            $stmt = $conn->prepare("DELETE FROM users WHERE cin = ?");
            $stmt->bind_param("s", $cin);
            $stmt->execute();
            $message = "User deleted successfully";
        }
    }
}

// Récupérer tous les utilisateurs
$users = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT j.idJourney) as journey_count,
           COUNT(DISTINCT b.idBooking) as booking_count
    FROM users u
    LEFT JOIN journey j ON u.cin = j.cinRequester
    LEFT JOIN booking b ON u.cin = b.cinRequester
    WHERE u.role != 'admin'
    GROUP BY u.cin
    ORDER BY u.cin DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Users Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="includes/styles.css">
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="container-fluid mt-0">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar-admin">
            <div class="sidebar-sticky pt-3">
                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                    <span>Admin Panel</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_users.php">
                            <i class="fas fa-users me-2"></i>
                            Users Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_journeys.php">
                            <i class="fas fa-route me-2"></i>
                            Journeys Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_bookings.php">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Bookings Management
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Users Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-plus me-2"></i>Create New User
                </button>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>CIN</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Gender</th>
                                    <th>Journeys</th>
                                    <th>Bookings</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['cin']) ?></td>
                                    <td><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($user['gender'] === 'male'): ?>
                                            <i class="fas fa-mars text-primary"></i> Male
                                        <?php else: ?>
                                            <i class="fas fa-venus text-danger"></i> Female
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= $user['journey_count'] ?></span></td>
                                    <td><span class="badge bg-success"><?= $user['booking_count'] ?></span></td>
                                    <td>
                                        <?php 
                                        $status = $user['status'] ?? 'active';
                                        if ($status === 'blocked'): 
                                        ?>
                                            <span class="badge bg-danger">Blocked</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($status !== 'blocked'): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Block this user?')">
                                                <input type="hidden" name="user_cin" value="<?= $user['cin'] ?>">
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Block">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_cin" value="<?= $user['cin'] ?>">
                                                <input type="hidden" name="action" value="unblock">
                                                <button type="submit" class="btn btn-sm btn-success" title="Unblock">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This will also delete all their journeys and bookings!')">
                                                <input type="hidden" name="user_cin" value="<?= $user['cin'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal pour créer un utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cin" class="form-label">CIN *</label>
                            <input type="text" class="form-control" name="cin" pattern="[01][0-9]{7}" title="8 digits starting with 0 or 1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="firstName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="lastName" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone *</label>
                            <input type="tel" class="form-control" name="phone" pattern="[23459][0-9]{7}" title="8 digits starting with 2,3,4,5 or 9" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender *</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" value="male" required>
                                    <label class="form-check-label"><i class="fas fa-mars text-primary"></i> Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" value="female" required>
                                    <label class="form-check-label"><i class="fas fa-venus text-danger"></i> Female</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role" value="user" checked required>
                                    <label class="form-check-label"><i class="fas fa-user"></i> User</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="role" value="admin" required>
                                    <label class="form-check-label"><i class="fas fa-user-shield"></i> Admin</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sidebar-admin {
    position: sticky;
    top: 0;
    height: 100vh;
    padding-top: 1rem;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    overflow-y: auto;
    z-index: 1;
}

.sidebar-admin .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.75rem 1rem;
}

.sidebar-admin .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar-admin .nav-link:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.site-header {
    z-index: 100;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
