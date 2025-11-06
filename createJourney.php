<?php
require_once 'config/connexion.php';
require_once 'Entity/City.php';
require_once 'Entity/Delegation.php';
require_once 'Entity/Car.php';
require_once 'Entity/Journey.php';
require_once 'Entity/Preference.php';
require_once 'Manager/CityManager.php';
require_once 'Manager/DelegationManager.php';
require_once 'Manager/CarManager.php';
require_once 'Manager/JourneyManager.php';
require_once 'Manager/PreferenceManager.php';
require_once 'Manager/carManager.php';

$cityManager = new CityManager($conn);
$delegationManager = new DelegationManager($conn);
$carManager = new CarManager($conn);
$journeyManager = new JourneyManager($conn);
$preferenceManager = new PreferenceManager($conn);

// --- Requête AJAX pour voiture existante ---
if (isset($_GET['immat'])) {
    $immat = trim($_GET['immat']);
    $car = $carManager->findByImmat($immat);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($car ? [
        'model' => $car->getModel(),
        'color' => $car->getColor(),
        'seats' => $car->getSeats()
    ] : new stdClass());
    exit;
}

// --- Traitement du formulaire ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departure = $_POST['departure_city'];
    $departureDelegation = $_POST['departure_delegation'];
    $destination = $_POST['destination_city'];
    $destinationDelegation = $_POST['destination_delegation'];
    $departureDate = $_POST['date'];
    $departureTime = $_POST['time'];
    $seatsA = (int)$_POST['seatsA'];
    $price = (float)$_POST['price'];

    // === Gestion des nouvelles villes ===
    if ($departure === 'new' && !empty($_POST['new_departure_city'])) {
        $city = new City(null, trim($_POST['new_departure_city']));
        if (!$cityManager->nameExists($city->getName())) {
            $cityManager->create($city);
        }
        $departure = $cityManager->getIdCityByName($city->getName());
    }

    if ($destination === 'new' && !empty($_POST['new_destination_city'])) {
        $city = new City(null, trim($_POST['new_destination_city']));
        if (!$cityManager->existsByName($city->getName())) {
            $cityManager->create($city);
        }
        $destination = $cityManager->getIdCityByName($city->getName());
    }

    // === Gestion des délégations ===
    if ($departureDelegation === 'new' && !empty($_POST['new_departure_delegation'])) {
        $deleg = new Delegation(null, trim($_POST['new_departure_delegation']));
        $delegationManager->create($deleg);
        $departureDelegation = $delegationManager->getIdByName($deleg->getName());
    }

    if ($destinationDelegation === 'new' && !empty($_POST['new_destination_delegation'])) {
        $deleg = new Delegation(null, trim($_POST['new_destination_delegation']));
        $delegationManager->create($deleg);
        $destinationDelegation = $delegationManager->getIdByName($deleg->getName());
    }

    // === Gestion voiture ===
    $immat = '';
    $hasCar = isset($_POST['hasCar']);
    if ($hasCar && !empty($_POST['immat_existing'])) {
        $immat = trim($_POST['immat_existing']);
        if (!$carManager->existsByImmat($immat)) {
            echo "<script>alert('❌ No car found with this license plate.');</script>";
            exit;
        }
    } else {
        $model = $_POST['carModel'];
        $immat = trim($_POST['immat']);
        $color = $_POST['color'];
        $seats = (int)$_POST['seats'];

        $newCar = new Car($immat, $model, $color, $seats);
        $carManager->create($newCar);
    }

    // === Préférences ===
    $preferences = isset($_POST['options']) ? $_POST['options'] : [];
    if (!empty($_POST['driverGender'])) {
        $preferences[] = "driver_" . $_POST['driverGender'];
    }
    $preferencesJson = json_encode($preferences, JSON_UNESCAPED_UNICODE);

    // === Validations ===
    if ($price < 0) {
        echo "<script>alert('❌ Price cannot be negative!');</script>";
        exit;
    }

    if ($departure == $destination) {
        echo "<script>alert('❌ Departure and destination cannot be the same!');</script>";
        exit;
    }

    $today = date('Y-m-d');
    if ($departureDate < $today) {
        echo "<script>alert('❌ Departure date cannot be in the past!');</script>";
        exit;
    }

    $carSeats = $carManager->getSeatsByImmat($immat);
    if ($seatsA > $carSeats) {
        echo "<script>alert('❌ Seats available cannot exceed car seats!');</script>";
        exit;
    }

    // === Enregistrer le trajet ===
    $journey = new Journey(
    null,                // idJourney
    $price,              // ✅ price
    $seatsA,             // ✅ nbSeats
    $departureDate,      // ✅ depDate
    $departureTime,      // ✅ depTime
    $departure,          // ✅ departure
    $destination,        // ✅ destination
    $departureDelegation,// ✅ departureDelegation
    $destinationDelegation, // ✅ destinationDelegation
    $immat,              // ✅ immatCar
    $preferencesJson     // ✅ preferences (JSON)
);


    if ($journeyManager->create($journey)) {
        echo "<script>alert('✅ Journey created successfully!');</script>";
    } else {
        echo "<script>alert('❌ Error creating journey.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Journey</title>
    <script>
        function toggleNewCity(type) {
            const select = document.getElementById(type + '_city');
            const div = document.getElementById('new_' + type + '_city');
            div.style.display = (select.value === 'new') ? 'block' : 'none';
        }

        function toggleNewDelegation(type) {
            const select = document.getElementById(type + '_delegation');
            const div = document.getElementById('new_' + type + '_delegation');
            div.style.display = (select.value === 'new') ? 'block' : 'none';
        }

        function toggleCarFields() {
            const hasCar = document.getElementById('hasCar').checked;
            document.getElementById('existingCar').style.display = hasCar ? 'block' : 'none';
            document.getElementById('newCar').style.display = hasCar ? 'none' : 'block';
        }

        function fetchCarInfo() {
            const immat = document.getElementById('immat_existing').value.trim();
            if (!immat) {
                alert("Please enter a license plate!");
                return;
            }
            fetch('createjourney.php?immat=' + encodeURIComponent(immat))
                .then(response => response.json())
                .then(data => {
                    const display = document.getElementById('car_info_display');
                    if (data && data.model) {
                        display.innerHTML = `<p><strong>Model:</strong> ${data.model}</p>
                                             <p><strong>Color:</strong> ${data.color}</p>
                                             <p><strong>Seats:</strong> ${data.seats}</p>`;
                    } else {
                        display.innerHTML = "<p style='color:red;'>No car found.</p>";
                    }
                })
                .catch(() => alert("Error loading car data."));
        }
    </script>
</head>
<body>
    <form method="POST" action="createJourney.php">
        <h2>Create Journey</h2>

        <h3>Departure :</h3>
        <label>City</label>
        <select name="departure_city" id="departure_city" onchange="toggleNewCity('departure')" required>
            <option value="">-- Choose city --</option>
            <?php foreach ($cityManager->findAll() as $city): ?>
                <option value="<?= $city->getIdCity() ?>"><?= htmlspecialchars($city->getName()) ?></option>
            <?php endforeach; ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_departure_city" style="display:none;">
            <input type="text" name="new_departure_city" placeholder="Enter new city">
        </div>

        <label>Delegation</label>
        <select name="departure_delegation" id="departure_delegation" onchange="toggleNewDelegation('departure')" required>
            <option value="">-- Choose delegation --</option>
            <?php foreach ($delegationManager->findAll() as $deleg): ?>
                <option value="<?= $deleg->getIdDelegation() ?>"><?= htmlspecialchars($deleg->getName()) ?></option>
            <?php endforeach; ?>
            <option value="new">+ Add new delegation</option>
        </select>
        <div id="new_departure_delegation" style="display:none;">
            <input type="text" name="new_departure_delegation" placeholder="Enter new delegation">
        </div>

        <h3>Destination :</h3>
        <label>City</label>
        <select name="destination_city" id="destination_city" onchange="toggleNewCity('destination')" required>
            <option value="">-- Choose city --</option>
            <?php foreach ($cityManager->findAll() as $city): ?>
                <option value="<?= $city->getIdCity() ?>"><?= htmlspecialchars($city->getName()) ?></option>
            <?php endforeach; ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_destination_city" style="display:none;">
            <input type="text" name="new_destination_city" placeholder="Enter new city">
        </div>

        <label>Delegation</label>
        <select name="destination_delegation" id="destination_delegation" onchange="toggleNewDelegation('destination')" required>
            <option value="">-- Choose delegation --</option>
            <?php foreach ($delegationManager->findAll() as $deleg): ?>
                <option value="<?= $deleg->getIdDelegation() ?>"><?= htmlspecialchars($deleg->getName()) ?></option>
            <?php endforeach; ?>
            <option value="new">+ Add new delegation</option>
        </select>
        <div id="new_destination_delegation" style="display:none;">
            <input type="text" name="new_destination_delegation" placeholder="Enter new delegation">
        </div>

        <br><label>Date</label>
        <input type="date" name="date" required>
        <label>Time</label>
        <input type="time" name="time" required>
        <label>Price</label>
        <input type="number" name="price" min="0" step="0.01" required>
        <label>Seats Available</label>
        <input type="number" name="seatsA" min="1" max="8" required>

        <h3>Car</h3>
        <label><input type="checkbox" id="hasCar" name="hasCar" onchange="toggleCarFields()"> I already registered my car</label>

        <div id="existingCar" style="display:none;">
            <input type="text" id="immat_existing" name="immat_existing" placeholder="Enter car plate">
            <button type="button" onclick="fetchCarInfo()">Load Info</button>
            <div id="car_info_display"></div>
        </div>

        <div id="newCar">
            <input type="text" name="carModel" placeholder="Car Model">
            <input type="text" name="immat" placeholder="License Plate">
            <input type="text" name="color" placeholder="Color">
            <input type="number" name="seats" min="1" max="8" placeholder="Seats">
        </div>

        <h3>Preferences</h3>
        <?php foreach ($preferenceManager->findAll() as $pref): ?>
            <label><input type="checkbox" name="options[]" value="<?= htmlspecialchars($pref->getLabel()) ?>"> <?= htmlspecialchars($pref->getLabel()) ?></label><br>
        <?php endforeach; ?>
        <!--<label><input type="radio" name="driverGender" value="male"> Male</label>
        <label><input type="radio" name="driverGender" value="female"> Female</label>-->

        <br><br><button type="submit">Create Journey</button>
    </form>
</body>
</html>
