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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Journey</title>
    <style>
        .status-available { color: green; font-weight: bold; }
        .status-full { color: red; font-weight: bold; }
        .btn-disabled { background-color: gray; color: white; border: none; padding: 5px 10px; cursor: not-allowed; }
        .btn-active { background-color: #f0ad4e; color: white; border: none; padding: 5px 10px; cursor: pointer; }
    </style>
</head>
<body>

<form method="POST">
    <h2>Search for a Journey</h2>

    <label for="departure_city">Departure:</label>
    <select name="departure_city" id="departure_city" required>
        <option value="">-- Choose city --</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?= $city->getIdCity(); ?>"><?= htmlspecialchars($city->getName()); ?></option>
        <?php endforeach; ?>
    </select>
    <br>

    <label for="destination_city">Destination:</label>
    <select name="destination_city" id="destination_city" required>
        <option value="">-- Choose city --</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?= $city->getIdCity(); ?>"><?= htmlspecialchars($city->getName()); ?></option>
        <?php endforeach; ?>
    </select>
    <br>

    <label for="date">Date:</label>
    <input type="date" id="date" name="date">
    <br>

    <label for="seats">Seats available:</label>
    <input type="number" id="seats" name="seats" min="1" max="9">
    <br>

    <h3>+ Preferences</h3>
    <?php foreach ($prefs as $pref): ?>
        <label>
            <input type="checkbox" name="preferences[]" value="<?= htmlspecialchars($pref->getLabel()); ?>">
            <?= htmlspecialchars($pref->getLabel()); ?>
        </label><br>
    <?php endforeach; ?>

    <br>
    <input type="submit" name="search" value="Search Journey">
</form>

<hr>

<?php
if (isset($_POST['search'])) {
    $departure = $_POST['departure_city'] ?? '';
    $destination = $_POST['destination_city'] ?? '';
    $date = $_POST['date'] ?? '';
    $seats = $_POST['seats'] ?? 0;
    $preferences = $_POST['preferences'] ?? [];

    if ($departure == $destination) {
        echo "<p style='color:red;'>Departure and destination must be different.</p>";
    } else {
        $journeys = $journeyManager->searchJourneys($departure, $destination, $date, $seats, $preferences);

        if (!empty($journeys)) {
            echo "<h3>Results:</h3>";
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

                echo "<div class='result-item'>";
                echo "<p><strong>From:</strong> {$depCity}" . (!empty($depDel) ? " ({$depDel})" : "") . " &rarr; <strong>To:</strong> {$destCity}" . (!empty($destDel) ? " ({$destDel})" : "") . "</p>";
                echo "<p><strong>Date:</strong> {$date} {$time} &nbsp; <strong>Available Seats:</strong> {$seats} &nbsp; <strong>Price:</strong> {$price} DT</p>";

                // Status
                if ($seats > 0) {
                    echo "<p><strong>Status:</strong> <span class='status-available'>Available</span></p>";
                } else {
                    echo "<p><strong>Status:</strong> <span class='status-full'>Full</span></p>";
                }

                if (!empty($j->getImmatCar())) {
                    echo "<p><strong>Car:</strong> " . htmlspecialchars($j->getImmatCar()) . "</p>";
                }

                // Driver info (delegation and driver shown clearly)
                if (!empty($j->driver_name) || !empty($j->driver_phone) || !empty($j->driver_email)) {
                    echo "<p><strong>Driver:</strong> " . htmlspecialchars($j->driver_name ?? 'Anonyme') . "</p>";
                    if (!empty($j->driver_phone)) echo "<p><strong>Phone:</strong> " . htmlspecialchars($j->driver_phone) . "</p>";
                    if (!empty($j->driver_email)) echo "<p><strong>Email:</strong> " . htmlspecialchars($j->driver_email) . "</p>";
                    if (!empty($j->driver_gender)) echo "<p><strong>Gender:</strong> " . htmlspecialchars($j->driver_gender) . "</p>";
                }

                $prefsArray = $j->getPreferencesArray();
                if (!empty($prefsArray)) {
                    echo "<p><strong>Preferences:</strong> " . htmlspecialchars(implode(', ', $prefsArray)) . "</p>";
                }

                // Add to cart button
                if ($seats > 0) {
                    echo "<p><a href='cart.php?add=" . urlencode($j->getIdJourney()) . "' class='btn-active'>Ajouter au panier</a></p>";
                } else {
                    echo "<p><button class='btn-disabled' disabled>Complet</button></p>";
                }

                echo "<hr></div>";
            }
        } else {
            echo "<p>No journeys found matching your criteria.</p>";
        }
    }
}
?>

</body>
</html>
