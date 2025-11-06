<?php

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
            (price, nbSeats, depDate, depTime, departure, destination, departureDelegation, destinationDelegation, immatCar, preferences)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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

$stmt->bind_param(
    "sdssssssss",
    $price,
    $nbSeats,
    $depDate,
    $depTime,
    $departure,
    $destination,
    $departureDelegation,
    $destinationDelegation,
    $immatCar,
    $preferences
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
                   dest_del.name AS destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
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
                   dest_del.name AS destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            ORDER BY j.depDate DESC, j.depTime DESC
        ";

        $result = $this->conn->query($sql);
        $journeys = [];

        while ($data = $result->fetch_assoc()) {
            $journeys[] = $this->hydrateJourney($data);
        }

        return $journeys;
    }

    // Compter les trajets
    public function count() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM journey");
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    // === Méthodes utilitaires ===
    private function hydrateJourney($data) {
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

        return $journey;
    }
}
?>
