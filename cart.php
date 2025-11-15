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

    // Vérifier le nombre de places restantes
    $stmt = $conn->prepare("SELECT nbSeats FROM journey WHERE idJourney = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($seatsRemaining);
    $stmt->fetch();
    $stmt->close();

    if ($seatsRemaining <= 0) {
        $_SESSION['error'] = "Sorry, this journey is fully booked.";
    } elseif (!in_array($id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $id; // Ajout du trajet au panier
        $_SESSION['success'] = "Journey added to cart successfully.";
    }

    header("Location: cart.php");
    exit();
}

// --- 3) SUPPRIMER UN TRAJET ---
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($x) => $x != $id);
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
</head>
<body>

<h1>Your Cart</h1>

<?php
if (!empty($_SESSION['error'])) {
    echo "<p style='color:red;'>".$_SESSION['error']."</p>";
    unset($_SESSION['error']);
}

if (!empty($_SESSION['success'])) {
    echo "<p style='color:green;'>".$_SESSION['success']."</p>";
    unset($_SESSION['success']);
}

// --- 4) AFFICHAGE DU PANIER ---
if (empty($_SESSION['cart'])) {
    echo "<p>Your cart is empty.</p>";
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
        <strong><?= htmlspecialchars($row['departure_city']) ?> → <?= htmlspecialchars($row['destination_city']) ?></strong><br>
        Date : <?= $row['depDate'] ?> at <?= substr($row['depTime'],0,5) ?><br>
        Price : <?= $row['price'] ?> DT<br>
        Available Seats: <?= $row['nbSeats'] ?><br>

        <a href="cart.php?remove=<?= $row['idJourney'] ?>">
            <button>Remove</button>
        </a>
    </div>
<?php endwhile; ?>

<form action="checkout.php" method="POST">
<?php foreach ($_SESSION['cart'] as $jid): ?>
    <label>Seats for journey <?= $jid ?>:</label>
    <input type="number" name="seats_<?= $jid ?>" min="1" max="<?= $row['nbSeats'] ?>" required>
    <br><br>
<?php endforeach; ?>

<button type="submit">Pay</button>
</form>

</body>
</html>
