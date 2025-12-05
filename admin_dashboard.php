<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_cin']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: authentification.php');
    exit();
}

// Récupérer les statistiques
$stats = [];

// Nombre total d'utilisateurs
$query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$stats['total_users'] = $query->fetch_assoc()['total'];

// Nombre total de trajets
$query = $conn->query("SELECT COUNT(*) as total FROM journey");
$stats['total_journeys'] = $query->fetch_assoc()['total'];

// Nombre total de réservations
$query = $conn->query("SELECT COUNT(*) as total FROM booking");
$stats['total_bookings'] = $query->fetch_assoc()['total'];

// Revenus totaux (simulation - somme des prix)
$query = $conn->query("SELECT COALESCE(SUM(totalPrice), 0) as total FROM booking");
$stats['total_revenue'] = $query->fetch_assoc()['total'];

// Trajets récents
$recent_journeys = $conn->query("
    SELECT j.*, 
           c1.name AS dep_city, c2.name AS dest_city,
           u.firstName, u.lastName
    FROM journey j
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    JOIN users u ON j.cinRequester = u.cin
    ORDER BY j.idJourney DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Réservations récentes
$recent_bookings = $conn->query("
    SELECT b.*, 
           u.firstName, u.lastName,
           j.depDate, j.depTime,
           c1.name AS dep_city, c2.name AS dest_city
    FROM booking b
    JOIN users u ON b.cinRequester = u.cin
    JOIN journey j ON b.idJourney = j.idJourney
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    ORDER BY b.idBooking DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard</title>
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
                        <a class="nav-link active" href="admin_dashboard.php">
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
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar me-1"></i>
                            Today
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Users
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $stats['total_users'] ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Journeys
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $stats['total_journeys'] ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-route fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Bookings
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $stats['total_bookings'] ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-ticket-alt fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Revenue
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= number_format($stats['total_revenue'], 2) ?> DT
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Journeys -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Journeys</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Driver</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Date</th>
                                            <th>Seats</th>
                                            <th>Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_journeys as $journey): ?>
                                        <tr>
                                            <td><?= $journey['idJourney'] ?></td>
                                            <td><?= htmlspecialchars($journey['firstName'] . ' ' . $journey['lastName']) ?></td>
                                            <td><?= htmlspecialchars($journey['dep_city']) ?></td>
                                            <td><?= htmlspecialchars($journey['dest_city']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($journey['depDate'])) ?></td>
                                            <td><?= $journey['nbSeats'] ?></td>
                                            <td><?= $journey['price'] ?> DT</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Recent Bookings</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Passenger</th>
                                            <th>Journey</th>
                                            <th>Date</th>
                                            <th>Seats</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td><?= $booking['idBooking'] ?></td>
                                            <td><?= htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']) ?></td>
                                            <td><?= htmlspecialchars($booking['dep_city'] . ' → ' . $booking['dest_city']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($booking['depDate'])) ?></td>
                                            <td><?= $booking['requestedSeats'] ?></td>
                                            <td><?= $booking['totalPrice'] ?> DT</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar .nav-link:hover {
    color: #0d6efd;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.text-xs {
    font-size: 0.7rem;
}

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
