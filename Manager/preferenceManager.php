<?php

class PreferenceManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Créer une préférence
    public function create(Preference $preference) {
        $stmt = $this->db->prepare("INSERT INTO preferences (label) VALUES (?)");
        $label = $preference->getLabel();
        $stmt->bind_param("s", $label);
        return $stmt->execute();
    }
    
    // Lire une préférence par ID
    public function read($idPref) {
        $stmt = $this->db->prepare("SELECT * FROM preferences WHERE idPref = ?");
        $stmt->bind_param("i", $idPref);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($data = $result->fetch_assoc()) {
            return new Preference($data['idPref'], $data['label']);
        }
        return null;
    }
    
    // Mettre à jour une préférence
    public function update(Preference $preference) {
        $stmt = $this->db->prepare("UPDATE preferences SET label = ? WHERE idPref = ?");
        $label = $preference->getLabel();
        $idPref = $preference->getIdPref();
        $stmt->bind_param("si", $label, $idPref);
        return $stmt->execute();
    }
    
    // Supprimer une préférence
    public function delete($idPref) {
        $stmt = $this->db->prepare("DELETE FROM preferences WHERE idPref = ?");
        $stmt->bind_param("i", $idPref);
        return $stmt->execute();
    }
    
    // Récupérer toutes les préférences
    public function findAll() {
        $result = $this->db->query("SELECT * FROM preferences ORDER BY label");
        $preferences = [];
        
        while ($data = $result->fetch_assoc()) {
            $preferences[] = new Preference($data['idPref'], $data['label']);
        }
        return $preferences;
    }
    
    // Rechercher par label
    public function findByLabel($label) {
        $stmt = $this->db->prepare("SELECT * FROM preferences WHERE label LIKE ?");
        $like = "%" . $label . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $preferences = [];
        while ($data = $result->fetch_assoc()) {
            $preferences[] = new Preference($data['idPref'], $data['label']);
        }
        return $preferences;
    }
    
    // Compter le nombre total de préférences
    public function count() {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM preferences");
        $row = $result->fetch_assoc();
        return $row['total'];
    }
}
