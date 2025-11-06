<?php

class DelegationManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn; // $conn est un objet mysqli
    }

    // Créer une délégation
    public function create(Delegation $delegation) {
        if (!$delegation->isValid()) {
            throw new InvalidArgumentException("Données de délégation invalides");
        }

        if ($this->nameExists($delegation->getName())) {
            throw new Exception("Une délégation avec le nom '{$delegation->getName()}' existe déjà");
        }

        $stmt = $this->conn->prepare("INSERT INTO delegation (name) VALUES (?)");
        $name = $delegation->getName();
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            $delegation->setIdDelegation($this->conn->insert_id);
            return true;
        } else {
            throw new Exception("Erreur lors de l'insertion : " . $stmt->error);
        }
    }

// Récupère l'ID d'une délégation par son nom
public function getIdByName($name) {
    $stmt = $this->conn->prepare("SELECT idDelegation FROM delegation WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['idDelegation'];
    }
    return null;
}


    // Lire une délégation par ID
    public function read($idDelegation) {
        $stmt = $this->conn->prepare("SELECT * FROM delegation WHERE idDelegation = ?");
        $stmt->bind_param("i", $idDelegation);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return new Delegation($data['idDelegation'], $data['name']);
        }
        return null;
    }

    // Lire une délégation par nom
    public function readByName($name) {
        $stmt = $this->conn->prepare("SELECT * FROM delegation WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return new Delegation($data['idDelegation'], $data['name']);
        }
        return null;
    }

    // Mettre à jour une délégation
    public function update(Delegation $delegation) {
        if (!$delegation->isValid()) {
            throw new InvalidArgumentException("Données de délégation invalides");
        }

        if ($this->nameExists($delegation->getName(), $delegation->getIdDelegation())) {
            throw new Exception("Une délégation avec le nom '{$delegation->getName()}' existe déjà");
        }

        $stmt = $this->conn->prepare("UPDATE delegation SET name = ? WHERE idDelegation = ?");
        $stmt->bind_param("si", $delegation->getName(), $delegation->getIdDelegation());

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour : " . $stmt->error);
        }
        return true;
    }

    // Supprimer une délégation
    public function delete($idDelegation) {
        $stmt = $this->conn->prepare("DELETE FROM delegation WHERE idDelegation = ?");
        $stmt->bind_param("i", $idDelegation);

        if (!$stmt->execute()) {
            if ($stmt->errno == 1451) {
                throw new Exception("Impossible de supprimer cette délégation car elle est référencée ailleurs");
            }
            throw new Exception("Erreur lors de la suppression : " . $stmt->error);
        }
        return true;
    }

    // Récupérer toutes les délégations
    public function findAll() {
        $result = $this->conn->query("SELECT * FROM delegation ORDER BY name");
        $delegations = [];

        while ($data = $result->fetch_assoc()) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        return $delegations;
    }

    // Rechercher par nom (LIKE)
    public function findByName($name) {
        $stmt = $this->conn->prepare("SELECT * FROM delegation WHERE name LIKE ? ORDER BY name");
        $like = "%" . $name . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();

        $delegations = [];
        while ($data = $result->fetch_assoc()) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        return $delegations;
    }

    // Compter le nombre total de délégations
    public function count() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM delegation");
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // Vérifier si une délégation existe
    public function exists($idDelegation) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM delegation WHERE idDelegation = ?");
        $stmt->bind_param("i", $idDelegation);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    // Vérifier si un nom de délégation existe déjà
    public function nameExists($name, $excludeId = null) {
        if ($excludeId === null) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM delegation WHERE name = ?");
            $stmt->bind_param("s", $name);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM delegation WHERE name = ? AND idDelegation != ?");
            $stmt->bind_param("si", $name, $excludeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
    }

    // Récupérer les délégations avec pagination
    public function findWithPagination($limit, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM delegation ORDER BY name LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $delegations = [];
        while ($data = $result->fetch_assoc()) {
            $delegations[] = new Delegation($data['idDelegation'], $data['name']);
        }
        return $delegations;
    }
}
