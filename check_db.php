<?php
// Test de connexion et vérification de la structure de la table users
require_once 'config/connexion.php';

echo "<h2>Structure de la table users:</h2>";
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

echo "<h2>Nombre total d'utilisateurs: " . $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] . "</h2>";

$conn->close();
?>
