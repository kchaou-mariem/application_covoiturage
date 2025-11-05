<?php
include 'connexion.php'; 
//Récupérer les infos d’une voiture existante
//javascript fetch vers add_journey.php avec immat en GET
if (isset($_GET['immat'])) {
    $immat = trim($_GET['immat']);
    $stmt = $conn->prepare("SELECT model, color, seats FROM car WHERE immat = ?");
    if (!$stmt) { 
        header('Content-Type: application/json; charset=utf-8'); // Important pour indiquer que la réponse est en JSON
        echo json_encode(new stdClass()); // renvoie {} en cas d'erreur
        exit;
    }
    $stmt->bind_param("s", $immat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    header('Content-Type: application/json; charset=utf-8');// Important pour indiquer que la réponse est en JSON
    echo json_encode($data ?: new stdClass()); // renvoie {} si pas trouvé
    exit; // très important : stoppe l'exécution pour ne pas envoyer du HTML ensuite
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $departure = $_POST['departure_city'];
    $departureDelegation = $_POST['departure_delegation'];
    $destination = $_POST['destination_city'];
    $destinationDelegation = $_POST['destination_delegation'];
    $departureDate = $_POST['date'];
    $departureTime = $_POST['time'];
    $seatsA = $_POST['seatsA'];
    $price = $_POST['price'];

    // --- Ajouter nouvelle ville si nécessaire ---
if ($departure === 'new' && !empty($_POST['new_departure_city'])) {
    $newCity = trim($_POST['new_departure_city']);
    $stmt = $conn->prepare("INSERT INTO city (name) VALUES (?)");
    $stmt->bind_param("s", $newCity); //s:string , remplacer ? dans la requete par $newCity
    $stmt->execute();
    $departure = $stmt->insert_id;
    //$departure = $newCity;
    $stmt->close();
}
if ($destination === 'new' && !empty($_POST['new_destination_city'])) {
    $newCityDest = trim($_POST['new_destination_city']);
    $stmt = $conn->prepare("INSERT INTO city (name) VALUES (?)"); 
    $stmt->bind_param("s", $newCityDest);
    $stmt->execute();
    $destination = $stmt->insert_id;
    $stmt->close();
}
// --- Ajouter nouvelle délégation si nécessaire ---
if ($departureDelegation === 'new' && !empty($_POST['new_departure_delegation'])) {
    $newDelegation = trim($_POST['new_departure_delegation']);
    $stmt = $conn->prepare("INSERT INTO delegation (name) VALUES (?)");
    $stmt->bind_param("s", $newDelegation);
    $stmt->execute();
    $departureDelegation =$stmt->insert_id;
    $stmt->close();
}
if ($destinationDelegation === 'new' && !empty($_POST['new_destination_delegation'])) {
    $newDelegationDest = trim($_POST['new_destination_delegation']);
    $stmt = $conn->prepare("INSERT INTO delegation (name) VALUES (?)");
    $stmt->bind_param("s", $newDelegationDest);
    $stmt->execute();
    $destinationDelegation = $stmt->insert_id;
    $stmt->close();
}

// --- GÉRER LA VOITURE ---
    $hasCar = isset($_POST['hasCar']);
    if ($hasCar && !empty($_POST['immat_existing'])) {
        $immat = trim($_POST['immat_existing']);

        // Vérifier si la voiture existe 
        //C’est le serveur PHP qui reçoit directement les données du formulaire via POST
        //on fait de nouveau une vérification côté serveur car on ne fait pas confiance au premiere javascript fetch (user peut ne pas cliquer sur le bouton load info avant post de formulaire)
        $checkCar = $conn->prepare("SELECT immat FROM car WHERE immat = ?");
        $checkCar->bind_param("s", $immat);
        $checkCar->execute();
        $resCheck = $checkCar->get_result();

        if ($resCheck->num_rows == 0) {
            echo "<script>alert('No car found with this license plate.');</script>";
            exit;
        }
    } else {
        // Nouvelle voiture
        $model = $_POST['carModel'] ?? '';
        $immat = isset($_POST['immat']) ? trim($_POST['immat']) : '';
        $color = $_POST['color'] ?? '';
        $carSeats = isset($_POST['seats']) ? (int)$_POST['seats'] : 0;

        $insertCar = $conn->prepare("INSERT INTO car (model, immat, color, seats) VALUES (?, ?, ?, ?)");
        $insertCar->bind_param("sssi", $model, $immat, $color, $carSeats);
        $insertCar->execute();
        $insertCar->close();
    }

    //preferneces

    // --- Préférences sélectionnées ---
    $preferences = isset($_POST['options']) ? $_POST['options'] : [];
    $driverGender = $_POST['driverGender'] ?? null;
    // ajouter le genre du conducteur si choisi
    if ($driverGender) {
        $preferences[] = "driver_" . $driverGender; 
    }
    // convertir en JSON pour stockage
    $preferencesJson = json_encode($preferences, JSON_UNESCAPED_UNICODE); //liste sera stockée en JSON dans la base de données


   // -------------- VALIDATIONS DES DONNÉES (conditions) --------------- //

    // Vérifier que le prix est positif
    if ($price < 0) {
        echo "<script>alert('❌ Le prix ne peut pas être négatif !');</script>";
        exit;
    }

    // Vérifier que la ville de départ et la destination sont différentes
    if ($departure == $destination) {
        echo "<script>alert('❌ La ville de départ et la destination doivent être différentes !');</script>";
        exit;
    }

    // Vérifier que la date de départ n’est pas dans le passé
    $today = date('Y-m-d');
    if ($departureDate < $today) {
        echo "<script>alert('❌ La date de départ ne peut pas être antérieure à aujourd’hui !');</script>";
        exit;
    }

    // Vérifier que le nombre de sièges disponibles ne dépasse pas le nombre de sièges de la voiture
    // Si la voiture existe déjà :
    if ($hasCar && !empty($_POST['immat_existing'])) {
        $checkSeats = $conn->prepare("SELECT seats FROM car WHERE immat = ?");
        $checkSeats->bind_param("s", $immat);
        $checkSeats->execute();
        $res = $checkSeats->get_result();
        if ($carRow = $res->fetch_assoc()) {
            if ($seatsA > $carRow['seats']) {
                echo "<script>alert('❌ Le nombre de places disponibles ne peut pas dépasser le nombre de sièges de la voiture !');</script>";
                exit;
            }
        }
        $checkSeats->close();
    } 
    // Si c’est une nouvelle voiture :
    else {
        if ($seatsA > $carSeats) {
            echo "<script>alert('❌ Le nombre de places disponibles ne peut pas dépasser le nombre de sièges de la voiture !');</script>";
            exit;
        }
    }


    // --- Insérer le trajet ---
    $sql = "INSERT INTO journey (departure, destination, depDate, depTime, nbSeats, price, departureDelegation, destinationDelegation, immatCar, preferences)
            VALUES ('$departure', '$destination', '$departureDate', '$departureTime', $seatsA, $price,'$departureDelegation','$destinationDelegation', '$immat', '$preferencesJson')";

    if ($conn->query($sql) === TRUE) {
    echo "<script>alert('journey is created ✅');</script>";
    } else {
        echo "<script>alert('Error : " . addslashes($conn->error) . "');</script>";
    }
}
?>
