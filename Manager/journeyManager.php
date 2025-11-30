<?php
require_once __DIR__ . '/../Entity/journey.php';
class JourneyManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn; // mysqli connection
    }

    // Créer un trajet
    public function create(Journey $journey) {
        if (!$journey->isValid()) {
            throw new InvalidArgumentException("Données du trajet invalides");
        }

        $stmt = $this->conn->prepare("
            INSERT INTO journey 
                (price, nbSeats, depDate, depTime, departure, destination, departureDelegation, destinationDelegation, immatCar, preferences, cinRequester)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Erreur préparation requête: " . $this->conn->error);
        }

    $price = $journey->getPrice();
    $nbSeats = $journey->getNbSeats();
    $depDate = $journey->getDepDate();
    $depTime = $journey->getDepTime();
    $departure = $journey->getDeparture();
    $destination = $journey->getDestination();
    $departureDelegation = $journey->getDepartureDelegation();
    $destinationDelegation = $journey->getDestinationDelegation();
    $immatCar = $journey->getImmatCar();
    $preferences = $journey->getPreferences();
    $cinRequester = $journey->getCinRequester();

    // types: price (d), nbSeats (i), depDate (s), depTime (s), departure (i), destination (i), departureDelegation (i), destinationDelegation (i), immatCar (s), preferences (s), cinRequester (s)
    $stmt->bind_param(
        "dissiiiisss",
        $price,
        $nbSeats,
        $depDate,
        $depTime,
        $departure,
        $destination,
        $departureDelegation,
        $destinationDelegation,
        $immatCar,
        $preferences,
        $cinRequester
    );


        if (!$stmt->execute()) {
            if ($this->conn->errno == 1062) {
                throw new Exception("Trajet déjà existant !");
            }
            throw new Exception("Erreur lors de la création du trajet: " . $stmt->error);
        }

        $journey->setIdJourney($this->conn->insert_id);
        return true;
    }

    // Lire un trajet
    public function read($idJourney) {
        $sql = "
                 SELECT j.*, 
                     dep_city.name AS departure_city_name,
                     dest_city.name AS destination_city_name,
                     dep_del.name AS departure_delegation_name,
                     dest_del.name AS destination_delegation_name,
                     car.model AS car_model, car.immat AS car_immat,
                     u.firstName AS driver_firstName, u.lastName AS driver_lastName, u.phone AS driver_phone, u.email AS driver_email, u.gender AS driver_gender
                 FROM journey j
                 LEFT JOIN city dep_city ON j.departure = dep_city.idCity
                 LEFT JOIN city dest_city ON j.destination = dest_city.idCity
                 LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
                 LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
                 LEFT JOIN car ON j.immatCar = car.immat
                 LEFT JOIN users u ON j.cinRequester = u.cin
                 WHERE j.idJourney = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idJourney);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return $this->hydrateJourney($data);
        }
        return null;
    }

    // Mettre à jour un trajet
    public function update(Journey $journey) {
        if (!$journey->isValid()) {
            throw new InvalidArgumentException("Données du trajet invalides");
        }

        $stmt = $this->conn->prepare("
            UPDATE journey SET 
                price = ?, nbSeats = ?, depDate = ?, depTime = ?, departure = ?, destination = ?, 
                departureDelegation = ?, destinationDelegation = ?, immatCar = ?, preferences = ?
            WHERE idJourney = ?
        ");

        $stmt->bind_param(
            "dissiiiissi",
            $journey->getPrice(),
            $journey->getNbSeats(),
            $journey->getDepDate(),
            $journey->getDepTime(),
            $journey->getDeparture(),
            $journey->getDestination(),
            $journey->getDepartureDelegation(),
            $journey->getDestinationDelegation(),
            $journey->getImmatCar(),
            $journey->getPreferences(),
            $journey->getIdJourney()
        );

        if (!$stmt->execute()) {
            if ($this->conn->errno == 1062) {
                throw new Exception("Une erreur de doublon est survenue !");
            }
            throw new Exception("Erreur lors de la mise à jour du trajet: " . $stmt->error);
        }

        return true;
    }

    // Supprimer un trajet
    public function delete($idJourney) {
        $stmt = $this->conn->prepare("DELETE FROM journey WHERE idJourney = ?");
        $stmt->bind_param("i", $idJourney);

        if (!$stmt->execute()) {
            if ($this->conn->errno == 1451) {
                throw new Exception("Impossible de supprimer ce trajet car il est référencé ailleurs");
            }
            throw new Exception("Erreur lors de la suppression : " . $stmt->error);
        }

        return true;
    }

    // Récupérer tous les trajets
    public function findAll() {
        $sql = "
            SELECT j.*, 
                   dep_city.name AS departure_city_name,
                   dest_city.name AS destination_city_name,
                   dep_del.name AS departure_delegation_name,
                   dest_del.name AS destination_delegation_name,
                   car.model AS car_model, car.immat AS car_immat,
                   u.firstName AS driver_firstName, u.lastName AS driver_lastName, u.phone AS driver_phone, u.email AS driver_email, u.gender AS driver_gender
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            LEFT JOIN car ON j.immatCar = car.immat
            LEFT JOIN users u ON j.cinRequester = u.cin
            WHERE j.depDate > CURDATE()
            ORDER BY j.depDate DESC, j.depTime DESC
        ";

        $result = $this->conn->query($sql);
        $journeys = [];

        while ($data = $result->fetch_assoc()) {
            $journeys[] = $this->hydrateJourney($data);
        }

        return $journeys;
    }

    // Find journeys published by a specific user (cinRequester)
    public function findByRequester($cin) {
        $sql = "
            SELECT j.*, 
                   dep_city.name AS departure_city_name,
                   dest_city.name AS destination_city_name,
                   dep_del.name AS departure_delegation_name,
                   dest_del.name AS destination_delegation_name,
                   car.model AS car_model, car.immat AS car_immat,
                   u.firstName AS driver_firstName, u.lastName AS driver_lastName, u.phone AS driver_phone, u.email AS driver_email, u.gender AS driver_gender
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            LEFT JOIN car ON j.immatCar = car.immat
            LEFT JOIN users u ON j.cinRequester = u.cin
            WHERE j.cinRequester = ?
            ORDER BY j.depDate DESC, j.depTime DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception('DB prepare failed: ' . $this->conn->error);
        $stmt->bind_param('s', $cin);
        $stmt->execute();
        $result = $stmt->get_result();

        $journeys = [];
        while ($data = $result->fetch_assoc()) {
            $journeys[] = $this->hydrateJourney($data);
        }

        $stmt->close();
        return $journeys;
    }

    // Compter les trajets
    public function count() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM journey");
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // === Méthodes utilitaires ===
    private function hydrateJourney($data) { //Transforme un tableau associatif $data en objet Journey
        $journey = new Journey(
            $data['idJourney'],
            $data['price'],
            $data['nbSeats'],
            $data['depDate'],
            $data['depTime'],
            $data['departure'],
            $data['destination'],
            $data['departureDelegation'],
            $data['destinationDelegation'],
            $data['immatCar'],
            $data['preferences']
        );

        if (isset($data['departure_city_name'])) $journey->departure_city_name = $data['departure_city_name'];
        if (isset($data['destination_city_name'])) $journey->destination_city_name = $data['destination_city_name'];
        if (isset($data['departure_delegation_name'])) $journey->departure_delegation_name = $data['departure_delegation_name'];
        if (isset($data['destination_delegation_name'])) $journey->destination_delegation_name = $data['destination_delegation_name'];

        // Car info if available
        if (isset($data['car_model'])) $journey->car_model = $data['car_model'];
        if (isset($data['car_immat'])) $journey->car_immat = $data['car_immat'];

        // Driver info if available
        if (isset($data['driver_firstName'])) $journey->driver_firstName = $data['driver_firstName'];
        if (isset($data['driver_lastName'])) $journey->driver_lastName = $data['driver_lastName'];
        if (isset($data['driver_phone'])) $journey->driver_phone = $data['driver_phone'];
        if (isset($data['driver_email'])) $journey->driver_email = $data['driver_email'];
        if (isset($data['driver_gender'])) $journey->driver_gender = $data['driver_gender'];
        if (!empty($journey->driver_firstName) || !empty($journey->driver_lastName)) {
            $journey->driver_name = trim(($journey->driver_firstName ?? '') . ' ' . ($journey->driver_lastName ?? ''));
        }

        return $journey;
    }

    //  Recherche des trajets selon critères
public function searchJourneys($departure, $destination, $date = null, $seats = 0, $preferences = []) {
     $sql = "SELECT j.*,
                    c1.name AS departure_city_name,
                    c2.name AS destination_city_name,
                    d1.name AS departure_delegation_name,
                    d2.name AS destination_delegation_name,
                    car.model AS car_model, car.immat AS car_immat
            , u.firstName AS driver_firstName, u.lastName AS driver_lastName, u.phone AS driver_phone, u.email AS driver_email, u.gender AS driver_gender
          FROM journey j
          LEFT JOIN city c1 ON j.departure = c1.idCity
          LEFT JOIN city c2 ON j.destination = c2.idCity
          LEFT JOIN delegation d1 ON j.departureDelegation = d1.idDelegation
          LEFT JOIN delegation d2 ON j.destinationDelegation = d2.idDelegation
      LEFT JOIN car ON j.immatCar = car.immat
      LEFT JOIN users u ON j.cinRequester = u.cin
        WHERE j.departure = ? AND j.destination = ? AND j.depDate > CURDATE()";
    $params = [$departure, $destination];
    $types = "ii";

    if (!empty($date)) {
        $sql .= " AND j.depDate >= ?";
        $params[] = $date;
        $types .= "s";
    }

    if (!empty($seats)) {
        $sql .= " AND j.nbSeats >= ?";
        $params[] = $seats;
        $types .= "i";
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $journeys = [];
    while ($data = $result->fetch_assoc()) {
        $journey = $this->hydrateJourney($data);
        // Optionnel : filtrer par préférences
        if (!empty($preferences)) {
            $journeyPrefs = $journey->getPreferencesArray();
            $match = !array_diff($preferences, $journeyPrefs);
            if (!$match) continue;
        }
        $journeys[] = $journey;
    }

    return $journeys;
}

}
?>
