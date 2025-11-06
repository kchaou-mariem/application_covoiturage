<?php
// Use a reliable path based on this file's directory to include the City entity
require_once __DIR__ . '/../Entity/city.php';
class CityManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn; // $conn est un objet mysqli
    }

    // Créer une ville
public function create(City $city) {
    if (!$city->isValid()) {
        throw new InvalidArgumentException("Données de ville invalides. Le nom est requis et doit faire 50 caractères maximum.");
    }

    // Vérifier si le nom existe déjà
    if ($this->nameExists($city->getName())) {
        throw new Exception("Une ville avec le nom '{$city->getName()}' existe déjà");
    }

    // Préparer la requête d'insertion
    $stmt = $this->conn->prepare("INSERT INTO city (name) VALUES (?)");
    if (!$stmt) {
        throw new Exception("Erreur lors de la préparation : " . $this->conn->error);
    }

    $name = $city->getName();
    $stmt->bind_param("s", $name);

    // Exécuter et récupérer l'ID inséré
    if ($stmt->execute()) {
        $insertId = $this->conn->insert_id;
        $city->setIdCity($insertId); // ✅ on appelle directement le setter défini dans l'entité
        $stmt->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Erreur lors de l'insertion de la ville : " . $error);
    }
}


    // Lire une ville par ID
    public function read($idCity) {
        $stmt = $this->conn->prepare("SELECT * FROM city WHERE idCity = ?");
        $stmt->bind_param("i", $idCity);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return new City($data['idCity'], $data['name']);
        }
        return null;
    }

    // Vérifie si une ville existe par son nom
public function existsByName($name) {
    $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM city WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0;
}

// Récupère l'ID d'une ville par son nom
public function getIdCityByName($name) {
    $stmt = $this->conn->prepare("SELECT idCity FROM city WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['idCity'];
    }
    return null;
}


    // Lire une ville par nom
    public function readByName($name) {
        $stmt = $this->conn->prepare("SELECT * FROM city WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return new City($data['idCity'], $data['name']);
        }
        return null;
    }

    // Mettre à jour une ville
    public function update(City $city) {
        if (!$city->isValid()) {
            throw new InvalidArgumentException("Données de ville invalides. Le nom est requis et doit faire 50 caractères maximum.");
        }

        if ($this->nameExists($city->getName(), $city->getIdCity())) {
            throw new Exception("Une ville avec le nom '{$city->getName()}' existe déjà");
        }

        $stmt = $this->conn->prepare("UPDATE city SET name = ? WHERE idCity = ?");
        $stmt->bind_param("si", $city->getName(), $city->getIdCity());

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour : " . $stmt->error);
        }
        return true;
    }

    // Supprimer une ville
    public function delete($idCity) {
        $stmt = $this->conn->prepare("DELETE FROM city WHERE idCity = ?");
        $stmt->bind_param("i", $idCity);

        if (!$stmt->execute()) {
            if ($stmt->errno == 1451) { // Violation clé étrangère
                throw new Exception("Impossible de supprimer cette ville car elle est référencée par d'autres enregistrements");
            }
            throw new Exception("Erreur lors de la suppression : " . $stmt->error);
        }
        return true;
    }

    // Récupérer toutes les villes
    public function findAll() {
        $result = $this->conn->query("SELECT * FROM city ORDER BY name");
        $cities = [];

        while ($data = $result->fetch_assoc()) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        return $cities;
    }

    // Rechercher par nom (LIKE)
    public function findByName($name) {
        $stmt = $this->conn->prepare("SELECT * FROM city WHERE name LIKE ? ORDER BY name");
        $like = "%" . $name . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();

        $cities = [];
        while ($data = $result->fetch_assoc()) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        return $cities;
    }

    // Compter le nombre total de villes
    public function count() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM city");
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // Vérifier si une ville existe
    public function exists($idCity) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM city WHERE idCity = ?");
        $stmt->bind_param("i", $idCity);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    // Vérifier si un nom de ville existe déjà
    public function nameExists($name, $excludeId = null) {
        if ($excludeId === null) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM city WHERE name = ?");
            $stmt->bind_param("s", $name);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM city WHERE name = ? AND idCity != ?");
            $stmt->bind_param("si", $name, $excludeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    // Récupérer les villes avec pagination
    public function findWithPagination($limit, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM city ORDER BY name LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $cities = [];
        while ($data = $result->fetch_assoc()) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        return $cities;
    }

    // Récupérer les villes avec recherche et pagination
    public function searchWithPagination($searchTerm, $limit, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM city WHERE name LIKE ? ORDER BY name LIMIT ? OFFSET ?");
        $like = "%" . $searchTerm . "%";
        $stmt->bind_param("sii", $like, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $cities = [];
        while ($data = $result->fetch_assoc()) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        return $cities;
    }

    // Compter les résultats d'une recherche
    public function countSearch($searchTerm) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM city WHERE name LIKE ?");
        $like = "%" . $searchTerm . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }
}
