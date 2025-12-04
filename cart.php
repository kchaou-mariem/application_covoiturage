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
<?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="container">
        <div class="card cart-card my-4 p-3">
            <h2 class="mb-3">Your Cart</h2>

            <?php
            if (!empty($_SESSION['error'])) {
                    echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                    unset($_SESSION['error']);
            }

            if (!empty($_SESSION['success'])) {
                    echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success']) . "</div>";
                    unset($_SESSION['success']);
            }

            // --- 4) AFFICHAGE DU PANIER ---
              if (empty($_SESSION['cart'])) {
              echo "<p>Your cart is empty.</p>";
            } else {
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

                    echo '<form action="checkout.php" method="POST">';
                    while ($row = $res->fetch_assoc()):
                        $depCity = htmlspecialchars($row['departure_city']);
                        $depDel = !empty($row['departure_delegation']) ? ' (' . htmlspecialchars($row['departure_delegation']) . ')' : '';
                        $destCity = htmlspecialchars($row['destination_city']);
                        $destDel = !empty($row['destination_delegation']) ? ' (' . htmlspecialchars($row['destination_delegation']) . ')' : '';
            ?>
                        <div class="cart-item d-flex align-items-start gap-3 mb-3" data-route="<?= htmlspecialchars($row['departure_city'] . ' → ' . $row['destination_city']) ?>" data-id="<?= $row['idJourney'] ?>">
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= $depCity . $depDel ?> → <?= $destCity . $destDel ?></div>
                                <div class="text-muted small">Date: <?= htmlspecialchars($row['depDate']) ?> at <?= htmlspecialchars(substr($row['depTime'],0,5)) ?></div>
                                <div class="mt-2">Price: <span class="journey-price" data-id="<?= $row['idJourney'] ?>"><?= htmlspecialchars($row['price']) ?></span> DT</div>
                                <?php if (!empty($row['car_model']) || !empty($row['car_immat'])): ?>
                                    <div class="small">Voiture: <?= htmlspecialchars($row['car_model'] ?? '') ?> <?= !empty($row['car_immat']) ? '(' . htmlspecialchars($row['car_immat']) . ')' : '' ?></div>
                                <?php endif; ?>
                                <div class="small">Available seats: <?= htmlspecialchars($row['nbSeats']) ?></div>

                                <?php if (!empty($row['driver_firstName']) || !empty($row['driver_lastName']) || !empty($row['driver_phone']) || !empty($row['driver_email'])): ?>
                                    <div class="mt-2 small">
                                        <strong>Conducteur:</strong> <?= htmlspecialchars(trim(($row['driver_firstName'] ?? '') . ' ' . ($row['driver_lastName'] ?? ''))) ?><br>
                                        <?php if (!empty($row['driver_phone'])): ?><span>Tél: <?= htmlspecialchars($row['driver_phone']) ?></span><br><?php endif; ?>
                                        <?php if (!empty($row['driver_email'])): ?><span>Email: <?= htmlspecialchars($row['driver_email']) ?></span><br><?php endif; ?>
                                        <?php if (!empty($row['driver_gender'])): ?><span>Genre: <?= htmlspecialchars($row['driver_gender']) ?></span><br><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="cart-actions text-end">
                                <label class="form-label small">Places à réserver</label>
                                <input class="form-control seats-input mb-2" data-id="<?= $row['idJourney'] ?>" type="number" name="seats_<?= $row['idJourney'] ?>" min="1" max="<?= htmlspecialchars($row['nbSeats']) ?>" required>
                                <div>
                                      <a href="cart.php?remove=<?= $row['idJourney'] ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                                </div>
                            </div>
                        </div>
            <?php endwhile;

                      echo '<div class="d-flex justify-content-end mt-3"><button class="btn btn-success" type="submit">Pay</button></div>';
                    echo '</form>';
            }
            ?>

        </div>
    </div>

    <!-- Modal de confirmation de paiement -->
    <div class="modal fade" id="confirmPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-credit-card"></i> Confirm Your Booking</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fw-bold mb-3">Please confirm your booking:</p>
                    <div id="bookingDetails" class="mb-3"></div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5">Total:</span>
                        <span class="fw-bold fs-4 text-success" id="totalAmount">0 DT</span>
                    </div>
                    <p class="text-muted small mt-2">Proceed to payment?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPaymentBtn">
                        <i class="fas fa-check"></i> Confirm & Pay
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
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
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = '<strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> Please enter number of seats for at least one journey. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.querySelector('.cart-card').insertBefore(alertDiv, document.querySelector('.cart-card').firstChild);
            return;
        }

        // Construire le contenu du modal
        let detailsHTML = '<div class="list-group">';
        items.forEach(function (it) {
            detailsHTML += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold text-primary">${it.route}</div>
                            <small class="text-muted">${it.seats} × ${it.price} DT</small>
                        </div>
                        <span class="badge bg-success rounded-pill fs-6">${it.subtotal} DT</span>
                    </div>
                </div>
            `;
        });
        detailsHTML += '</div>';

        document.getElementById('bookingDetails').innerHTML = detailsHTML;
        document.getElementById('totalAmount').textContent = total + ' DT';

        // Afficher le modal
        const modal = new bootstrap.Modal(document.getElementById('confirmPaymentModal'));
        modal.show();
    });

    // Bouton de confirmation dans le modal
    document.getElementById('confirmPaymentBtn').addEventListener('click', function() {
        form.submit();
    });
});
</script>
