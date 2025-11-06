<?php

class User {
    private $cin;
    private $email;
    private $firstName;
    private $lastName;
    private $gender;
    private $password;
    private $phone;
    
    // Constructeur
    public function __construct(
        $cin = null,
        $email = null,
        $firstName = null,
        $lastName = null,
        $gender = 'male',
        $password = null,
        $phone = null
    ) {
        $this->cin = $cin;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->gender = $gender;
        $this->password = $password;
        $this->phone = $phone;
    }
    
    // Getters
    public function getCin() {
        return $this->cin;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getFirstName() {
        return $this->firstName;
    }
    
    public function getLastName() {
        return $this->lastName;
    }
    
    public function getGender() {
        return $this->gender;
    }
    
    public function getPassword() {
        return $this->password;
    }
    
    public function getPhone() {
        return $this->phone;
    }
    
    public function getFullName() {
        return $this->firstName . ' ' . $this->lastName;
    }
    
    // Setters
    public function setCin($cin) {
        $this->cin = $cin;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }
    
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }
    
    public function setGender($gender) {
        $this->gender = $gender;
    }
    
    public function setPassword($password) {
        $this->password = $password;
    }
    
    public function setPhone($phone) {
        $this->phone = $phone;
    }
    
    // Hash le mot de passe
    public function hashPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Vérifie le mot de passe
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    // Validation des données
    public function isValid() {
        return !empty($this->cin) &&
               !empty($this->email) &&
               !empty($this->firstName) &&
               !empty($this->lastName) &&
               !empty($this->password) &&
               filter_var($this->email, FILTER_VALIDATE_EMAIL) &&
               strlen($this->cin) <= 20 &&
               strlen($this->email) <= 255 &&
               strlen($this->firstName) <= 100 &&
               strlen($this->lastName) <= 100 &&
               in_array($this->gender, ['male', 'female']);
    }
    
    // Validation pour la mise à jour (sans password requis)
    public function isValidForUpdate() {
        return !empty($this->cin) &&
               !empty($this->email) &&
               !empty($this->firstName) &&
               !empty($this->lastName) &&
               filter_var($this->email, FILTER_VALIDATE_EMAIL) &&
               strlen($this->cin) <= 20 &&
               strlen($this->email) <= 255 &&
               strlen($this->firstName) <= 100 &&
               strlen($this->lastName) <= 100 &&
               in_array($this->gender, ['male', 'female']);
    }
    
    // Méthode pour afficher l'objet
    public function __toString() {
        return "User [CIN: {$this->cin}, Name: {$this->getFullName()}, Email: {$this->email}]";
    }
    
    // Méthode pour obtenir un tableau des données (sans password)
    public function toArray() {
        return [
            'cin' => $this->cin,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'gender' => $this->gender,
            'phone' => $this->phone
        ];
    }
}