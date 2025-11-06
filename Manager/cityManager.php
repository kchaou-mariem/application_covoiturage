<?php

class CityManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Créer une ville
    public function create(City $city) {
        if (!$city->isValid()) {
            throw new InvalidArgumentException("Données de ville invalides. Le nom est requis et doit faire 50 caractères maximum.");
        }
        
        $query = $this->db->prepare("INSERT INTO city (name) VALUES (:name)");
        $query->bindValue(':name', $city->getName());
        
        try {
            $result = $query->execute();
            if ($result) {
                $city->setIdCity($this->db->lastInsertId());
            }
            return $result;
        } catch (PDOException $e) {
            // Gestion de l'erreur de contrainte UNIQUE
            if ($e->getCode() == '23000') {
                throw new Exception("Une ville avec le nom '{$city->getName()}' existe déjà");
            }
            throw $e;
        }
    }
    
    // Lire une ville par ID
    public function read($idCity) {
        $query = $this->db->prepare("SELECT * FROM city WHERE idCity = :idCity");
        $query->bindValue(':idCity', $idCity, PDO::PARAM_INT);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return new City($data['idCity'], $data['name']);
        }
        
        return null;
    }
    
    // Lire une ville par nom exact
    public function readByName($name) {
        $query = $this->db->prepare("SELECT * FROM city WHERE name = :name");
        $query->bindValue(':name', $name);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return new City($data['idCity'], $data['name']);
        }
        
        return null;
    }
    
    // Mettre à jour une ville
    public function update(City $city) {
        if (!$city->isValid()) {
            throw new InvalidArgumentException("Données de ville invalides. Le nom est requis et doit faire 50 caractères maximum.");
        }
        
        $query = $this->db->prepare("UPDATE city SET name = :name WHERE idCity = :idCity");
        $query->bindValue(':name', $city->getName());
        $query->bindValue(':idCity', $city->getIdCity(), PDO::PARAM_INT);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            // Gestion de l'erreur de contrainte UNIQUE
            if ($e->getCode() == '23000') {
                throw new Exception("Une ville avec le nom '{$city->getName()}' existe déjà");
            }
            throw $e;
        }
    }
    
    // Supprimer une ville
    public function delete($idCity) {
        $query = $this->db->prepare("DELETE FROM city WHERE idCity = :idCity");
        $query->bindValue(':idCity', $idCity, PDO::PARAM_INT);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            // Gestion des contraintes de clé étrangère
            if ($e->getCode() == '23000') {
                throw new Exception("Impossible de supprimer cette ville car elle est référencée par d'autres enregistrements");
            }
            throw $e;
        }
    }
    
    // Récupérer toutes les villes
    public function findAll() {
        $query = $this->db->query("SELECT * FROM city ORDER BY name");
        $cities = [];
        
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        
        return $cities;
    }
    
    // Rechercher par nom (LIKE)
    public function findByName($name) {
        $query = $this->db->prepare("SELECT * FROM city WHERE name LIKE :name ORDER BY name");
        $query->bindValue(':name', '%' . $name . '%');
        $query->execute();
        
        $cities = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        
        return $cities;
    }
    
    // Compter le nombre total de villes
    public function count() {
        $query = $this->db->query("SELECT COUNT(*) FROM city");
        return $query->fetchColumn();
    }
    
    // Vérifier si une ville existe
    public function exists($idCity) {
        $query = $this->db->prepare("SELECT COUNT(*) FROM city WHERE idCity = :idCity");
        $query->bindValue(':idCity', $idCity, PDO::PARAM_INT);
        $query->execute();
        
        return $query->fetchColumn() > 0;
    }
    
    // Vérifier si un nom de ville existe déjà (pour éviter les doublons)
    public function nameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM city WHERE name = :name";
        $params = [':name' => $name];
        
        if ($excludeId !== null) {
            $sql .= " AND idCity != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        
        $query = $this->db->prepare($sql);
        $query->execute($params);
        
        return $query->fetchColumn() > 0;
    }
    
    // Récupérer les villes avec pagination
    public function findWithPagination($limit, $offset = 0) {
        $query = $this->db->prepare("SELECT * FROM city ORDER BY name LIMIT :limit OFFSET :offset");
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
        
        $cities = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        
        return $cities;
    }
    
    // Récupérer les villes avec recherche et pagination
    public function searchWithPagination($searchTerm, $limit, $offset = 0) {
        $query = $this->db->prepare("SELECT * FROM city WHERE name LIKE :search ORDER BY name LIMIT :limit OFFSET :offset");
        $query->bindValue(':search', '%' . $searchTerm . '%');
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
        
        $cities = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $cities[] = new City($data['idCity'], $data['name']);
        }
        
        return $cities;
    }
    
    // Compter les résultats d'une recherche
    public function countSearch($searchTerm) {
        $query = $this->db->prepare("SELECT COUNT(*) FROM city WHERE name LIKE :search");
        $query->bindValue(':search', '%' . $searchTerm . '%');
        $query->execute();
        
        return $query->fetchColumn();
    }
}