<?php
session_start();
require_once 'config/connexion.php';
require_once 'Manager/JourneyManager.php';
require_once 'Manager/booking_manager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_trajets.php');
    exit;
}

if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.html');
    exit;
}

$id = intval($_POST['idJourney'] ?? 0);
if ($id <= 0) {
    header('Location: my_trajets.php?msg=' . urlencode('Invalid journey id'));
    exit;
}

$journeyManager = new JourneyManager($conn);
$bookingManager = new BookingManager($conn);

$journey = $journeyManager->read($id);
if (!$journey) {
    header('Location: my_trajets.php?msg=' . urlencode('Trajet introuvable'));
    exit;
}

// Ensure the current user is the owner
if ($journey->getCinRequester() !== $_SESSION['user_cin']) {
    header('Location: my_trajets.php?msg=' . urlencode('Vous n\'êtes pas autorisé à supprimer ce trajet'));
    exit;
}

$count = $bookingManager->countBookingsForJourney($id);
if ($count > 0) {
    header('Location: my_trajets.php?msg=' . urlencode('Impossible de supprimer : il y a des réservations pour ce trajet'));
    exit;
}

try {
    // Get journey details before deletion for email
    require_once 'Manager/CityManager.php';
    $cityManager = new CityManager($conn);
    
    $depCity = $cityManager->findById($journey->getDeparture());
    $destCity = $cityManager->findById($journey->getDestination());
    
    $journeyDetails = [
        'from' => $depCity ? $depCity->getName() : 'Unknown',
        'to' => $destCity ? $destCity->getName() : 'Unknown',
        'date' => $journey->getDepDate(),
        'time' => substr($journey->getDepTime(), 0, 5)
    ];
    
    // Delete the journey
    $journeyManager->delete($id);
    
    header('Location: my_trajets.php?msg=' . urlencode('Trajet supprimé avec succès'));
    exit;
} catch (Exception $e) {
    header('Location: my_trajets.php?msg=' . urlencode('Erreur lors de la suppression: ' . $e->getMessage()));
    exit;
}
