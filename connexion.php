 <?php
 $serveur = "localhost"; // Serveur MySQL local (via XAMPP)
 $utilisateur = "root"; // Nom d’utilisateur par défaut de MySQL
 $motdepasse= "";   // Mot de passe vide par défaut dans XAMPP
 // Createconnection: mysqli_connect() : fonction pour se connecter à MySQL
 $connexion= mysqli_connect($serveur, $utilisateur, $motdepasse);
 // Check connection
 if (!$connexion) {
 // mysqli_connect_error() : Affiche le message d’erreur si la connexion échoue
 die("Échec de la connexion : " . mysqli_connect_error()); }
 echo"Connected Successfully";
 // sélection de la base de données
 $db= mysqli_select_db($connexion, "covoiturages");
 // DB Check connection
 if (!$db) {
 die("Base de données introuvable : " . mysqli_error($connexion));
 }
 echo"Connexion à la BD réussie";

$sql = "CREATE TABLE IF NOT EXISTS users (
    cin VARCHAR(20) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL, 
    gender ENUM('male', 'female') DEFAULT 'male',
    password VARCHAR(255) NOT NULL,
    phone INT
    )";

if (mysqli_query($connexion, $sql)) {
    echo "Table users créée avec succès !";
} else {
    echo "Erreur: " . mysqli_error($connexion);
}

?>