<?php
session_start();
require_once 'config/connexion.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.php');
    exit();
}

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid booking ID";
    header('Location: list_booking.php');
    exit();
}

$bookingId = intval($_GET['id']);
$cinRequester = $_SESSION['user_cin'];

// Vérifier que la réservation appartient bien à l'utilisateur
$checkQuery = $conn->prepare("
    SELECT b.*, j.depDate, j.depTime 
    FROM booking b
    JOIN journey j ON b.idJourney = j.idJourney
    WHERE b.idBooking = ? AND b.cinRequester = ?
");
$checkQuery->bind_param("is", $bookingId, $cinRequester);
$checkQuery->execute();
$result = $checkQuery->get_result();
$booking = $result->fetch_assoc();
$checkQuery->close();

if (!$booking) {
    $_SESSION['error'] = "Booking not found or you don't have permission to delete it";
    header('Location: list_booking.php');
    exit();
}

// Vérifier que la réservation n'est pas dans le passé
$journeyDateTime = strtotime($booking['depDate'] . ' ' . $booking['depTime']);
$now = time();

if ($journeyDateTime < $now) {
    $_SESSION['error'] = "Cannot cancel past bookings";
    header('Location: list_booking.php');
    exit();
}

// Commencer la transaction
$conn->begin_transaction();

try {
    // Remettre les places disponibles dans le trajet
    $updateSeatsQuery = $conn->prepare("
        UPDATE journey 
        SET nbSeats = nbSeats + ? 
        WHERE idJourney = ?
    ");
    $updateSeatsQuery->bind_param("ii", $booking['requestedSeats'], $booking['idJourney']);
    $updateSeatsQuery->execute();
    $updateSeatsQuery->close();
    
    // Supprimer la réservation
    $deleteQuery = $conn->prepare("DELETE FROM booking WHERE idBooking = ?");
    $deleteQuery->bind_param("i", $bookingId);
    $deleteQuery->execute();
    $deleteQuery->close();
    
    // Confirmer la transaction
    $conn->commit();
    
    $_SESSION['success'] = "Booking cancelled successfully. " . $booking['requestedSeats'] . " seat(s) have been released.";
    header('Location: list_booking.php');
    exit();
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
    header('Location: list_booking.php');
    exit();
}
?>
