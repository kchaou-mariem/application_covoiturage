<?php
require_once __DIR__ . '/../Entity/preference.php';

class PreferenceManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Créer une préférence
    public function create(Preference $preference) {
        $stmt = $this->conn->prepare("INSERT INTO preferences (label) VALUES (?)");
        $label = $preference->getLabel();
        $stmt->bind_param("s", $label);
        return $stmt->execute();
    }
    
    // Lire une préférence par ID
    public function read($idPref) {
        $stmt = $this->conn->prepare("SELECT * FROM preferences WHERE idPref = ?");
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
        $stmt = $this->conn->prepare("UPDATE preferences SET label = ? WHERE idPref = ?");
        $label = $preference->getLabel();
        $idPref = $preference->getIdPref();
        $stmt->bind_param("si", $label, $idPref);
        return $stmt->execute();
    }
    
    // Supprimer une préférence
    public function delete($idPref) {
        $stmt = $this->conn->prepare("DELETE FROM preferences WHERE idPref = ?");
        $stmt->bind_param("i", $idPref);
        return $stmt->execute();
    }
    
    // Récupérer toutes les préférences
    public function findAll() {
        $result = $this->conn->query("SELECT * FROM preferences ORDER BY label");
        $preferences = [];
        
        while ($data = $result->fetch_assoc()) {
            $preferences[] = new Preference($data['idPref'], $data['label']);
        }
        return $preferences;
    }
    
    // Rechercher par label
    public function findByLabel($label) {
        $stmt = $this->conn->prepare("SELECT * FROM preferences WHERE label LIKE ?");
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
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM preferences");
        $row = $result->fetch_assoc();
        return $row['total'];
    }
}
