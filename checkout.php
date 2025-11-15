<?php
session_start();
require_once 'config/connexion.php';
require_once 'Entity/booking.php';
require_once 'Manager/booking_manager.php';

// Helper pour log dans console
function console_log($msg) {
    echo "<script>console.log(" . json_encode($msg) . ");</script>";
}

// 1. Vérifier la connexion à la base de données
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
console_log("Database connection OK");

// 2. CIN
$_SESSION['cin'] = "11163595";
$cinRequester = $_SESSION['cin'];
console_log("CIN in session: $cinRequester");

// 3. Vérifier l'utilisateur
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    die("❌ Table 'users' does not exist!");
}

$sql = "SELECT cin, firstName, lastName FROM users WHERE cin = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cinRequester);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ User with CIN '$cinRequester' does not exist.");
}
$user = $result->fetch_assoc();
console_log("User found: " . $user['firstName'] . " " . $user['lastName']);

// 4. Vérifier panier
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    die("❌ Your cart is empty.");
}
console_log("Cart has " . count($_SESSION['cart']) . " item(s)");

// 5. Vérifier méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Invalid access. Submit the form from the cart page.");
}

$bookingManager = new BookingManager($conn);
$messages = [];
$successCount = 0;

$conn->begin_transaction();
try {
    foreach ($_SESSION['cart'] as $idJourney) {
        $seatsField = "seats_$idJourney";
        if (!isset($_POST[$seatsField])) {
            $messages[] = "Seats not specified for journey $idJourney.";
            continue;
        }

        $requestedSeats = intval($_POST[$seatsField]);
        if ($requestedSeats <= 0) {
            $messages[] = "Invalid seats number for journey $idJourney.";
            continue;
        }

        // Vérifier disponibilité
        if (!$bookingManager->checkSeatAvailability($idJourney, $requestedSeats)) {
            $messages[] = "Not enough seats available for journey $idJourney.";
            continue;
        }

        // Calcul du prix
        $sql = "SELECT price FROM journey WHERE idJourney = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idJourney);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $pricePerSeat = $row['price'];
        $totalPrice = $requestedSeats * $pricePerSeat;

        // Créer réservation
        $booking = new Booking($idJourney, $cinRequester, $requestedSeats, $totalPrice);
        $bookingManager->addBooking($booking);

        // Mettre à jour les places
        $bookingManager->updateAvailableSeats($idJourney, $requestedSeats);

        $messages[] = "Booking completed for journey $idJourney - $requestedSeats seat(s)";
        $successCount++;
        console_log("Successfully processed journey $idJourney");
    }

    $conn->commit();
    if ($successCount > 0) unset($_SESSION['cart']);

} catch (Exception $e) {
    $conn->rollback();
    $messages[] = "Transaction failed: " . $e->getMessage();
    console_log("Transaction rolled back due to error: " . $e->getMessage());
}

// Affichage interface (sans debug)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Booking Summary</title>
<style>
/* ton CSS existant */
</style>
</head>
<body>
<div class="container">
<h2>📋 Booking Summary</h2>
<div class="summary">
<strong>Total bookings processed:</strong> <?php echo $successCount; ?><br>
<strong>User:</strong> <?php echo $user['firstName'] . ' ' . $user['lastName']; ?> (CIN: <?php echo $cinRequester; ?>)
</div>
<div class="messages">
<?php foreach ($messages as $message): ?>
<div class="message-item <?php echo strpos($message, 'completed') !== false ? 'success' : 'error'; ?>">
<?php echo $message; ?>
</div>
<?php endforeach; ?>
</div>
<a href="<?php echo $successCount>0 ? 'trajets.php' : 'cart.php'; ?>" class="btn">
<?php echo $successCount>0 ? '🏠 Return to Home Page' : '🛒 Return to Cart'; ?>
</a>
</div>
</body>
</html>
