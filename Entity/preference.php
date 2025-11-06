<?php

class Preference {
    private $idPref;
    private $label;
    
    // Constructeur
    public function __construct($idPref = null, $label = null) {
        $this->idPref = $idPref;
        $this->label = $label;
    }
    
    // Getters
    public function getIdPref() {
        return $this->idPref;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    // Setters
    public function setLabel($label) {
        $this->label = $label;
    }
    
    // Méthode pour afficher l'objet
    public function __toString() {
        return "Preference [ID: " . $this->idPref . ", Label: " . $this->label . "]";
    }
}