<?php

class Journey {
    private $idJourney;
    private $price;
    private $nbSeats;
    private $depDate;
    private $depTime;
    private $departure;
    private $destination;
    private $departureDelegation;
    private $destinationDelegation;
    private $immatCar;
    private $preferences;
    
    // Constructeur
    public function __construct(
        $idJourney = null,
        $price = null,
        $nbSeats = null,
        $depDate = null,
        $depTime = null,
        $departure = null,
        $destination = null,
        $departureDelegation = null,
        $destinationDelegation = null,
        $immatCar = null,
        $preferences = null
    ) {
        $this->idJourney = $idJourney;
        $this->price = $price;
        $this->nbSeats = $nbSeats;
        $this->depDate = $depDate;
        $this->depTime = $depTime;
        $this->departure = $departure;
        $this->destination = $destination;
        $this->departureDelegation = $departureDelegation;
        $this->destinationDelegation = $destinationDelegation;
        $this->immatCar = $immatCar;
        $this->preferences = $preferences;
    }
    
    // Getters
    public function getIdJourney() {
        return $this->idJourney;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getNbSeats() {
        return $this->nbSeats;
    }
    
    public function getDepDate() {
        return $this->depDate;
    }
    
    public function getDepTime() {
        return $this->depTime;
    }
    
    public function getDeparture() {
        return $this->departure;
    }
    
    public function getDestination() {
        return $this->destination;
    }
    
    public function getDepartureDelegation() {
        return $this->departureDelegation;
    }
    
    public function getDestinationDelegation() {
        return $this->destinationDelegation;
    }
    
    public function getImmatCar() {
        return $this->immatCar;
    }
    
    public function getPreferences() {
        return $this->preferences;
    }
    
    // Setters
    public function setPrice($price) {
        $this->price = $price;
    }
    
    public function setNbSeats($nbSeats) {
        $this->nbSeats = $nbSeats;
    }
    
    public function setDepDate($depDate) {
        $this->depDate = $depDate;
    }
    
    public function setDepTime($depTime) {
        $this->depTime = $depTime;
    }
    
    public function setDeparture($departure) {
        $this->departure = $departure;
    }
    
    public function setDestination($destination) {
        $this->destination = $destination;
    }
    
    public function setDepartureDelegation($departureDelegation) {
        $this->departureDelegation = $departureDelegation;
    }
    
    public function setDestinationDelegation($destinationDelegation) {
        $this->destinationDelegation = $destinationDelegation;
    }
    
    public function setImmatCar($immatCar) {
        $this->immatCar = $immatCar;
    }
    
    public function setPreferences($preferences) {
        $this->preferences = $preferences;
    }
    
    // Méthodes utilitaires
    public function getDateTime() {
        return $this->depDate . ' ' . $this->depTime;
    }
    
    public function isFull() {
        return $this->nbSeats <= 0;
    }
    
    public function isValid() {
        return !empty($this->price) && 
               !empty($this->nbSeats) && 
               !empty($this->depDate) && 
               !empty($this->depTime) && 
               ($this->departure !== null || $this->departureDelegation !== null) &&
               ($this->destination !== null || $this->destinationDelegation !== null) &&
               !empty($this->immatCar) &&
               $this->price >= 0 &&
               $this->nbSeats >= 1 &&
               $this->nbSeats <= 9; // Maximum 9 sièges pour une voiture
    }
    
    // Gestion des préférences JSON
    public function setPreferencesArray($preferencesArray) {
        $this->preferences = json_encode($preferencesArray, JSON_UNESCAPED_UNICODE);
    }
    
    public function getPreferencesArray() {
        if ($this->preferences && json_decode($this->preferences)) {
            return json_decode($this->preferences, true);
        }
        return [];
    }
    
    public function addPreference($preferenceId) {
        $preferences = $this->getPreferencesArray();
        if (!in_array($preferenceId, $preferences)) {
            $preferences[] = $preferenceId;
            $this->setPreferencesArray($preferences);
        }
    }
    
    public function removePreference($preferenceId) {
        $preferences = $this->getPreferencesArray();
        $key = array_search($preferenceId, $preferences);
        if ($key !== false) {
            unset($preferences[$key]);
            $this->setPreferencesArray(array_values($preferences));
        }
    }
    
    public function hasPreference($preferenceId) {
        $preferences = $this->getPreferencesArray();
        return in_array($preferenceId, $preferences);
    }
    
    // Méthode pour afficher l'objet
    public function __toString() {
        return "Journey [ID: " . $this->idJourney . 
               ", Price: " . $this->price . 
               ", Seats: " . $this->nbSeats . 
               ", Date: " . $this->depDate . 
               ", Time: " . $this->depTime . "]";
    }
    
    // Méthode pour obtenir un tableau des données
    public function toArray() {
        return [
            'idJourney' => $this->idJourney,
            'price' => $this->price,
            'nbSeats' => $this->nbSeats,
            'depDate' => $this->depDate,
            'depTime' => $this->depTime,
            'departure' => $this->departure,
            'destination' => $this->destination,
            'departureDelegation' => $this->departureDelegation,
            'destinationDelegation' => $this->destinationDelegation,
            'immatCar' => $this->immatCar,
            'preferences' => $this->preferences
        ];
    }
}