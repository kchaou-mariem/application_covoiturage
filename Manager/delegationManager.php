<?php

class DelegationManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Créer une délégation
    public function create(Delegation $delegation) {
        if (!$delegation->isValid()) {
            throw new InvalidArgumentException("Données de délégation invalides");
        }
        
        $query = $this->db->prepare("INSERT INTO delegation (name) VALUES (:name)");
        $query->bindValue(':name', $delegation->getName());
        
        try {
            $result = $query->execute();
            if ($result) {
                $delegation->setIdDelegation($this->db->lastInsertId());
            }
            return $result;
        } catch (PDOException $e) {
            // Gestion de l'erreur de contrainte UNIQUE
            if ($e->getCode() == '23000') {
                throw new Exception("Une délégation avec ce nom existe déjà");
            }
            throw $e;
        }
    }
    
    // Lire une délégation par ID
    public function read($idDelegation) {
        $query = $this->db->prepare("SELECT * FROM delegation WHERE idDelegation = :idDelegation");
        $query->bindValue(':idDelegation', $idDelegation, PDO::PARAM_INT);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return new Delegation($data['idDelegation'], $data['name']);
        }
        
        return null;
    }
    
    // Lire une délégation par nom
    public function readByName($name) {
        $query = $this->db->prepare("SELECT * FROM delegation WHERE name = :name");
        $query->bindValue(':name', $name);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return new Delegation($data['idDelegation'], $data['name']);
        }
        
        return null;
    }
    
    // Mettre à jour une délégation
    public function update(Delegation $delegation) {
        if (!$delegation->isValid()) {
            throw new InvalidArgumentException("Données de délégation invalides");
        }
        
        $query = $this->db->prepare("UPDATE delegation SET name = :name WHERE idDelegation = :idDelegation");
        $query->bindValue(':name', $delegation->getName());
        $query->bindValue(':idDelegation', $delegation->getIdDelegation(), PDO::PARAM_INT);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            // Gestion de l'erreur de contrainte UNIQUE
            if ($e->getCode() == '23000') {
                throw new Exception("Une délégation avec ce nom existe déjà");
            }
            throw $e;
        }
    }
    
    // Supprimer une délégation
    public function delete($idDelegation) {
        $query = $this->db->prepare("DELETE FROM delegation WHERE idDelegation = :idDelegation");
        $query->bindValue(':idDelegation', $idDelegation, PDO::PARAM_INT);
        
        return $query->execute();
    }
    
    // Récupérer toutes les délégations
    public function findAll() {
        $query = $this->db->query("SELECT * FROM delegation ORDER BY name");
        $delegations = [];
        
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        
        return $delegations;
    }
    
    // Rechercher par nom (LIKE)
    public function findByName($name) {
        $query = $this->db->prepare("SELECT * FROM delegation WHERE name LIKE :name ORDER BY name");
        $query->bindValue(':name', '%' . $name . '%');
        $query->execute();
        
        $delegations = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        
        return $delegations;
    }
    
    // Compter le nombre total de délégations
    public function count() {
        $query = $this->db->query("SELECT COUNT(*) FROM delegation");
        return $query->fetchColumn();
    }
    
    // Vérifier si une délégation existe
    public function exists($idDelegation) {
        $query = $this->db->prepare("SELECT COUNT(*) FROM delegation WHERE idDelegation = :idDelegation");
        $query->bindValue(':idDelegation', $idDelegation, PDO::PARAM_INT);
        $query->execute();
        
        return $query->fetchColumn() > 0;
    }
    
    // Récupérer les délégations avec pagination
    public function findWithPagination($limit, $offset = 0) {
        $query = $this->db->prepare("SELECT * FROM delegation ORDER BY name LIMIT :limit OFFSET :offset");
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();
        
        $delegations = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        
        return $delegations;
    }
}