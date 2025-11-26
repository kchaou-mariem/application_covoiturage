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
$ids = implode(",", array_map('intval', $_SESSION['cart'])); // Sécuriser les IDs
$sql = "SELECT j.*, 
    c1.name AS departure_city, 
    c2.name AS destination_city,
    d1.name AS departure_delegation, 
    d2.name AS destination_delegation,
    car.model AS car_model,
    car.immat AS car_immat,
    u.firstName AS driver_firstName, u.lastName AS driver_lastName, u.phone AS driver_phone, u.email AS driver_email, u.gender AS driver_gender
    FROM journey j
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    LEFT JOIN delegation d1 ON j.departureDelegation = d1.idDelegation
    LEFT JOIN delegation d2 ON j.destinationDelegation = d2.idDelegation
    LEFT JOIN car ON j.immatCar = car.immat
    LEFT JOIN users u ON j.cinRequester = u.cin
    WHERE j.idJourney IN ($ids)";

$res = $conn->query($sql);

// Start a single checkout form that contains seat inputs for each journey
echo '<form action="checkout.php" method="POST">';
while ($row = $res->fetch_assoc()):
?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px;" data-route="<?= htmlspecialchars($row['departure_city'] . ' → ' . $row['destination_city']) ?>" data-id="<?= $row['idJourney'] ?>">
        <?php
            $depCity = htmlspecialchars($row['departure_city']);
            $depDel = !empty($row['departure_delegation']) ? ' (' . htmlspecialchars($row['departure_delegation']) . ')' : '';
            $destCity = htmlspecialchars($row['destination_city']);
            $destDel = !empty($row['destination_delegation']) ? ' (' . htmlspecialchars($row['destination_delegation']) . ')' : '';
        ?>
        <strong><?= $depCity . $depDel ?> → <?= $destCity . $destDel ?></strong><br>
        Date : <?= htmlspecialchars($row['depDate']) ?> at <?= htmlspecialchars(substr($row['depTime'],0,5)) ?><br>
        Price : <span class="journey-price" data-id="<?= $row['idJourney'] ?>"><?= htmlspecialchars($row['price']) ?></span> DT<br>
        <?php if (!empty($row['car_model']) || !empty($row['car_immat'])): ?>
            <strong>Car:</strong> <?= htmlspecialchars($row['car_model'] ?? '') ?> <?= !empty($row['car_immat']) ? '(' . htmlspecialchars($row['car_immat']) . ')' : '' ?><br>
        <?php endif; ?>
        Available Seats: <?= htmlspecialchars($row['nbSeats']) ?><br>

        <!-- Seats input placed inside the same journey block -->
        <label>Seats to reserve:</label>
        <input class="seats-input" data-id="<?= $row['idJourney'] ?>" type="number" name="seats_<?= $row['idJourney'] ?>" min="1" max="<?= htmlspecialchars($row['nbSeats']) ?>" required>
        <br><br>
        <?php if (!empty($row['driver_firstName']) || !empty($row['driver_lastName']) || !empty($row['driver_phone']) || !empty($row['driver_email'])): ?>
            <div>
                <strong>Driver:</strong> <?= htmlspecialchars(trim(($row['driver_firstName'] ?? '') . ' ' . ($row['driver_lastName'] ?? ''))) ?><br>
                <?php if (!empty($row['driver_phone'])): ?><strong>Phone:</strong> <?= htmlspecialchars($row['driver_phone']) ?><br><?php endif; ?>
                <?php if (!empty($row['driver_email'])): ?><strong>Email:</strong> <?= htmlspecialchars($row['driver_email']) ?><br><?php endif; ?>
                <?php if (!empty($row['driver_gender'])): ?><strong>Gender:</strong> <?= htmlspecialchars($row['driver_gender']) ?><br><?php endif; ?>
            </div>
        <?php endif; ?>

        <a href="cart.php?remove=<?= $row['idJourney'] ?>">
            <button type="button">Remove</button>
        </a>
    </div>

<?php endwhile; ?>

    <button type="submit" >Pay</button>
    </form>

</body>
</html>
<script>
// Confirmation before submitting payment
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="checkout.php"]');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const items = [];
        let total = 0;

        document.querySelectorAll('.seats-input').forEach(function (input) {
            const id = input.dataset.id;
            const seats = parseInt(input.value) || 0;
            if (seats <= 0) return;
            const priceEl = document.querySelector('.journey-price[data-id="' + id + '"]');
            const routeEl = document.querySelector('div[data-id="' + id + '"]');
            const price = priceEl ? parseFloat(priceEl.textContent) : 0;
            const route = routeEl ? routeEl.dataset.route : ('Journey ' + id);
            const subtotal = seats * price;
            items.push({ id, route, seats, price, subtotal });
            total += subtotal;
        });

        if (items.length === 0) {
            alert('Please enter number of seats for at least one journey.');
            return;
        }

        let msg = 'Please confirm your booking:\n\n';
        items.forEach(function (it) {
            msg += it.route + ': ' + it.seats + ' × ' + it.price + ' = ' + it.subtotal + '\n';
        });
        msg += '\nTotal: ' + total + ' DT\n\nProceed to payment?';

        if (confirm(msg)) {
            form.submit();
        }
    });
});
</script>
