<?php
session_start();
require_once 'config/connexion.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_cin']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: authentification.php');
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Création d'une ville
    if (isset($_POST['create_city'])) {
        $cityName = $_POST['city_name'];
        $delegationId = $_POST['delegation_id'];
        $stmt = $conn->prepare("INSERT INTO city (name, idDelegation) VALUES (?, ?)");
        $stmt->bind_param("si", $cityName, $delegationId);
        if ($stmt->execute()) {
            $message = "City created successfully";
        } else {
            $error = "Error creating city: " . $conn->error;
        }
    }
    
    // Création d'une délégation
    if (isset($_POST['create_delegation'])) {
        $delegationName = $_POST['delegation_name'];
        $stmt = $conn->prepare("INSERT INTO delegation (name) VALUES (?)");
        $stmt->bind_param("s", $delegationName);
        if ($stmt->execute()) {
            $message = "Delegation created successfully";
        } else {
            $error = "Error creating delegation: " . $conn->error;
        }
    }
    
    // Création d'une voiture
    if (isset($_POST['create_car'])) {
        $carType = $_POST['car_type'];
        $carModel = $_POST['car_model'];
        $carMatricule = $_POST['car_matricule'];
        $cinOwner = $_POST['cin_owner'];
        $stmt = $conn->prepare("INSERT INTO car (type, model, matricule, cinOwner) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $carType, $carModel, $carMatricule, $cinOwner);
        if ($stmt->execute()) {
            $message = "Car created successfully";
        } else {
            $error = "Error creating car: " . $conn->error;
        }
    }
    
    // Création d'un trajet
    if (isset($_POST['create_journey'])) {
        $departure = $_POST['departure'];
        $destination = $_POST['destination'];
        $depDate = $_POST['depDate'];
        $depTime = $_POST['depTime'];
        $nbSeats = $_POST['nbSeats'];
        $price = $_POST['price'];
        $cinRequester = $_POST['cinRequester'];
        
        $stmt = $conn->prepare("INSERT INTO journey (departure, destination, depDate, depTime, nbSeats, price, cinRequester) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissids", $departure, $destination, $depDate, $depTime, $nbSeats, $price, $cinRequester);
        if ($stmt->execute()) {
            $message = "Journey created successfully";
        } else {
            $error = "Error creating journey: " . $conn->error;
        }
    }
    
    // Mise à jour d'un trajet
    if (isset($_POST['update_journey'])) {
        $journeyId = $_POST['journey_id'];
        $departure = $_POST['departure'];
        $destination = $_POST['destination'];
        $depDate = $_POST['depDate'];
        $depTime = $_POST['depTime'];
        $nbSeats = $_POST['nbSeats'];
        $price = $_POST['price'];
        
        $stmt = $conn->prepare("UPDATE journey SET departure = ?, destination = ?, depDate = ?, depTime = ?, nbSeats = ?, price = ? WHERE idJourney = ?");
        $stmt->bind_param("iissidi", $departure, $destination, $depDate, $depTime, $nbSeats, $price, $journeyId);
        if ($stmt->execute()) {
            $message = "Journey updated successfully";
        } else {
            $error = "Error updating journey: " . $conn->error;
        }
    }
    
    // Suppression d'un trajet
    if (isset($_POST['delete_journey'])) {
        $journeyId = $_POST['journey_id'];
        
        // Supprimer les réservations liées
        $stmt = $conn->prepare("DELETE FROM booking WHERE idJourney = ?");
        $stmt->bind_param("i", $journeyId);
        $stmt->execute();
        
        // Supprimer le trajet
        $stmt = $conn->prepare("DELETE FROM journey WHERE idJourney = ?");
        $stmt->bind_param("i", $journeyId);
        $stmt->execute();
        
        $message = "Journey deleted successfully";
    }
}

// Récupérer les données nécessaires pour les formulaires
$cities = $conn->query("SELECT * FROM city ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$delegations = $conn->query("SELECT * FROM delegation ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT cin, firstName, lastName FROM users WHERE role = 'user' ORDER BY firstName")->fetch_all(MYSQLI_ASSOC);

// Récupérer tous les trajets
$journeys = $conn->query("
    SELECT j.*, 
           c1.name AS dep_city, c2.name AS dest_city,
           u.firstName, u.lastName, u.email,
           COUNT(b.idBooking) as booking_count
    FROM journey j
    JOIN city c1 ON j.departure = c1.idCity
    JOIN city c2 ON j.destination = c2.idCity
    JOIN users u ON j.cinRequester = u.cin
    LEFT JOIN booking b ON j.idJourney = b.idJourney
    GROUP BY j.idJourney
    ORDER BY j.depDate DESC, j.depTime DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                    <span>Admin Panel</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">
                            <i class="fas fa-users me-2"></i>
                            Users Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_journeys.php">
                            <i class="fas fa-route me-2"></i>
                            Journeys Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_bookings.php">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Bookings Management
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Journeys Management</h1>
                <div class="btn-toolbar">
                    <div class="btn-group me-2">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createJourneyModal">
                            <i class="fas fa-plus me-1"></i>Journey
                        </button>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createCityModal">
                            <i class="fas fa-city me-1"></i>City
                        </button>
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#createDelegationModal">
                            <i class="fas fa-map-marked me-1"></i>Delegation
                        </button>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#createCarModal">
                            <i class="fas fa-car me-1"></i>Car
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Journeys</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Driver</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Seats</th>
                                    <th>Price</th>
                                    <th>Bookings</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($journeys as $journey): ?>
                                <tr>
                                    <td><?= $journey['idJourney'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($journey['firstName'] . ' ' . $journey['lastName']) ?>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($journey['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($journey['dep_city']) ?></td>
                                    <td><?= htmlspecialchars($journey['dest_city']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($journey['depDate'])) ?></td>
                                    <td><?= substr($journey['depTime'], 0, 5) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $journey['nbSeats'] ?></span>
                                    </td>
                                    <td><?= $journey['price'] ?> DT</td>
                                    <td>
                                        <span class="badge bg-success"><?= $journey['booking_count'] ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editJourneyModal<?= $journey['idJourney'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this journey? This will also delete all related bookings!')">
                                            <input type="hidden" name="journey_id" value="<?= $journey['idJourney'] ?>">
                                            <input type="hidden" name="delete_journey" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modals pour éditer chaque trajet -->
<?php foreach ($journeys as $journey): ?>
<div class="modal fade" id="editJourneyModal<?= $journey['idJourney'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Journey #<?= $journey['idJourney'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="journey_id" value="<?= $journey['idJourney'] ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Departure City *</label>
                            <select class="form-select" name="departure" required>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['idCity'] ?>" <?= $city['idCity'] == $journey['departure'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destination City *</label>
                            <select class="form-select" name="destination" required>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['idCity'] ?>" <?= $city['idCity'] == $journey['destination'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="depDate" value="<?= $journey['depDate'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time *</label>
                            <input type="time" class="form-control" name="depTime" value="<?= $journey['depTime'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Number of Seats *</label>
                            <input type="number" class="form-control" name="nbSeats" min="1" max="8" value="<?= $journey['nbSeats'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price (DT) *</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.5" value="<?= $journey['price'] ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_journey" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Journey
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal créer un trajet -->
<div class="modal fade" id="createJourneyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-route me-2"></i>Create Journey</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Departure City *</label>
                            <select class="form-select" name="departure" required>
                                <option value="">Select city...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['idCity'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destination City *</label>
                            <select class="form-select" name="destination" required>
                                <option value="">Select city...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['idCity'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="depDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time *</label>
                            <input type="time" class="form-control" name="depTime" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Number of Seats *</label>
                            <input type="number" class="form-control" name="nbSeats" min="1" max="8" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (DT) *</label>
                            <input type="number" class="form-control" name="price" min="0" step="0.5" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Driver *</label>
                            <select class="form-select" name="cinRequester" required>
                                <option value="">Select driver...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['cin'] ?>"><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_journey" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create Journey
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal créer une ville -->
<div class="modal fade" id="createCityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-city me-2"></i>Create City</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">City Name *</label>
                        <input type="text" class="form-control" name="city_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delegation *</label>
                        <select class="form-select" name="delegation_id" required>
                            <option value="">Select delegation...</option>
                            <?php foreach ($delegations as $delegation): ?>
                                <option value="<?= $delegation['idDelegation'] ?>"><?= htmlspecialchars($delegation['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_city" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Create City
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal créer une délégation -->
<div class="modal fade" id="createDelegationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-marked me-2"></i>Create Delegation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Delegation Name *</label>
                        <input type="text" class="form-control" name="delegation_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_delegation" class="btn btn-info">
                        <i class="fas fa-save me-2"></i>Create Delegation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal créer une voiture -->
<div class="modal fade" id="createCarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-car me-2"></i>Create Car</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Car Type *</label>
                        <input type="text" class="form-control" name="car_type" placeholder="e.g., Sedan, SUV" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Car Model *</label>
                        <input type="text" class="form-control" name="car_model" placeholder="e.g., Peugeot 208" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matricule *</label>
                        <input type="text" class="form-control" name="car_matricule" placeholder="e.g., 123 TU 4567" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner *</label>
                        <select class="form-select" name="cin_owner" required>
                            <option value="">Select owner...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['cin'] ?>"><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_car" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Create Car
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar .nav-link:hover {
    color: #0d6efd;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
