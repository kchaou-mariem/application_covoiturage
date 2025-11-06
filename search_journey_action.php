<?php
include 'connexion.php';

$departure = $_POST['departure_city'] ?? '';
$destination = $_POST['destination_city'] ?? '';
$date = $_POST['date'] ?? '';
$seats = $_POST['seats'] ?? '';

if ($departure == $destination) {
    echo "Departure and destination must be different.";
    exit;
}

$sql = "SELECT j.*, 
               c1.name AS departure_city, 
               c2.name AS destination_city, 
               car.model AS car_model, 
               car.color AS car_color, 
               car.seats AS car_seats
        FROM journey j
        LEFT JOIN city c1 ON j.departure = c1.idCity
        LEFT JOIN city c2 ON j.destination = c2.idCity
        LEFT JOIN car ON j.immatCar = car.immat
        WHERE j.departure = '$departure' 
          AND j.destination = '$destination'";


//Si l’utilisateur a renseigné une date ou un nombre de sièges, on ajoute ces conditions à la requête.
if (!empty($date)) { 
    $sql .= " AND j.depDate >= '$date'";
}
if (!empty($seats)) {
    $sql .= " AND j.nbSeats >= '$seats'";
}

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Database error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo "<h3>Results:</h3>";
    while ($row = mysqli_fetch_assoc($result)) {
        $carModel = $row['car_model'] ?? '-';
        $carColor = $row['car_color'] ?? '-';
        $carSeats = $row['car_seats'] ?? '-';

        echo "From: {$row['departure_city']}<br>";
        echo "To: {$row['destination_city']}<br>";
        echo "Date: {$row['depDate']} {$row['depTime']}<br>";
        echo "Available Seats: {$row['nbSeats']}<br>";
        echo "Price: {$row['price']} DT<br>";
        echo "Car: {$carModel} - {$carColor} ({$carSeats} seats)<br>";

        if (!empty($row['preferences'])) {
            echo "Preferences:<br>";
            $prefs = json_decode($row['preferences'], true);
            if (is_array($prefs)) {
                foreach ($prefs as $key => $val) {
                    echo "- $val<br>";
                }
            } else {
                echo $row['preferences'] . "<br>";
            }
        }
        echo "<hr>";
    }
} else {
    echo "No journeys found matching your criteria.";
}
?>
