<?php
require_once 'Entity/booking.php';
require_once 'config/connexion.php';

class BookingManager {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Count bookings for a given journey
     */
    public function countBookingsForJourney($idJourney) {
        $sql = "SELECT COUNT(*) AS total FROM booking WHERE idJourney = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('DB prepare failed: ' . $this->conn->error);
        $stmt->bind_param('i', $idJourney);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = 0;
        if ($row = $result->fetch_assoc()) {
            $count = (int)$row['total'];
        }
        $stmt->close();
        return $count;
    }

    /**
     * ✅ Calculate total seats booked for a journey
     */
    public function getTotalSeatsBooked($idJourney) {
        $sql = "SELECT SUM(requestedSeats) AS totalSeats FROM booking WHERE idJourney = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('DB prepare failed: ' . $this->conn->error);
        $stmt->bind_param('i', $idJourney);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalSeats = 0;
        if ($row = $result->fetch_assoc()) {
            $totalSeats = (int)($row['totalSeats'] ?? 0);
        }
        $stmt->close();
        return $totalSeats;
    }

    /**
     * Get bookings for a specific journey with requester contact info
     * Returns an array of arrays with keys: 'booking' (Booking object) and 'user' (assoc with firstName,lastName,email,phone,cin)
     */
    public function getBookingsForJourney($idJourney) {
        $sql = "SELECT b.*, u.firstName, u.lastName, u.email, u.phone, u.cin
                FROM booking b
                LEFT JOIN users u ON b.cinRequester = u.cin
                WHERE b.idJourney = ?
                ORDER BY b.bookingDate DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('DB prepare failed: ' . $this->conn->error);
        $stmt->bind_param('i', $idJourney);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($r = $result->fetch_assoc()) {
            $booking = new Booking($r['idJourney'], $r['cinRequester'], $r['requestedSeats'], $r['totalPrice']);
            $booking->setIdBooking($r['idBooking']);
            $booking->setBookingDate($r['bookingDate']);

            $rows[] = [
                'booking' => $booking,
                'user' => [
                    'firstName' => $r['firstName'] ?? null,
                    'lastName' => $r['lastName'] ?? null,
                    'email' => $r['email'] ?? null,
                    'phone' => $r['phone'] ?? null,
                    'cin' => $r['cinRequester'] ?? null,
                ]
            ];
        }

        $stmt->close();
        return $rows;
    }

    /**
     * Ajouter une nouvelle réservation
     */
    public function addBooking(Booking $booking) {
        $sql = "INSERT INTO Booking (idJourney, cinRequester, requestedSeats, totalPrice, bookingDate) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
            $idJourney = $booking->getIdJourney();
            $cinRequester = $booking->getCinRequester();
            $requestedSeats = $booking->getRequestedSeats();
            $totalPrice = $booking->getTotalPrice();
            $bookingDate = $booking->getBookingDate();

            $stmt->bind_param("isids", $idJourney, $cinRequester, $requestedSeats, $totalPrice, $bookingDate);
                if ($stmt->execute()) {
            $bookingId = $this->conn->insert_id;
            $booking->setIdBooking($bookingId);
            $stmt->close();
            return $bookingId;
        } else {
            $stmt->close();
            throw new Exception("Erreur lors de l'ajout de la réservation: " . $this->conn->error);
        }
    }

    /**
     * Récupérer les réservations d'un utilisateur
     */
    public function getUserBookings($cin) {
        $sql = "SELECT b.*, j.depDate, j.depTime, j.price as pricePerSeat,
                       c1.name as departure_city, c2.name as destination_city
                FROM booking b
                JOIN journey j ON b.idJourney = j.idJourney
                JOIN city c1 ON j.departure = c1.idCity
                JOIN city c2 ON j.destination = c2.idCity
                WHERE b.cinRequester = ?
                ORDER BY j.depDate DESC, j.depTime DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $cin);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $booking = new Booking(
                $row['idJourney'],
                $row['cinRequester'],
                $row['requestedSeats'],
                $row['totalPrice']
            );
            $booking->setIdBooking($row['idBooking']);
            $booking->setBookingDate($row['bookingDate']);
            
            // Ajouter les données supplémentaires
            $bookingData = [
                'booking' => $booking,
                'journey_details' => [
                    'depDate' => $row['depDate'],
                    'depTime' => $row['depTime'],
                    'pricePerSeat' => $row['pricePerSeat'],
                    'departure_city' => $row['departure_city'],
                    'destination_city' => $row['destination_city']
                ]
            ];
            
            $bookings[] = $bookingData;
        }

        $stmt->close();
        return $bookings;
    }

    /**
     * Vérifier la disponibilité des places
     */
    public function checkSeatAvailability($idJourney, $requestedSeats) {
        $sql = "SELECT nbSeats FROM journey WHERE idJourney = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idJourney);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt->close();
            throw new Exception("Trajet non trouvé");
        }

        $journey = $result->fetch_assoc();
        $availableSeats = $journey['nbSeats'];
        $stmt->close();

        return $requestedSeats <= $availableSeats;
    }

    /**
     * Mettre à jour les places disponibles
     */
    public function updateAvailableSeats($idJourney, $seatsToDeduct) {
        $sql = "UPDATE journey SET nbSeats = nbSeats - ? WHERE idJourney = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $seatsToDeduct, $idJourney);
        
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Erreur lors de la mise à jour des places: " . $this->conn->error);
        }
        
        $stmt->close();
        return true;
    }

    /**
     * Annuler une réservation
     */
    public function cancelBooking($idBooking, $cinRequester) {
        // Récupérer les infos de la réservation avant suppression
        $sql = "SELECT idJourney, requestedSeats FROM booking WHERE idBooking = ? AND cinRequester = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $idBooking, $cinRequester);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt->close();
            throw new Exception("Réservation non trouvée");
        }

        $booking = $result->fetch_assoc();
        $stmt->close();

        // Restaurer les places
        $this->updateAvailableSeats($booking['idJourney'], -$booking['requestedSeats']);

        // Supprimer la réservation
        $sql = "DELETE FROM booking WHERE idBooking = ? AND cinRequester = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $idBooking, $cinRequester);
        
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
?>