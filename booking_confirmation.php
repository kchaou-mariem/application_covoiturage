<?php
session_start();
require_once 'config/connexion.php';

if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.php');
    exit();
}

// Récupérer l'ID de réservation depuis l'URL
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    header('Location: list_booking.php');
    exit();
}

// Récupérer les détails de la réservation
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
    WHERE b.idBooking = ? AND b.cinRequester = ?
");

$query->bind_param("is", $bookingId, $_SESSION['user_cin']);
$query->execute();
$result = $query->get_result();
$booking = $result->fetch_assoc();
$query->close();

if (!$booking) {
    header('Location: list_booking.php?error=Booking not found');
    exit();
}

// Préparer les données pour l'affichage et le PDF
$bookingData = [
    'booking_id' => $booking['idBooking'],
    'passenger_name' => $booking['passenger_firstName'] . ' ' . $booking['passenger_lastName'],
    'passenger_cin' => $booking['passenger_cin'],
    'passenger_phone' => $booking['passenger_phone'],
    'from' => $booking['dep_city'],
    'to' => $booking['dest_city'],
    'date' => $booking['depDate'],
    'time' => substr($booking['depTime'], 0, 5),
    'seats' => $booking['requestedSeats'],
    'price_per_seat' => $booking['price'],
    'total' => $booking['totalPrice'],
    'driver_name' => $booking['driver_firstName'] . ' ' . $booking['driver_lastName'],
    'driver_phone' => $booking['driver_phone'],
    'car_model' => $booking['car_model'],
    'car_immat' => $booking['car_immat']
];

// Si demande de téléchargement PDF
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    require_once 'includes/pdf_helper.php';
    downloadBookingPDF($bookingData);
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
                Booking Confirmed!
            </h2>
            <p class="mb-0 mt-2">Your journey has been successfully booked</p>
        </div>
        
        <div class="card-body p-4">
            <!-- Code de réservation -->
            <div class="text-center mb-4 p-3 bg-light rounded">
                <div class="text-muted small">Booking Reference</div>
                <div class="fs-4 font-monospace fw-bold text-primary">
                    <?= strtoupper(substr(md5($bookingData['booking_id']), 0, 12)) ?>
                </div>
                <div class="small text-muted">Booking ID: #<?= $bookingData['booking_id'] ?></div>
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
                            <span class="detail-value"><?= htmlspecialchars($bookingData['from']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">To:</span>
                            <span class="detail-value"><?= htmlspecialchars($bookingData['to']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d/m/Y', strtotime($bookingData['date'])) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">
                                <i class="fas fa-clock me-1"></i>
                                <?= $bookingData['time'] ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Seats:</span>
                            <span class="detail-value">
                                <span class="badge bg-info"><?= $bookingData['seats'] ?> seat(s)</span>
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
                            <span class="detail-value"><?= htmlspecialchars($bookingData['driver_name']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">
                                <a href="tel:<?= htmlspecialchars($bookingData['driver_phone']) ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>
                                    <?= htmlspecialchars($bookingData['driver_phone']) ?>
                                </a>
                            </span>
                        </div>
                        <?php if ($bookingData['car_model']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Car:</span>
                            <span class="detail-value">
                                <i class="fas fa-car me-1"></i>
                                <?= htmlspecialchars($bookingData['car_model']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($bookingData['car_immat']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Plate:</span>
                            <span class="detail-value"><?= htmlspecialchars($bookingData['car_immat']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Résumé du paiement -->
            <div class="payment-summary">
                <h5 class="section-title">
                    <i class="fas fa-money-bill-wave text-warning"></i> Payment Summary
                </h5>
                <div class="d-flex justify-content-between mb-2">
                    <span><?= $bookingData['seats'] ?> seat(s) × <?= $bookingData['price_per_seat'] ?> DT</span>
                    <span><?= $bookingData['total'] ?> DT</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fs-4 fw-bold text-success">
                    <span>TOTAL PAID:</span>
                    <span><?= $bookingData['total'] ?> DT</span>
                </div>
            </div>

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
                <a href="booking_confirmation.php?id=<?= $bookingData['booking_id'] ?>&download=pdf" 
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
