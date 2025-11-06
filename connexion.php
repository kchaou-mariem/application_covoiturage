<?php
$servername = "localhost";   
$username = "root";          
$password = "";              
$dbname = "covoiturages"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>
