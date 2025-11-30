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

<?php include __DIR__ . '/includes/header.php'; ?>
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="display-4 text-primary">
                    <i class="fas fa-road"></i> Available Journeys
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
                                                                        <div>
                                                                            <h5 class="card-title mb-0">
                                                                                    <i class="fas fa-route"></i>
                                                                                    <?= htmlspecialchars($journey->departure_city_name ?? 'Départ') ?> → <?= htmlspecialchars($journey->destination_city_name ?? 'Destination') ?>
                                                                            </h5>
                                                                            <div class="card-header-date text-muted small"><?= date('d/m/Y', strtotime($journey->getDepDate())) ?></div>
                                                                        </div>
                                    <span class="price-badge badge">
                                        <?= number_format($journey->getPrice(), 2) ?> DT
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
                                                        <?= htmlspecialchars($journey->departure_city_name ?? 'Departure') ?>
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
                                                <?= htmlspecialchars($journey->destination_city_name ?? 'Arrival') ?>
                                            <?php if (!empty($journey->destination_delegation_name)): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($journey->destination_delegation_name) ?></div>
                                            <?php endif; ?>
                                            </div>
                                            <small class="text-muted">Arrival</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informations date, heure et prix -->
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
                                            <i class="fas fa-users me-1"></i> Available seats:
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

                                <!-- Preferences -->
                                <?php if (!empty($journey->getPreferencesArray())): ?>
                                    <div class="mb-3">
                                        <div class="fw-bold mb-2">
                                            <i class="fas fa-tags me-1"></i> Preferences:
                                        </div>
                                        <div class="d-flex flex-wrap">
                                            <?php foreach ($journey->getPreferencesArray() as $prefId): ?>
                                                <span class="preference-tag mb-1">
                                                    <i class="fas fa-check-circle me-1"></i> Preference <?= $prefId ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Informations Driver -->
                                <div class="border-top pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="driver-avatar me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-fill">
                                            <div class="fw-bold">Driver</div>
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
                                                <div class="fw-bold">Car</div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-car-side me-1"></i>
                                                    <?= htmlspecialchars($journey->car_model ?? '') ?><?php if (!empty($journey->car_model) && !empty($journey->car_immat)) echo ' '; ?><?= !empty($journey->car_immat) ? '(' . htmlspecialchars($journey->car_immat) . ')' : '' ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions: add-to-cart only -->
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid">
                                    <?php if (isset($_SESSION['user_cin'])): ?>
                                        <!-- add-to-cart visible only to logged-in users -->
                                        <?php if ($journey->getNbSeats() > 0): ?>
                                            <!-- active add-to-cart link (will trigger confirmation) -->
                                            <a href="cart.php?add=<?= $journey->getIdJourney() ?>" class="btn btn-warning btn-lg add-to-cart-link" data-id="<?= $journey->getIdJourney() ?>">
                                                <i class="fas fa-cart-plus me-2"></i> Add to cart
                                            </a>
                                        <?php else: ?>
                                            <!-- disabled when no seats -->
                                            <button class="btn btn-warning btn-lg" disabled title="Complet">
                                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Guest CTA: encourage signing in to add to cart -->
                                        <a href="authentification.html" class="btn btn-outline-primary btn-lg" title="Connectez-vous pour ajouter au panier">
                                            <i class="fas fa-sign-in-alt me-2"></i> Sign in to add
                                        </a>
                                    <?php endif; ?>
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
                        <h3 class="text-muted">No journeys available</h3>
                        <p class="text-muted mb-4">There are no journeys at the moment.</p>
                        <?php if (isset($_SESSION['user_cin'])): ?>
                            <a href="createJourney.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i> Post a journey
                            </a>
                        <?php else: ?>
                            <a href="authentification.html" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Sign in to post a journey
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fonction pour réserver un trajet
        function reserverTrajet(idJourney) {
            if (confirm('Voulez-vous vraiment réserver ce trajet ?')) {
                window.location.href = 'reserver.php?id=' + idJourney;
            }
        }

        // Fonction pour afficher les détails d'un trajet
        function afficherDetails(idJourney) {
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

        // Confirmation ajout panier (FR)
        function confirmAddToCart(id) {
            if (confirm('Voulez-vous ajouter ce trajet au panier ?')) {
                window.location.href = 'cart.php?add=' + id;
            }
        }

        // Attach click handler to add-to-cart buttons (safer than inline handlers)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.add-to-cart-link').forEach(function(el) {
                el.addEventListener('click', function(evt) {
                    evt.preventDefault();
                    const id = this.getAttribute('data-id');
                    confirmAddToCart(id);
                });
            });
        });
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>