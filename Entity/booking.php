<?php
class Booking {
    private $idBooking;
    private $idJourney;
    private $cinRequester;
    private $requestedSeats;
    private $totalPrice;
    private $bookingDate;

    public function __construct($idJourney, $cinRequester, $requestedSeats, $totalPrice) {
        $this->idJourney = $idJourney;
        $this->cinRequester = $cinRequester;
        $this->requestedSeats = $requestedSeats;
        $this->totalPrice = $totalPrice;
        $this->bookingDate = date('Y-m-d H:i:s');
    }

    // Getters
    public function getIdBooking() { return $this->idBooking; }
    public function getIdJourney() { return $this->idJourney; }
    public function getCinRequester() { return $this->cinRequester; }
    public function getRequestedSeats() { return $this->requestedSeats; }
    public function getTotalPrice() { return $this->totalPrice; }
    public function getBookingDate() { return $this->bookingDate; }

    // Setters
    public function setIdBooking($id) { $this->idBooking = $id; }
    public function setBookingDate($date) { $this->bookingDate = $date; }
}
?>