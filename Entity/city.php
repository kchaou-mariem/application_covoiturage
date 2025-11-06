<?php

class City {
    private $idCity;
    private $name;
    
    // Constructeur
    public function __construct($idCity = null, $name = null) {
        $this->idCity = $idCity;
        $this->name = $name;
    }
    
    // Getters
    public function getIdCity() {
        return $this->idCity;
    }
    
    public function getName() {
        return $this->name;
    }
    
    // Setters
    public function setName($name) {
        $this->name = $name;
        
    }
    
    // Setter for the ID - used after inserting to populate the entity
    public function setIdCity($id) {
        $this->idCity = $id;
    }

 
    // Méthode pour afficher l'objet
    public function __toString() {
        return "City [ID: " . $this->idCity . ", Name: " . $this->name . "]";
    }
    
    // Validation des données
    public function isValid() {
        return !empty($this->name) && strlen($this->name) <= 50;
    }
    
    // Méthode pour obtenir un tableau des données
    public function toArray() {
        return [
            'idCity' => $this->idCity,
            'name' => $this->name
        ];
    }
}