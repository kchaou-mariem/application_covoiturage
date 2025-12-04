<?php
require_once 'config/connexion.php';
require_once 'Manager/JourneyManager.php';
require_once 'Manager/CityManager.php';
require_once 'Manager/PreferenceManager.php';
require_once 'Manager/CarManager.php';

// Initialisation des managers
$cityManager = new CityManager($conn);
$prefManager = new PreferenceManager($conn);
$journeyManager = new JourneyManager($conn);

$cities = $cityManager->findAll();
$prefs = $prefManager->findAll();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php
// Afficher les résultats ou messages en haut de la page
if (isset($_POST['search'])) {
    $departure = $_POST['departure_city'] ?? '';
    $destination = $_POST['destination_city'] ?? '';
    $searchDate = $_POST['date'] ?? '';
    $searchSeats = $_POST['seats'] ?? 0;
    $preferences = $_POST['preferences'] ?? [];

    if ($departure == $destination) {
        echo "<div class='alert alert-warning text-center my-4' role='alert'>";
        echo "<i class='fas fa-exclamation-triangle'></i> <strong>Departure and destination must be different.</strong>";
        echo "</div>";
    } else {
        $journeys = $journeyManager->searchJourneys($departure, $destination, $searchDate, $searchSeats, $preferences);

        if (empty($journeys)) {
            echo "<div class='alert alert-info text-center my-4' role='alert'>";
            echo "<i class='fas fa-search'></i> <strong>No journeys found matching your criteria.</strong>";
            echo "<p class='mb-0 mt-2 small'>Try adjusting your search filters or check back later.</p>";
            echo "</div>";
        } else {
            // Afficher le nombre de résultats et les résultats en haut
            echo "<div class='container'>";
            echo "<div class='alert alert-success mb-4 d-flex align-items-center' role='alert'>";
            echo "<i class='fas fa-check-circle fs-4 me-3'></i>";
            echo "<div><strong class='fs-5'>Search Results</strong><br><span class='text-muted'>" . count($journeys) . " journey(s) found matching your criteria</span></div>";
            echo "</div>";
            
            // Afficher les résultats
            echo "<div class='search-results'>";
            foreach ($journeys as $j) {
                // Sanitize values
                $depCity = htmlspecialchars($j->departure_city_name ?? '');
                $depDel = htmlspecialchars($j->departure_delegation_name ?? '');
                $destCity = htmlspecialchars($j->destination_city_name ?? '');
                $destDel = htmlspecialchars($j->destination_delegation_name ?? '');
                $date = htmlspecialchars($j->getDepDate());
                $time = htmlspecialchars($j->getDepTime());
                $seats = (int)$j->getNbSeats();
                $price = htmlspecialchars(number_format((float)$j->getPrice(), 2));

                echo "<div class='result-item card shadow-sm'>";
                
                // Header avec trajet
                echo "<div class='card-header bg-light'>";
                echo "<h5 class='mb-0'><i class='fas fa-map-marker-alt text-success'></i> {$depCity}" . (!empty($depDel) ? " <small class='text-muted'>({$depDel})</small>" : "") . " <i class='fas fa-arrow-right text-primary mx-2'></i> <i class='fas fa-map-marker-alt text-danger'></i> {$destCity}" . (!empty($destDel) ? " <small class='text-muted'>({$destDel})</small>" : "") . "</h5>";
                echo "</div>";
                
                echo "<div class='card-body'>";
                
                // Informations principales en badges
                echo "<div class='mb-3 d-flex flex-wrap gap-2'>";
                echo "<span class='badge bg-primary'><i class='fas fa-calendar me-1'></i>{$date}</span>";
                echo "<span class='badge bg-primary'><i class='fas fa-clock me-1'></i>" . substr($time, 0, 5) . "</span>";
                echo "<span class='badge bg-info'><i class='fas fa-users me-1'></i>{$seats} seat(s)</span>";
                echo "<span class='badge bg-success'><i class='fas fa-tag me-1'></i>{$price} DT</span>";
                
                // Status badge
                if ($seats > 0) {
                    echo "<span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Available</span>";
                } else {
                    echo "<span class='badge bg-danger'><i class='fas fa-times-circle me-1'></i>Full</span>";
                }
                echo "</div>";

                // Informations voiture
                if (!empty($j->car_model) || !empty($j->car_immat)) {
                    $carModel = htmlspecialchars($j->car_model ?? '');
                    $carImmat = !empty($j->car_immat) ? htmlspecialchars($j->car_immat) : '';
                    echo "<div class='mb-2'><i class='fas fa-car text-muted me-2'></i><strong>Car:</strong> {$carModel}" . (!empty($carImmat) ? " <span class='text-muted'>({$carImmat})</span>" : "") . "</div>";
                }

                // Informations conducteur
                if (!empty($j->driver_name) || !empty($j->driver_phone) || !empty($j->driver_email)) {
                    echo "<hr class='my-2'>";
                    echo "<div class='driver-info'>";
                    echo "<div class='mb-1'><i class='fas fa-user-circle text-primary me-2'></i><strong>Driver:</strong> " . htmlspecialchars($j->driver_name ?? 'Anonymous');
                    if (!empty($j->driver_gender)) {
                        $genderIcon = ($j->driver_gender == 'male') ? 'fa-mars' : 'fa-venus';
                        echo " <i class='fas {$genderIcon} ms-1'></i>";
                    }
                    echo "</div>";
                    if (!empty($j->driver_phone)) echo "<div class='small text-muted mb-1'><i class='fas fa-phone me-2'></i>" . htmlspecialchars($j->driver_phone) . "</div>";
                    if (!empty($j->driver_email)) echo "<div class='small text-muted'><i class='fas fa-envelope me-2'></i>" . htmlspecialchars($j->driver_email) . "</div>";
                    echo "</div>";
                }

                // Préférences
                $prefsArray = $j->getPreferencesArray();
                if (!empty($prefsArray)) {
                    echo "<hr class='my-2'>";
                    echo "<div class='mb-2'><i class='fas fa-star text-warning me-2'></i><strong>Preferences:</strong> ";
                    foreach ($prefsArray as $pref) {
                        echo "<span class='badge bg-light text-dark border me-1'>" . htmlspecialchars($pref) . "</span>";
                    }
                    echo "</div>";
                }

                // Add to cart button
                echo "<div class='mt-3 text-end'>";
                if ($seats > 0) {
                    echo "<a href='cart.php?add=" . urlencode($j->getIdJourney()) . "' class='btn btn-warning'><i class='fas fa-shopping-cart me-1'></i>Add to Cart</a>";
                } else {
                    echo "<button class='btn btn-secondary' disabled><i class='fas fa-ban me-1'></i>Full</button>";
                }
                echo "</div>";

                echo "</div></div>"; // close card-body and result-item
            }
            echo "</div></div>"; // close .search-results and container
        }
    }
}
?>

<form method="POST" class="search-form">
    <h2>Search for a Journey</h2>

    <label for="departure_city">Departure:</label>
    <select class="form-select" name="departure_city" id="departure_city" required>
        <option value="">-- Choose city --</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?= $city->getIdCity(); ?>"><?= htmlspecialchars($city->getName()); ?></option>
        <?php endforeach; ?>
    </select>
    <br>
    <br>

    <label for="destination_city">Destination:</label>
    <select class="form-select" name="destination_city" id="destination_city" required>
        <option value="">-- Choose city --</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?= $city->getIdCity(); ?>"><?= htmlspecialchars($city->getName()); ?></option>
        <?php endforeach; ?>
    </select>
    <br>

    <label for="date">Date:</label>
    <input class="form-control" type="date" id="date" name="date">
    <br>

    <label for="seats">Seats available:</label>
    <input class="form-control" type="number" id="seats" name="seats" min="1" max="9">
    <br>

    <h3>+ Preferences</h3>
    <?php foreach ($prefs as $pref): ?>
        <label>
            <input type="checkbox" name="preferences[]" value="<?= htmlspecialchars($pref->getLabel()); ?>">
            <?= htmlspecialchars($pref->getLabel()); ?>
        </label><br>
    <?php endforeach; ?>

    <br>
    <button type="submit" name="search" class="btn btn-search">Search Journey</button>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
