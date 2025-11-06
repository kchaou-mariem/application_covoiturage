<?php

class PreferenceManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Créer une préférence
    public function create(Preference $preference) {
        $query = $this->db->prepare("INSERT INTO preferences (label) VALUES (:label)");
        $query->bindValue(':label', $preference->getLabel());
        
        return $query->execute();
    }
    
    // Lire une préférence par ID
    public function read($idPref) {
        $query = $this->db->prepare("SELECT * FROM preferences WHERE idPref = :idPref");
        $query->bindValue(':idPref', $idPref, PDO::PARAM_INT);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return new Preference($data['idPref'], $data['label']);
        }
        
        return null;
    }
    
    // Mettre à jour une préférence
    public function update(Preference $preference) {
        $query = $this->db->prepare("UPDATE preferences SET label = :label WHERE idPref = :idPref");
        $query->bindValue(':label', $preference->getLabel());
        $query->bindValue(':idPref', $preference->getIdPref(), PDO::PARAM_INT);
        
        return $query->execute();
    }
    
    // Supprimer une préférence
    public function delete($idPref) {
        $query = $this->db->prepare("DELETE FROM preferences WHERE idPref = :idPref");
        $query->bindValue(':idPref', $idPref, PDO::PARAM_INT);
        
        return $query->execute();
    }
    
    // Récupérer toutes les préférences
    public function findAll() {
        $query = $this->db->query("SELECT * FROM preferences ORDER BY label");
        $preferences = [];
        
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $preferences[] = new Preference($data['idPref'], $data['label']);
        }
        
        return $preferences;
    }
    
    // Rechercher par label
    public function findByLabel($label) {
        $query = $this->db->prepare("SELECT * FROM preferences WHERE label LIKE :label");
        $query->bindValue(':label', '%' . $label . '%');
        $query->execute();
        
        $preferences = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $preferences[] = new Preference($data['idPref'], $data['label']);
        }
        
        return $preferences;
    }
    
    // Compter le nombre total de préférences
    public function count() {
        $query = $this->db->query("SELECT COUNT(*) FROM preferences");
        return $query->fetchColumn();
    }
}