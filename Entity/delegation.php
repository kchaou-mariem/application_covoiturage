<?php

class Delegation {
    private $idDelegation;
    private $name;
    
    // Constructeur
    public function __construct($idDelegation = null, $name = null) {
        $this->idDelegation = $idDelegation;
        $this->name = $name;
    }
    
    // Getters
    public function getIdDelegation() {
        return $this->idDelegation;
    }
    
    public function getName() {
        return $this->name;
    }
    
    // Setters
    public function setName($name) {
        $this->name = $name;
    }
    
    // Méthode pour afficher l'objet
    public function __toString() {
        return "Delegation [ID: " . $this->idDelegation . ", Name: " . $this->name . "]";
    }
    
    // Validation des données
    public function isValid() {
        return !empty($this->name) && strlen($this->name) <= 100;
    }
}