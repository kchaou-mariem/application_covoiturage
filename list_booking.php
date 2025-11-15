<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['cin'])) {
    header("Location: login.php");
    exit();
}

$cinRequester = $_SESSION['cin'];

// Récupérer les réservations de l'utilisateur
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

$reservations = [];
$reservations_a_venir = [];
$reservations_passees = [];

while ($row = $result->fetch_assoc()) {
    // Déterminer le statut
    $dateTrajet = $row['depDate'];
    $aujourdhui = date('Y-m-d');
    
    if ($dateTrajet > $aujourdhui) {
        $row['statut'] = 'À venir';
        $row['badge_class'] = 'badge-coming';
        $reservations_a_venir[] = $row;
    } else if ($dateTrajet == $aujourdhui) {
        $row['statut'] = 'Aujourd\'hui';
        $row['badge_class'] = 'badge-today';
        $reservations_a_venir[] = $row;
    } else {
        $row['statut'] = 'Passé';
        $row['badge_class'] = 'badge-past';
        $reservations_passees[] = $row;
    }
    
    $reservations[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: #f0f2f5;
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

    /* Statistiques */
    .stats-container {
        display: flex;
        justify-content: space-around;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: #fff;
        padding: 15px 20px;
        margin: 10px;
        border-radius: 10px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        text-align: center;
        flex: 1 1 150px;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: bold;
        color: #4a90e2;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #666;
    }

    /* Réservations */
    .reservations-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
    }

    .reservation-card {
        background: #fff;
        padding: 15px;
        border-radius: 10px;
        flex: 1 1 300px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .route {
        font-weight: bold;
        margin-bottom: 10px;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }

    .badge-coming { background: #d4edda; color: #155724; }
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
        color: #4a90e2;
    }

    .action-btn {
        display: inline-block;
        padding: 6px 12px;
        margin-top: 10px;
        background: #4a90e2;
        color: #fff;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .action-btn:hover { background: #357ab8; }

    .empty-state {
        text-align: center;
        padding: 40px 0;
        color: #666;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .stats-container {
            flex-direction: column;
            align-items: center;
        }

        .reservation-card {
            flex: 1 1 100%;
        }
    }
</style>

</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Mes Réservations</h1>
            <p>Consultez l'historique de vos trajets réservés</p>
        </div>

        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($reservations); ?></div>
                <div class="stat-label">Total des réservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($reservations_a_venir); ?></div>
                <div class="stat-label">Réservations à venir</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($reservations_passees); ?></div>
                <div class="stat-label">Réservations passées</div>
            </div>
        </div>

        <!-- Réservations à venir -->
        <div class="reservations-section">
            <h2 class="section-title">🚗 Réservations à venir (<?php echo count($reservations_a_venir); ?>)</h2>
            
            <?php if (!empty($reservations_a_venir)): ?>
                <div class="reservations-grid">
                    <?php foreach ($reservations_a_venir as $reservation): ?>
                        <div class="reservation-card">
                            <div class="card-header">
                                <div class="route"><?php echo htmlspecialchars($reservation['departure_city']); ?> → <?php echo htmlspecialchars($reservation['destination_city']); ?></div>
                                <span class="badge <?php echo $reservation['badge_class']; ?>">
                                    <?php echo $reservation['statut']; ?>
                                </span>
                            </div>
                            
                            <div class="card-details">
                                <div class="detail-item">
                                    <span class="detail-label">Date de départ:</span>
                                    <span class="detail-value"><?php echo date('d/m/Y', strtotime($reservation['depDate'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Heure:</span>
                                    <span class="detail-value"><?php echo substr($reservation['depTime'], 0, 5); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Places réservées:</span>
                                    <span class="detail-value"><?php echo $reservation['requestedSeats']; ?> place(s)</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Prix unitaire:</span>
                                    <span class="detail-value"><?php echo $reservation['pricePerSeat']; ?> DT</span>
                                </div>
                            </div>
                            
                            <div class="price"><?php echo $reservation['totalPrice']; ?> DT</div>
                            
                            <div class="card-footer">
                                <span class="booking-id">#<?php echo $reservation['idBooking']; ?></span>
                                <a href="trajet_details.php?id=<?php echo $reservation['idJourney']; ?>" class="action-btn">
                                    Voir le trajet
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div>📭</div>
                    <h3>Aucune réservation à venir</h3>
                    <p>Vous n'avez pas de trajets réservés pour le moment</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Réservations passées -->
        <div class="reservations-section">
            <h2 class="section-title">📊 Réservations passées (<?php echo count($reservations_passees); ?>)</h2>
            
            <?php if (!empty($reservations_passees)): ?>
                <div class="reservations-grid">
                    <?php foreach ($reservations_passees as $reservation): ?>
                        <div class="reservation-card">
                            <div class="card-header">
                                <div class="route"><?php echo htmlspecialchars($reservation['departure_city']); ?> → <?php echo htmlspecialchars($reservation['destination_city']); ?></div>
                                <span class="badge <?php echo $reservation['badge_class']; ?>">
                                    <?php echo $reservation['statut']; ?>
                                </span>
                            </div>
                            
                            <div class="card-details">
                                <div class="detail-item">
                                    <span class="detail-label">Date du trajet:</span>
                                    <span class="detail-value"><?php echo date('d/m/Y', strtotime($reservation['depDate'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Heure:</span>
                                    <span class="detail-value"><?php echo substr($reservation['depTime'], 0, 5); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Places réservées:</span>
                                    <span class="detail-value"><?php echo $reservation['requestedSeats']; ?> place(s)</span>
                                </div>
                            </div>
                            
                            <div class="price"><?php echo $reservation['totalPrice']; ?> DT</div>
                            
                            <div class="card-footer">
                                <span class="booking-id">#<?php echo $reservation['idBooking']; ?></span>
                                <a href="#" class="action-btn" style="background: #95a5a6;">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div>📊</div>
                    <h3>Aucune réservation passée</h3>
                    <p>Votre historique de réservations apparaîtra ici</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="trajets.php" class="nav-btn">🔍 Chercher un trajet</a>
            <a href="profile.php" class="nav-btn">👤 Mon profil</a>
        </div>
    </div>

    <script>
        // Animation simple au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.reservation-card');
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