<?php
session_start();
require_once 'config/connexion.php';

// Check if the user is logged in
if (!isset($_SESSION['cin'])) {
    header("Location: login.php");
    exit();
}

$cinRequester = $_SESSION['cin'];

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Stats */
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            margin: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1 1 150px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 1.9rem;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #555;
        }

        /* Bookings */
        .bookings-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .booking-card {
            background: #fff;
            padding: 18px;
            border-radius: 12px;
            flex: 1 1 300px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .route {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-upcoming { background: #d4edda; color: #155724; }
        .badge-today { background: #fff3cd; color: #856404; }
        .badge-past { background: #e2e3e5; color: #6c757d; }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .price {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
            color: #3498db;
        }

        .action-btn {
            display: inline-block;
            padding: 7px 14px;
            margin-top: 10px;
            background: #3498db;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .action-btn:hover { background: #2c80b4; }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #777;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .stats-container { flex-direction: column; align-items: center; }
            .booking-card { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
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
                            
                            <div class="card-footer">
                                <span class="booking-id">#<?php echo $booking['idBooking']; ?></span>
                                <a href="trajet_details.php?id=<?php echo $booking['idJourney']; ?>" class="action-btn">
                                    View Journey
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
                            
                            <div class="card-footer">
                                <span class="booking-id">#<?php echo $booking['idBooking']; ?></span>
                                <a href="#" class="action-btn" style="background: #95a5a6;">
                                    View Details
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
</body>
</html>
