<?php
session_start();
require_once 'config/connexion.php';
require_once 'Entity/booking.php';
require_once 'Manager/booking_manager.php';

// Helper pour log dans console
function console_log($msg) {
    // Log to server error log instead of sending output to the browser
    if (is_array($msg) || is_object($msg)) {
        error_log(json_encode($msg));
    } else {
        error_log($msg);
    }
}

// 1. Vérifier la connexion à la base de données
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
console_log("Database connection OK");


// 2. Vérifier l'utilisateur
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    die("❌ Table 'users' does not exist!");
}

// 3. CIN
// Use the logged-in user's CIN stored in session as 'user_cin'
if (session_status() == PHP_SESSION_NONE) session_start();
$cinRequester = $_SESSION['user_cin'] ?? null;
console_log("CIN in session: $cinRequester");


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
$lastBookingId = null;

$conn->begin_transaction();
try {
    foreach ($_SESSION['cart'] as $idJourney) {
        // Build a human-friendly label for this journey (Dep → Dest) to avoid showing raw IDs in UI
        $label = 'Journey ' . $idJourney;
        $q = $conn->prepare("SELECT c1.name AS dep, c2.name AS dest FROM journey j JOIN city c1 ON j.departure = c1.idCity JOIN city c2 ON j.destination = c2.idCity WHERE j.idJourney = ?");
        if ($q) {
            $q->bind_param('i', $idJourney);
            $q->execute();
            $r = $q->get_result()->fetch_assoc();
            if ($r) $label = htmlspecialchars(($r['dep'] ?? 'Départ') . ' → ' . ($r['dest'] ?? 'Destination'));
            $q->close();
        }
        $seatsField = "seats_$idJourney";
        if (!isset($_POST[$seatsField])) {
            $messages[] = "Seats not specified for journey: $label.";
            continue;
        }

        $requestedSeats = intval($_POST[$seatsField]);
        if ($requestedSeats <= 0) {
            $messages[] = "Invalid seats number for journey: $label.";
            continue;
        }

        // Vérifier disponibilité
        if (!$bookingManager->checkSeatAvailability($idJourney, $requestedSeats)) {
            $messages[] = "Not enough seats available for journey: $label.";
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
        $bookingId = $bookingManager->addBooking($booking);
        
        // Stocker le dernier ID de réservation pour la redirection
        $lastBookingId = $bookingId;

        // Mettre à jour les places
        $bookingManager->updateAvailableSeats($idJourney, $requestedSeats);

        $messages[] = "Booking completed for journey $label - $requestedSeats place(s)";
        $successCount++;
        console_log("Successfully processed journey $idJourney");
    }

    $conn->commit();
    if ($successCount > 0) {
        unset($_SESSION['cart']);
        // Rediriger vers la page de confirmation avec le dernier ID de réservation
        if ($lastBookingId) {
            header('Location: booking_confirmation.php?id=' . $lastBookingId);
        } else {
            header('Location: list_booking.php?success=1');
        }
        exit();
    }

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
<strong>Bookings processed:</strong> <?php echo $successCount; ?><br>
<strong>User:</strong> <?php echo $user['firstName'] . ' ' . $user['lastName']; ?> (CIN: <?php echo $cinRequester; ?>)
</div>
<div class="messages">
<?php foreach ($messages as $message): ?>
<div class="message-item <?php echo strpos($message, 'completed') !== false ? 'success' : 'error'; ?>">
<?php echo $message; ?>
</div>
<?php endforeach; ?>
</div>
<a href="<?php echo $successCount>0 ? 'trajets.php' : 'cart.php'; ?>" class="btn btn-primary">
<?php echo $successCount>0 ? '🏠 Return to Journeys' : '🛒 Return to Cart'; ?>
</a>
</div>
</body>
</html>
