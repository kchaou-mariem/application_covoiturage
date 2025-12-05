<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_cin']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: authentification.php');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $bookingId = $_POST['booking_id'];
    
    // Supprimer la réservation
    $stmt = $conn->prepare("DELETE FROM booking WHERE idBooking = ?");
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    
    $message = "Booking deleted successfully";
}

// Récupérer toutes les réservations
$bookings = $conn->query("
    SELECT b.*, 
           u.firstName, u.lastName, u.email, u.phone,
           c1.name AS dep_city, c2.name AS dest_city,
           j.depDate, j.depTime, j.price,
           driver.firstName AS driver_first, driver.lastName AS driver_last
    FROM booking b
    JOIN users u ON b.cinRequester  = u.cin
    JOIN journey j ON b.idJourney = j.idJourney
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    JOIN users driver ON j.cinRequester = driver.cin
    ORDER BY b.bookingDate DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Bookings Management</title>
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
                        <a class="nav-link" href="admin_users.php">
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
                        <a class="nav-link active" href="admin_bookings.php">
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
                <h1 class="h2">Bookings Management</h1>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Bookings</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Passenger</th>
                                    <th>Journey</th>
                                    <th>Driver</th>
                                    <th>Date & Time</th>
                                    <th>Seats</th>
                                    <th>Total Price</th>
                                    <th>Booking Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= $booking['idBooking'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']) ?>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['phone']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($booking['dep_city']) ?></strong>
                                        <i class="fas fa-arrow-right mx-1"></i>
                                        <strong><?= htmlspecialchars($booking['dest_city']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($booking['driver_first'] . ' ' . $booking['driver_last']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($booking['depDate'])) ?>
                                        <br>
                                        <small><?= substr($booking['depTime'], 0, 5) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $booking['requestedSeats'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= ($booking['requestedSeats'] * $booking['price']) ?> DT</strong>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($booking['bookingDate'])) ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this booking?')">
                                            <input type="hidden" name="booking_id" value="<?= $booking['idBooking'] ?>">
                                            <input type="hidden" name="delete_booking" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
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
