<?php
require_once 'config/connexion.php';

echo "<h2>Migration: Ajout des colonnes role et status</h2>";

// Vérifier si la colonne 'role' existe
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($result->num_rows == 0) {
    echo "<p>Ajout de la colonne 'role'...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
    echo "<p style='color: green;'>✓ Colonne 'role' ajoutée avec succès</p>";
} else {
    echo "<p style='color: blue;'>ℹ Colonne 'role' existe déjà</p>";
}

// Vérifier si la colonne 'status' existe
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($result->num_rows == 0) {
    echo "<p>Ajout de la colonne 'status'...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
    echo "<p style='color: green;'>✓ Colonne 'status' ajoutée avec succès</p>";
} else {
    echo "<p style='color: blue;'>ℹ Colonne 'status' existe déjà</p>";
}

echo "<hr>";
echo "<h2>Structure mise à jour de la table users:</h2>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td style='padding: 5px;'>" . htmlspecialchars($value ?? '') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>Créer un compte admin</h2>";
echo "<p>Pour créer un compte admin, vous devez:</p>";
echo "<ol>";
echo "<li>Créer un compte utilisateur normal via le formulaire d'inscription</li>";
echo "<li>Ensuite, exécutez cette requête SQL (remplacez 'email@example.com' par votre email):</li>";
echo "</ol>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo "UPDATE users SET role = 'admin' WHERE email = 'votre-email@example.com';\n";
echo "</pre>";

$conn->close();
?>
