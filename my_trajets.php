<?php
session_start();

require_once 'config/connexion.php';
require_once 'Manager/JourneyManager.php';
require_once 'Manager/booking_manager.php';

if (!isset($_SESSION['user_cin'])) {
    header('Location: authentification.html');
    exit;
}

$journeyManager = new JourneyManager($conn);
$bookingManager = new BookingManager($conn);

$cin = $_SESSION['user_cin'];
$journeys = $journeyManager->findByRequester($cin);

?>

<?php include __DIR__ . '/includes/header.php'; ?>
  <h1 class="mb-3">My Journeys</h1>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if (empty($journeys)): ?>
      <div class="alert alert-secondary">You haven't posted any journeys yet.</div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($journeys as $journey): ?>
          <div class="col-md-6 mb-3">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Trajet — <?= htmlspecialchars($journey->departure_city_name ?? '') ?> → <?= htmlspecialchars($journey->destination_city_name ?? '') ?></h5>
                <p class="mb-1"><strong>Date:</strong> <?= date('d/m/Y', strtotime($journey->getDepDate())) ?> <?= substr($journey->getDepTime(), 0, 5) ?></p>
                <p class="mb-1"><strong>Available seats:</strong> <?= $journey->getNbSeats() ?></p>
                <p class="mb-1"><strong>Price:</strong> <?= number_format($journey->getPrice(), 2) ?> DT</p>

                <?php $bookings = $bookingManager->countBookingsForJourney($journey->getIdJourney()); ?>

                <?php if ($bookings > 0): ?>
                  <div class="alert alert-warning small">This journey has <?= $bookings ?> booking(s) — deletion is not possible while bookings exist.</div>
                  <?php $bookingRows = $bookingManager->getBookingsForJourney($journey->getIdJourney()); ?>
                  <div class="mt-2">
                    <div class="fw-bold mb-1">Contact requesters to ask them to cancel their bookings:</div>
                    <ul class="list-group">
                      <?php foreach ($bookingRows as $br):
                          $u = $br['user'];
                          $b = $br['booking'];
                          $name = trim(($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? '')) ?: ($u['cin'] ?? 'Demandeur');
                          $email = $u['email'] ?? '';
                          $phone = $u['phone'] ?? '';
                          // Build a human-readable subject/body using departure, destination and date (no raw journey id)
                          $dep = htmlspecialchars($journey->departure_city_name ?? '');
                          $dest = htmlspecialchars($journey->destination_city_name ?? '');
                          $dateStr = date('d/m/Y', strtotime($journey->getDepDate()));
                          $timeStr = substr($journey->getDepTime(),0,5);

                          $subjectText = "Please cancel reservation for journey from $dep to $dest on $dateStr";
                          $bodyText = "Hello " . ($u['firstName'] ?? '') . ",\n\nI am the proposer of the journey from $dep to $dest scheduled on $dateStr at $timeStr.\nCould you please cancel your reservation so I can remove this journey?\n\nThank you.";

                          $subject = rawurlencode($subjectText);
                          $body = rawurlencode($bodyText);
                      ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                            <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>
                            <?php if ($email): ?> <div>Email: <a href="mailto:<?= htmlspecialchars($email) ?>?subject=<?= $subject ?>&body=<?= $body ?>"><?= htmlspecialchars($email) ?></a></div><?php endif; ?>
                            <?php if ($phone): ?> <div>Phone: <a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a></div><?php endif; ?>
                            <div class="small text-muted">Places réservées: <?= $b->getRequestedSeats() ?></div>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php else: ?>
                  <form method="post" action="delete_journey.php" onsubmit="return confirm('Delete this journey? This action is irreversible.');">
                    <input type="hidden" name="idJourney" value="<?= $journey->getIdJourney() ?>">
                    <button type="submit" class="btn btn-danger">Delete journey</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
