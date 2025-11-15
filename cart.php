<?php
session_start();
require_once 'config/connexion.php';

// --- 1) INITIALISER LE PANIER SI VIDE ---
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- 2) AJOUTER UN TRAJET ---
if (isset($_GET['add'])) {
    $id = intval($_GET['add']);
    if (!in_array($id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $id; // Ajout du trajet au panier
    }
    header("Location: cart.php");
    exit();
}

// --- 3) SUPPRIMER UN TRAJET ---
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($x) => $x != $id);//Garde uniquement les éléments différents de l'ID à supprimer // fn est une fonction fléchée PHP 7.4+ // $x représente chaque élément du tableau
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Panier</title>
</head>
<body>

<h1>Votre panier</h1>

<?php
// --- 4) AFFICHAGE DU PANIER ---
if (empty($_SESSION['cart'])) {
    echo "<p>Votre panier est vide.</p>";
    exit();
}

$ids = implode(",", array_map('intval', $_SESSION['cart']));
$sql = "SELECT j.*, 
        c1.name AS departure_city, 
        c2.name AS destination_city
        FROM journey j
        JOIN city c1 ON j.departure = c1.idCity
        JOIN city c2 ON j.destination = c2.idCity
        WHERE j.idJourney IN ($ids)";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()):
?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px;">
        <strong><?= $row['departure_city'] ?> → <?= $row['destination_city'] ?></strong><br>
        Date : <?= $row['depDate'] ?> à <?= $row['depTime'] ?><br>
        Prix : <?= $row['price'] ?> DT<br>

        <a href="cart.php?remove=<?= $row['idJourney'] ?>">
            <button>Retirer</button>
        </a>
    </div>
<?php endwhile; ?>

<form action="checkout.php" method="POST">
<?php foreach ($_SESSION['cart'] as $jid): ?>
    <label>Seats for journey <?= $jid ?>:</label>
    <input type="number" name="seats_<?= $jid ?>" min="1" required>
    <br><br>
<?php endforeach; ?>

<button type="submit">Payer</button>
</form>


<!-- <?php $cartIds = implode(",", $_SESSION['cart']); ?>

<a href="checkout.php?cart=<?= $cartIds ?>">
    <button>Payer</button>
</a> -->


</body>
</html>
