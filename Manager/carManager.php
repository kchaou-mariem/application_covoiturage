<?php
class CarManager {
    private $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    // CREATE
    public function create(Car $car) {
        if (!$car->isValid()) {
            throw new InvalidArgumentException("Les données de la voiture sont invalides");
        }

        if ($this->existsByImmat($car->getImmat())) {
            throw new Exception("Une voiture avec cette immatriculation existe déjà");
        }

        $stmt = $this->conn->prepare("INSERT INTO car (immat, model, color, seats) VALUES (?, ?, ?, ?)");
        $immat = $car->getImmat();
    $model = $car->getModel();
    $color = $car->getColor();
    $seats = $car->getSeats();
    $stmt->bind_param("sssi", $immat, $model, $color, $seats);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'ajout de la voiture : " . $stmt->error);
        }

        return true;
    }

    // READ - Trouver une voiture par immatriculation
    public function findByImmat($immat) {
        $stmt = $this->conn->prepare("SELECT * FROM car WHERE immat = ?");
        $stmt->bind_param("s", $immat);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            return $this->hydrateCar($data);
        }
        return null;
    }

    // READ - Trouver toutes les voitures
    public function findAll() {
        $result = $this->conn->query("SELECT * FROM car ORDER BY model");
        $cars = [];
        while ($data = $result->fetch_assoc()) {
            $cars[] = $this->hydrateCar($data);
        }
        return $cars;
    }

    public function getSeatsByImmat($immatCar) {
    $stmt = $this->conn->prepare("SELECT seats FROM car WHERE immat = ?");
    $stmt->bind_param("s", $immatCar);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['seats'];
    } else {
        return null; // aucune voiture trouvée avec cette immatriculation
    }
}


    // UPDATE
    public function update(Car $car) {
        if (!$car->isValid()) {
            throw new InvalidArgumentException("Les données de la voiture sont invalides");
        }

        $stmt = $this->conn->prepare("UPDATE car SET model = ?, color = ?, seats = ? WHERE immat = ?");
        $stmt->bind_param("ssis",
            $car->getModel(),
            $car->getColor(),
            $car->getSeats(),
            $car->getImmat()
        );

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour : " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    // DELETE
    public function delete($immat) {
        $stmt = $this->conn->prepare("DELETE FROM car WHERE immat = ?");
        $stmt->bind_param("s", $immat);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la suppression : " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    // Vérifier existence par immatriculation
    public function existsByImmat($immat) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM car WHERE immat = ?");
        $stmt->bind_param("s", $immat);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        return $count > 0;
    }

    // Hydrater un objet Car depuis un tableau
    private function hydrateCar($data) {
        return new Car(
            $data['immat'],
            $data['model'],
            $data['color'],
            $data['seats']
        );
    }
}
