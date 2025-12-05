<?php
session_start();
require_once 'config/connexion.php';

if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.php');
    exit();
}

// Récupérer les IDs de réservation (soit depuis l'URL, soit toutes les récentes)
$bookingIds = [];

if (isset($_GET['ids']) && !empty($_GET['ids'])) {
    // Plusieurs IDs passés depuis checkout
    $bookingIds = array_map('intval', explode(',', $_GET['ids']));
} elseif (isset($_GET['id']) && intval($_GET['id']) > 0) {
    // Un seul ID
    $bookingIds = [intval($_GET['id'])];
} else {
    header('Location: list_booking.php');
    exit();
}

// Récupérer les détails de toutes les réservations
$placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
$types = str_repeat('i', count($bookingIds)) . 's';

$query = $conn->prepare("
    SELECT 
        b.*,
        j.depDate, j.depTime, j.price,
        c1.name AS dep_city, c2.name AS dest_city,
        u_passenger.firstName AS passenger_firstName, u_passenger.lastName AS passenger_lastName,
        u_passenger.cin AS passenger_cin, u_passenger.phone AS passenger_phone,
        u_driver.firstName AS driver_firstName, u_driver.lastName AS driver_lastName,
        u_driver.phone AS driver_phone,
        car.model AS car_model, car.immat AS car_immat
    FROM booking b
    JOIN journey j ON b.idJourney = j.idJourney
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    JOIN users u_passenger ON b.cinRequester = u_passenger.cin
    LEFT JOIN users u_driver ON j.cinRequester = u_driver.cin
    LEFT JOIN car ON j.immatCar = car.immat
    WHERE b.idBooking IN ($placeholders) AND b.cinRequester = ?
    ORDER BY b.idBooking ASC
");

$params = array_merge($bookingIds, [$_SESSION['user_cin']]);
$query->bind_param($types, ...$params);
$query->execute();
$result = $query->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$query->close();

if (empty($bookings)) {
    header('Location: list_booking.php?error=Booking not found');
    exit();
}

// Calculer le total global
$grandTotal = array_sum(array_column($bookings, 'totalPrice'));

// Si demande de téléchargement PDF
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    require_once 'includes/pdf_helper.php';
    downloadBookingsPDF($bookings, $grandTotal);
    exit();
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container my-4">
    <!-- Bouton retour -->
    <div class="mb-3">
        <a href="list_booking.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to My Bookings
        </a>
    </div>

    <!-- Carte de confirmation -->
    <div class="card confirmation-card shadow-lg">
        <div class="card-header bg-success text-white text-center py-4">
            <h2 class="mb-0">
                <i class="fas fa-check-circle fs-1"></i><br>
                Booking<?= count($bookings) > 1 ? 's' : '' ?> Confirmed!
            </h2>
            <p class="mb-0 mt-2">Your journey<?= count($bookings) > 1 ? 's have' : ' has' ?> been successfully booked</p>
        </div>
        
        <div class="card-body p-4">
            <?php foreach ($bookings as $index => $booking): ?>
            <?php if ($index > 0): ?><hr class="my-4"><?php endif; ?>
            
            <!-- Code de réservation -->
            <div class="text-center mb-4 p-3 bg-light rounded">
                <div class="text-muted small">Booking Reference <?= count($bookings) > 1 ? '#' . ($index + 1) : '' ?></div>
                <div class="fs-4 font-monospace fw-bold text-primary">
                    <?= strtoupper(substr(md5($booking['idBooking']), 0, 12)) ?>
                </div>
                <div class="small text-muted">Booking ID: #<?= $booking['idBooking'] ?></div>
            </div>

            <!-- Informations du voyage -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-section">
                        <h5 class="section-title">
                            <i class="fas fa-route text-primary"></i> Journey Details
                        </h5>
                        <div class="detail-item">
                            <span class="detail-label">From:</span>
                            <span class="detail-value"><?= htmlspecialchars($booking['dep_city']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">To:</span>
                            <span class="detail-value"><?= htmlspecialchars($booking['dest_city']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d/m/Y', strtotime($booking['depDate'])) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">
                                <i class="fas fa-clock me-1"></i>
                                <?= substr($booking['depTime'], 0, 5) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Seats:</span>
                            <span class="detail-value">
                                <span class="badge bg-info"><?= $booking['requestedSeats'] ?> seat(s)</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-section">
                        <h5 class="section-title">
                            <i class="fas fa-user-circle text-success"></i> Driver Information
                        </h5>
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?= htmlspecialchars($booking['driver_firstName'] . ' ' . $booking['driver_lastName']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">
                                <a href="tel:<?= htmlspecialchars($booking['driver_phone']) ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>
                                    <?= htmlspecialchars($booking['driver_phone']) ?>
                                </a>
                            </span>
                        </div>
                        <?php if ($booking['car_model']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Car:</span>
                            <span class="detail-value">
                                <i class="fas fa-car me-1"></i>
                                <?= htmlspecialchars($booking['car_model']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($booking['car_immat']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Plate:</span>
                            <span class="detail-value"><?= htmlspecialchars($booking['car_immat']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Résumé du paiement pour cette réservation -->
            <div class="payment-summary-item">
                <div class="d-flex justify-content-between mb-2">
                    <span><?= $booking['requestedSeats'] ?> seat(s) × <?= $booking['price'] ?> DT</span>
                    <span class="fw-bold"><?= $booking['totalPrice'] ?> DT</span>
                </div>
            </div>
            
            <?php endforeach; ?>

            <!-- Total global si plusieurs réservations -->
            <?php if (count($bookings) > 1): ?>
            <div class="payment-summary mt-4">
                <h5 class="section-title">
                    <i class="fas fa-money-bill-wave text-warning"></i> Total Payment Summary
                </h5>
                <?php foreach ($bookings as $index => $booking): ?>
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Booking #<?= ($index + 1) ?>: <?= htmlspecialchars($booking['dep_city']) ?> → <?= htmlspecialchars($booking['dest_city']) ?></span>
                    <span><?= $booking['totalPrice'] ?> DT</span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between fs-4 fw-bold text-success">
                    <span>TOTAL PAID:</span>
                    <span><?= $grandTotal ?> DT</span>
                </div>
            </div>
            <?php else: ?>
            <div class="payment-summary mt-4">
                <h5 class="section-title">
                    <i class="fas fa-money-bill-wave text-warning"></i> Payment Summary
                </h5>
                <hr>
                <div class="d-flex justify-content-between fs-4 fw-bold text-success">
                    <span>TOTAL PAID:</span>
                    <span><?= $grandTotal ?> DT</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Instructions importantes -->
            <div class="alert alert-warning mt-4">
                <h6 class="alert-heading">
                    <i class="fas fa-exclamation-triangle"></i> Important Instructions
                </h6>
                <ul class="mb-0">
                    <li>Please arrive <strong>10 minutes before</strong> the departure time</li>
                    <li>Contact the driver if you have any questions</li>
                    <li>Keep this confirmation for your records</li>
                    <li>Be respectful and punctual</li>
                </ul>
            </div>

            <!-- Boutons d'action -->
            <div class="text-center mt-4">
                <a href="booking_confirmation.php?ids=<?= implode(',', array_column($bookings, 'idBooking')) ?>&download=pdf" 
                   class="btn btn-danger btn-lg me-2" target="_blank" 
                   title="Open printable version in new tab">
                    <i class="fas fa-print"></i> Print / Save as PDF
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-lg me-2">
                    <i class="fas fa-print"></i> Print This Page
                </button>
                <a href="list_booking.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-list"></i> View All Bookings
                </a>
            </div>
        </div>

        <div class="card-footer text-center text-muted">
            <small>
                <i class="fas fa-info-circle"></i> 
                For any questions or support, contact us at support@vroomvroom.com
                <br>
                <strong>Tip:</strong> Use "Print / Save as PDF" button and select "Save as PDF" in your browser's print dialog
            </small>
        </div>
    </div>
</div>

<style>
.confirmation-card {
    max-width: 900px;
    margin: 0 auto;
    border-radius: 15px;
    overflow: hidden;
}

.info-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 15px;
    height: 100%;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #dee2e6;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #6c757d;
}

.detail-value {
    color: #212529;
    text-align: right;
}

.payment-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 25px;
    border-radius: 10px;
    border: 2px solid #dee2e6;
}

.payment-summary-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

@media print {
    .btn, .card-footer, nav, header, footer, .no-print {
        display: none !important;
    }
    
    body {
        margin: 0;
        padding: 20px;
    }
    
    .confirmation-card {
        box-shadow: none !important;
        border: 1px solid #dee2e6;
    }
    
    .card-header {
        background: #fff !important;
        color: #000 !important;
        border-bottom: 3px solid #000;
    }
    
    .info-section {
        page-break-inside: avoid;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
