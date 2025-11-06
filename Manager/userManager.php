<?php

class UserManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn; // $conn est une instance mysqli
    }

    // CREATE - Créer un utilisateur
    public function insert(User $user) {
        if (!$user->isValid()) {
            throw new InvalidArgumentException("Données utilisateur invalides");
        }

        $stmt = $this->conn->prepare("
            INSERT INTO users (cin, email, firstName, lastName, gender, password, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $cin = $user->getCin();
        $email = $user->getEmail();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $gender = $user->getGender();
        $password = $user->getPassword();
        $phone = $user->getPhone();

        $stmt->bind_param("sssssss", $cin, $email, $firstName, $lastName, $gender, $password, $phone);

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Doublon clé unique
                if (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception("Un utilisateur avec cet email existe déjà");
                } elseif (strpos($e->getMessage(), 'cin') !== false) {
                    throw new Exception("Un utilisateur avec ce CIN existe déjà");
                }
            }
            throw new Exception("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
        }
    }

    // READ - Trouver par CIN
    public function findByCin($cin) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE cin = ?");
        $stmt->bind_param("s", $cin);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return $data ? $this->hydrateUser($data) : null;
    }

    // READ - Trouver par email
    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return $data ? $this->hydrateUser($data) : null;
    }

    // READ - Trouver tous les utilisateurs
    public function findAll() {
        $result = $this->conn->query("SELECT * FROM users ORDER BY lastName, firstName");
        $users = [];

        while ($data = $result->fetch_assoc()) {
            $users[] = $this->hydrateUser($data);
        }
        return $users;
    }

    // UPDATE - Mettre à jour un utilisateur
    public function update(User $user) {
        if (!$user->isValidForUpdate()) {
            throw new InvalidArgumentException("Données utilisateur invalides pour la mise à jour");
        }

        $sql = "UPDATE users SET email=?, firstName=?, lastName=?, gender=?, phone=?";
        $params = [$user->getEmail(), $user->getFirstName(), $user->getLastName(), $user->getGender(), $user->getPhone()];
        $types = "sssss";

        // Mot de passe uniquement si fourni
        if (!empty($user->getPassword())) {
            $sql .= ", password=?";
            $params[] = $user->getPassword();
            $types .= "s";
        }

        $sql .= " WHERE cin=?";
        $params[] = $user->getCin();
        $types .= "s";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                throw new Exception("Un utilisateur avec cet email existe déjà");
            }
            throw new Exception("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }

    // DELETE - Supprimer un utilisateur
    public function delete($cin) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE cin = ?");
        $stmt->bind_param("s", $cin);

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) { // Contrainte d'intégrité
                throw new Exception("Impossible de supprimer cet utilisateur car il est référencé par d'autres enregistrements");
            }
            throw $e;
        }
    }

    // Vérifier si un CIN existe
    public function cinExists($cin) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM users WHERE cin = ?");
        $stmt->bind_param("s", $cin);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }

    // Vérifier si un email existe
    public function emailExists($email, $excludeCin = null) {
        if ($excludeCin) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM users WHERE email = ? AND cin != ?");
            $stmt->bind_param("ss", $email, $excludeCin);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }

    // Authentifier un utilisateur
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && $user->verifyPassword($password)) {
            return $user;
        }
        return null;
    }

    // Compter le nombre total d'utilisateurs
    public function count() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // Rechercher des utilisateurs par nom
    public function searchByName($name) {
        $stmt = $this->conn->prepare("
            SELECT * FROM users 
            WHERE firstName LIKE ? OR lastName LIKE ?
            ORDER BY lastName, firstName
        ");
        $like = "%" . $name . "%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($data = $result->fetch_assoc()) {
            $users[] = $this->hydrateUser($data);
        }
        return $users;
    }

    // Hydrater un utilisateur depuis un tableau
    private function hydrateUser($data) {
        return new User(
            $data['cin'],
            $data['email'],
            $data['firstName'],
            $data['lastName'],
            $data['gender'],
            $data['password'],
            $data['phone']
        );
    }
}