<?php
// trajets.php

session_start();

// Connexion à la base de données et inclusion des classes
require_once 'config/connexion.php';
require_once 'Entity/Journey.php';
require_once 'Manager/JourneyManager.php';
require_once 'Entity/City.php';
require_once 'Manager/CityManager.php';

// Vérifier si la connexion est établie
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Initialisation des managers
$journeyManager = new JourneyManager($conn);
$cityManager = new CityManager($conn);

// Récupérer TOUS les trajets depuis la base de données
$journeys = $journeyManager->findAll();

// Récupérer les villes pour les noms (optionnel)
$cities = $cityManager->findAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les Trajets - Covoiturage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .journey-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        .journey-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .price-badge {
            font-size: 1.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .seats-available {
            color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
        }
        .seats-full {
            color: #dc3545;
            font-weight: bold;
        }
        .route-icon {
            color: #007bff;
            margin: 0 10px;
        }
        .preference-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-right: 5px;
            display: inline-block;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        .driver-avatar {
            width: 50px;
            height: 50px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-car-side"></i> Covoiturage
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_cin'])): ?>
                    <span class="navbar-text me-3">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_firstName'] ?? 'Utilisateur') ?>
                    </span>
                    <form method="post" action="logout.php" style="display:inline;">
                        <button type="submit" class="nav-link btn btn-link" style="padding:0; border:none; background:none;" 
                                onclick="return confirm('Voulez-vous vous déconnecter ?');">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="display-4 text-primary">
                    <i class="fas fa-road"></i> Available journeys
                </h1>
                <p class="lead text-muted">
                    Explore available journeys
                </p>
                <div class="badge bg-info fs-6">
                    <i class="fas fa-list"></i> <?= count($journeys) ?> journey(s) found
                </div>
            </div>
        </div>

        <!-- Liste des trajets -->
        <div class="row">
            <?php if (!empty($journeys)): ?>
                <?php foreach ($journeys as $journey): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card journey-card h-100">
                            <!-- En-tête de la carte -->
                            <div class="card-header card-header-custom py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-route"></i> Journey #<?= $journey->getIdJourney() ?>
                                    </h5>
                                    <span class="price-badge badge">
                                        <?= number_format($journey->getPrice(), 2) ?> €
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <!-- Itinéraire -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-center flex-fill">
                                            <div class="fw-bold text-primary">
                                                <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($journey->departure_city_name ?? 'Départ') ?>
                                                <?php if (!empty($journey->departure_delegation_name)): ?>
                                                    <div class="text-muted small"><?= htmlspecialchars($journey->departure_delegation_name) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Departure</small>
                                        </div>
                                        
                                        <div class="route-icon">
                                            <i class="fas fa-arrow-right fa-lg"></i>
                                        </div>
                                        
                                        <div class="text-center flex-fill">
                                            <div class="fw-bold text-success">
                                                <i class="fas fa-flag-checkered"></i>
                                                <?= htmlspecialchars($journey->destination_city_name ?? 'Destination') ?>
                                            <?php if (!empty($journey->destination_delegation_name)): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($journey->destination_delegation_name) ?></div>
                                            <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Arrival</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informations date et heure -->
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold">Date</div>
                                                <div class="text-muted small">
                                                    <?= date('d/m/Y', strtotime($journey->getDepDate())) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-warning me-2"></i>
                                            <div>
                                                <div class="fw-bold">Time</div>
                                                <div class="text-muted small">
                                                    <?= substr($journey->getDepTime(), 0, 5) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Places disponibles -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">
                                            <i class="fas fa-users me-1"></i> Places disponibles:
                                        </span>
                                        <?php if ($journey->getNbSeats() > 0): ?>
                                            <span class="seats-available">
                                                <?= $journey->getNbSeats() ?> place(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="seats-full">
                                                <i class="fas fa-times"></i> Complet
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Préférences -->
                                <?php if (!empty($journey->getPreferencesArray())): ?>
                                    <div class="mb-3">
                                        <div class="fw-bold mb-2">
                                            <i class="fas fa-tags me-1"></i> Préférences:
                                        </div>
                                        <div class="d-flex flex-wrap">
                                            <?php foreach ($journey->getPreferencesArray() as $prefId): ?>
                                                <span class="preference-tag mb-1">
                                                    <i class="fas fa-check-circle me-1"></i> Préférence <?= $prefId ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Informations conducteur -->
                                <div class="border-top pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="driver-avatar me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="fw-bold">Conducteur</div>
                                            <div class="text-muted small">
                                                    <?= htmlspecialchars($journey->driver_name ?? 'Anonyme') ?>
                                                    <?php if (!empty($journey->driver_phone) || !empty($journey->driver_email) || !empty($journey->driver_gender)): ?>
                                                        <br>
                                                        <?php if (!empty($journey->driver_phone)): ?><strong>Phone:</strong> <?= htmlspecialchars($journey->driver_phone) ?><br><?php endif; ?>
                                                        <?php if (!empty($journey->driver_email)): ?><strong>Email:</strong> <?= htmlspecialchars($journey->driver_email) ?><br><?php endif; ?>
                                                        <?php if (!empty($journey->driver_gender)): ?><strong>Gender:</strong> <?= htmlspecialchars($journey->driver_gender) ?><br><?php endif; ?>
                                                    <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($journey->car_model) || !empty($journey->car_immat)): ?>
                                            <div class="text-end">
                                                <div class="fw-bold">Véhicule</div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-car-side me-1"></i>
                                                    <?= htmlspecialchars($journey->car_model ?? '') ?><?php if (!empty($journey->car_model) && !empty($journey->car_immat)) echo ' '; ?><?= !empty($journey->car_immat) ? '(' . htmlspecialchars($journey->car_immat) . ')' : '' ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid gap-2">
                                    <?php if (isset($_SESSION['user_cin'])): ?>
                                        <?php if ($journey->getNbSeats() > 0): ?>
                                            <button class="btn btn-success btn-lg" 
                                                    onclick="reserverTrajet(<?= $journey->getIdJourney() ?>)">
                                                <i class="fas fa-check-circle me-2"></i> Réserver
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-lg" disabled>
                                                <i class="fas fa-times-circle me-2"></i> Complet
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i> Se connecter pour réserver
                                        </a>
                                    <?php endif; ?>
                                    <a href="cart.php?add=<?= $journey->getIdJourney() ?>" class="btn btn-warning">
                                        <i class="fas fa-cart-plus"></i> Ajouter au panier
                                    </a>
                                    <button class="btn btn-outline-info" 
                                            onclick="afficherDetails(<?= $journey->getIdJourney() ?>)">
                                        <i class="fas fa-info-circle me-2"></i> Plus de détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Aucun trajet trouvé -->
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-road fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted">Aucun trajet disponible</h3>
                        <p class="text-muted mb-4">Il n'y a pas de trajets de covoiturage pour le moment.</p>
                        <?php if (isset($_SESSION['user_cin'])): ?>
                            <a href="creer_trajet.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i> Proposer un trajet
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Connectez-vous pour proposer un trajet
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container text-center">
            <p>&copy; 2024 Covoiturage. Tous droits réservés.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour réserver un trajet
        function reserverTrajet(idJourney) {
            if (confirm('Voulez-vous vraiment réserver ce trajet ?')) {
                // Redirection vers la page de réservation
                window.location.href = 'reserver.php?id=' + idJourney;
            }
        }

        // Fonction pour afficher les détails d'un trajet
        function afficherDetails(idJourney) {
            // Redirection vers la page de détails
            window.location.href = 'details_trajet.php?id=' + idJourney;
        }

        // Animation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.journey-card');
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