<?php
session_start();
require_once 'config/connexion.php';

// Check if the user is logged in
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_cin'])) {
        header('Location: authentification.html');
        exit;
    }
    $cinRequester = $_SESSION['user_cin'];

// Fetch user's bookings
$sql = "SELECT 
            b.idBooking,
            b.requestedSeats,
            b.totalPrice,
            b.bookingDate,
            j.idJourney,
            j.depDate,
            j.depTime,
            j.price as pricePerSeat,
            c1.name as departure_city,
            c2.name as destination_city
        FROM booking b
        JOIN journey j ON b.idJourney = j.idJourney
        JOIN city c1 ON j.departure = c1.idCity
        JOIN city c2 ON j.destination = c2.idCity
        WHERE b.cinRequester = ?
        ORDER BY j.depDate DESC, j.depTime DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cinRequester);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
$upcoming_bookings = [];
$past_bookings = [];

while ($row = $result->fetch_assoc()) {
    $journeyDate = $row['depDate'];
    $today = date('Y-m-d');
    
    if ($journeyDate > $today) {
        $row['status'] = 'Upcoming';
        $row['badge_class'] = 'badge-upcoming';
        $upcoming_bookings[] = $row;
    } else if ($journeyDate == $today) {
        $row['status'] = 'Today';
        $row['badge_class'] = 'badge-today';
        $upcoming_bookings[] = $row;
    } else {
        $row['status'] = 'Past';
        $row['badge_class'] = 'badge-past';
        $past_bookings[] = $row;
    }
    
    $bookings[] = $row;
}

$stmt->close();
?>

<?php include __DIR__ . '/includes/header.php'; ?>
        <!-- Messages de succès/erreur -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="header">
            <h1>🎫 My Bookings</h1>
            <p>Check your booking history and upcoming trips</p>
        </div>

        <!-- Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($upcoming_bookings); ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($past_bookings); ?></div>
                <div class="stat-label">Past</div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="bookings-section">
            <h2 class="section-title">🚗 Upcoming Bookings (<?php echo count($upcoming_bookings); ?>)</h2>
            
            <?php if (!empty($upcoming_bookings)): ?>
                <div class="bookings-grid">
                    <?php foreach ($upcoming_bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="card-header">
                                        <div class="route"><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['destination_city']); ?></div>
                                        <span class="badge <?php echo $booking['badge_class']; ?>">
                                            <?php echo $booking['status']; ?>
                                        </span>
                                    </div>
                            
                            <div class="card-details">
                                <div class="detail-item">
                                    <span>Date:</span>
                                    <span><?php echo date('d/m/Y', strtotime($booking['depDate'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Time:</span>
                                    <span><?php echo substr($booking['depTime'], 0, 5); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Seats:</span>
                                    <span><?php echo $booking['requestedSeats']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Price/seat:</span>
                                    <span><?php echo $booking['pricePerSeat']; ?> DT</span>
                                </div>
                            </div>
                            
                            <div class="price"><?php echo $booking['totalPrice']; ?> DT</div>
                            
                            <div class="card-actions mt-2">
                                <a href="booking_confirmation.php?id=<?php echo $booking['idBooking']; ?>" class="btn-view">
                                    <i class="fas fa-file-invoice"></i> View Confirmation
                                </a>
                                <button onclick="showCancelModal(<?php echo $booking['idBooking']; ?>, '<?php echo htmlspecialchars($booking['departure_city'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($booking['destination_city'], ENT_QUOTES); ?>', '<?php echo date('d/m/Y', strtotime($booking['depDate'])); ?>', <?php echo $booking['requestedSeats']; ?>)" class="btn-cancel">
                                    <i class="fas fa-times-circle"></i> Cancel
                                </button>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div>📭</div>
                    <h3>No upcoming bookings</h3>
                    <p>You don't have any trips booked yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Bookings -->
        <div class="bookings-section">
            <h2 class="section-title">📊 Past Bookings (<?php echo count($past_bookings); ?>)</h2>
            
            <?php if (!empty($past_bookings)): ?>
                <div class="bookings-grid">
                    <?php foreach ($past_bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="card-header">
                                <div class="route"><?php echo htmlspecialchars($booking['departure_city']); ?> → <?php echo htmlspecialchars($booking['destination_city']); ?></div>
                                <span class="badge <?php echo $booking['badge_class']; ?>">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </div>
                            
                            <div class="card-details">
                                <div class="detail-item">
                                    <span>Date:</span>
                                    <span><?php echo date('d/m/Y', strtotime($booking['depDate'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Time:</span>
                                    <span><?php echo substr($booking['depTime'], 0, 5); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span>Seats:</span>
                                    <span><?php echo $booking['requestedSeats']; ?></span>
                                </div>
                            </div>
                            
                            <div class="price"><?php echo $booking['totalPrice']; ?> DT</div>
                            
                            <div class="card-actions mt-2">
                                <a href="booking_confirmation.php?id=<?php echo $booking['idBooking']; ?>" class="btn-view">
                                    <i class="fas fa-file-invoice"></i> View Confirmation
                                </a>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div>📊</div>
                    <h3>No past bookings</h3>
                    <p>Your booking history will appear here</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="nav-buttons" style="text-align:center; margin-top:30px;">
            <a href="trajets.php" class="action-btn" style="margin-right:10px;">🔍 Search Trip</a>
            <a href="profile.php" class="action-btn">👤 My Profile</a>
        </div>
    </div>

    <!-- Modal de confirmation d'annulation -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancel Booking
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-times-circle text-danger" style="font-size: 4rem; opacity: 0.8;"></i>
                    </div>
                    <h6 class="text-center mb-3">Are you sure you want to cancel this booking?</h6>
                    
                    <div class="booking-summary p-3 bg-light rounded">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold"><i class="fas fa-route me-2 text-primary"></i>Route:</span>
                            <span id="modal-route"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold"><i class="fas fa-calendar me-2 text-primary"></i>Date:</span>
                            <span id="modal-date"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold"><i class="fas fa-chair me-2 text-primary"></i>Seats:</span>
                            <span id="modal-seats"></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>The seats will be released and made available for other passengers.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i>Keep Booking
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                        <i class="fas fa-trash-alt me-1"></i>Yes, Cancel Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.booking-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        let currentBookingId = null;
        
        function showCancelModal(bookingId, fromCity, toCity, date, seats) {
            currentBookingId = bookingId;
            document.getElementById('modal-route').textContent = fromCity + ' → ' + toCity;
            document.getElementById('modal-date').textContent = date;
            document.getElementById('modal-seats').textContent = seats + ' seat(s)';
            
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        }
        
        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            if (currentBookingId) {
                window.location.href = 'delete_booking.php?id=' + currentBookingId;
            }
        });
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>
