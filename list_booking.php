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
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>
