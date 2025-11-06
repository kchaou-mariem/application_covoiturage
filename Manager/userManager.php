<?php

class UserManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // CREATE - Créer un utilisateur
    public function insert(User $user) {
        if (!$user->isValid()) {
            throw new InvalidArgumentException("Données utilisateur invalides");
        }
        
        $query = $this->db->prepare("
            INSERT INTO users (cin, email, firstName, lastName, gender, password, phone) 
            VALUES (:cin, :email, :firstName, :lastName, :gender, :password, :phone)
        ");
        
        $this->bindUserParams($query, $user);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            // Gestion des erreurs de contrainte UNIQUE
            if ($e->getCode() == '23000') {
                if (strpos($e->getMessage(), 'email') !== false) {
                    throw new Exception("Un utilisateur avec cet email existe déjà");
                } elseif (strpos($e->getMessage(), 'cin') !== false) {
                    throw new Exception("Un utilisateur avec ce CIN existe déjà");
                }
            }
            throw new Exception("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // READ - Trouver par CIN
    public function findByCin($cin) {
        $query = $this->db->prepare("SELECT * FROM users WHERE cin = :cin");
        $query->bindValue(':cin', $cin);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrateUser($data) : null;
    }
    
    // READ - Trouver par email
    public function findByEmail($email) {
        $query = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $query->bindValue(':email', $email);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrateUser($data) : null;
    }
    
    // READ - Trouver tous les utilisateurs
    public function findAll() {
        $query = $this->db->query("SELECT * FROM users ORDER BY lastName, firstName");
        $users = [];
        
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $data) {
            $users[] = $this->hydrateUser($data);
        }
        
        return $users;
    }
    
    // UPDATE - Mettre à jour un utilisateur
    public function update(User $user) {
        if (!$user->isValidForUpdate()) {
            throw new InvalidArgumentException("Données utilisateur invalides pour la mise à jour");
        }
        
        // Construction dynamique de la requête UPDATE
        $updates = [];
        $params = [':cin' => $user->getCin()];
        
        $updates[] = "email = :email";
        $params[':email'] = $user->getEmail();
        
        $updates[] = "firstName = :firstName";
        $params[':firstName'] = $user->getFirstName();
        
        $updates[] = "lastName = :lastName";
        $params[':lastName'] = $user->getLastName();
        
        $updates[] = "gender = :gender";
        $params[':gender'] = $user->getGender();
        
        // Mise à jour du mot de passe seulement si fourni
        if (!empty($user->getPassword())) {
            $updates[] = "password = :password";
            $params[':password'] = $user->getPassword();
        }
        
        $updates[] = "phone = :phone";
        $params[':phone'] = $user->getPhone();
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE cin = :cin";
        $query = $this->db->prepare($sql);
        
        try {
            return $query->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                throw new Exception("Un utilisateur avec cet email existe déjà");
            }
            throw new Exception("Erreur lors de la mise à jour: " . $e->getMessage());
        }
    }
    
    // DELETE - Supprimer un utilisateur
    public function delete($cin) {
        $query = $this->db->prepare("DELETE FROM users WHERE cin = :cin");
        $query->bindValue(':cin', $cin);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                throw new Exception("Impossible de supprimer cet utilisateur car il est référencé par d'autres enregistrements");
            }
            throw $e;
        }
    }
    
    // Vérifier si un CIN existe
    public function cinExists($cin) {
        $query = $this->db->prepare("SELECT COUNT(*) FROM users WHERE cin = :cin");
        $query->bindValue(':cin', $cin);
        $query->execute();
        
        return $query->fetchColumn() > 0;
    }
    
    // Vérifier si un email existe
    public function emailExists($email, $excludeCin = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeCin) {
            $sql .= " AND cin != :excludeCin";
            $params[':excludeCin'] = $excludeCin;
        }
        
        $query = $this->db->prepare($sql);
        $query->execute($params);
        
        return $query->fetchColumn() > 0;
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
        return $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
    
    // Rechercher des utilisateurs par nom
    public function searchByName($name) {
        $query = $this->db->prepare("
            SELECT * FROM users 
            WHERE firstName LIKE :name OR lastName LIKE :name 
            ORDER BY lastName, firstName
        ");
        $query->bindValue(':name', '%' . $name . '%');
        $query->execute();
        
        $users = [];
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $data) {
            $users[] = $this->hydrateUser($data);
        }
        
        return $users;
    }
    
    // Méthodes privées utilitaires
    private function bindUserParams($query, User $user) {
        $query->bindValue(':cin', $user->getCin());
        $query->bindValue(':email', $user->getEmail());
        $query->bindValue(':firstName', $user->getFirstName());
        $query->bindValue(':lastName', $user->getLastName());
        $query->bindValue(':gender', $user->getGender());
        $query->bindValue(':password', $user->getPassword());
        $query->bindValue(':phone', $user->getPhone());
    }
    
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