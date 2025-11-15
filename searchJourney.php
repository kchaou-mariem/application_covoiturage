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
                echo "<p>
                        <strong>From:</strong> {$j->departure_city_name} {$j->departure_delegation_name} 
                        → <strong>To:</strong> {$j->destination_city_name} {$j->destination_delegation_name}<br>
                        <strong>Date:</strong> {$j->getDepDate()} {$j->getDepTime()}<br>
                        <strong>Available Seats:</strong> {$j->getNbSeats()}<br>
                        <strong>Price:</strong> {$j->getPrice()} DT<br>";

                // Statut du trajet
                if ($j->getNbSeats() > 0) {
                    echo "<strong>Status:</strong> <span class='status-available'>Available</span><br>";
                } else {
                    echo "<strong>Status:</strong> <span class='status-full'>Full</span><br>";
                }

                if (!empty($j->getImmatCar())) {
                    echo "<strong>Car:</strong> {$j->getImmatCar()}<br>";
                }

                $prefsArray = $j->getPreferencesArray();
                if (!empty($prefsArray)) {
                    echo "<strong>Preferences:</strong> " . implode(', ', $prefsArray) . "<br>";
                }

                // Bouton Ajouter au panier
                if ($j->getNbSeats() > 0) {
                    echo "<a href='cart.php?add={$j->getIdJourney()}' class='btn-active'>Ajouter au panier</a>";
                } else {
                    echo "<button class='btn-disabled' disabled>Complet</button>";
                }

                echo "</p><hr>";
            }
        } else {
            echo "<p>No journeys found matching your criteria.</p>";
        }
    }
}
?>

</body>
</html>
