<?php

class JourneyManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Créer un trajet
    public function create(Journey $journey) {
        if (!$journey->isValid()) {
            throw new InvalidArgumentException("Données du trajet invalides");
        }
        
        $query = $this->db->prepare("
            INSERT INTO journey 
            (price, nbSeats, depDate, depTime, departure, destination, departureDelegation, destinationDelegation, immatCar, preferences) 
            VALUES 
            (:price, :nbSeats, :depDate, :depTime, :departure, :destination, :departureDelegation, :destinationDelegation, :immatCar, :preferences)
        ");
        
        $this->bindJourneyParams($query, $journey);
        
        try {
            $result = $query->execute();
            if ($result) {
                $journey->setIdJourney($this->db->lastInsertId());
            }
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la création du trajet: " . $e->getMessage());
        }
    }
    
    // Lire un trajet par ID
    public function read($idJourney) {
        $query = $this->db->prepare("
            SELECT j.*,
                   dep_city.name as departure_city_name,
                   dest_city.name as destination_city_name,
                   dep_del.name as departure_delegation_name,
                   dest_del.name as destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            WHERE j.idJourney = :idJourney
        ");
        $query->bindValue(':idJourney', $idJourney, PDO::PARAM_INT);
        $query->execute();
        
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            return $this->hydrateJourney($data);
        }
        
        return null;
    }
    
    // Mettre à jour un trajet
    public function update(Journey $journey) {
        if (!$journey->isValid()) {
            throw new InvalidArgumentException("Données du trajet invalides");
        }
        
        $query = $this->db->prepare("
            UPDATE journey SET 
            price = :price, 
            nbSeats = :nbSeats, 
            depDate = :depDate, 
            depTime = :depTime, 
            departure = :departure, 
            destination = :destination, 
            departureDelegation = :departureDelegation, 
            destinationDelegation = :destinationDelegation, 
            immatCar = :immatCar, 
            preferences = :preferences 
            WHERE idJourney = :idJourney
        ");
        
        $this->bindJourneyParams($query, $journey);
        $query->bindValue(':idJourney', $journey->getIdJourney(), PDO::PARAM_INT);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du trajet: " . $e->getMessage());
        }
    }
    
    // Supprimer un trajet
    public function delete($idJourney) {
        $query = $this->db->prepare("DELETE FROM journey WHERE idJourney = :idJourney");
        $query->bindValue(':idJourney', $idJourney, PDO::PARAM_INT);
        
        try {
            return $query->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                throw new Exception("Impossible de supprimer ce trajet car il est référencé par d'autres enregistrements");
            }
            throw $e;
        }
    }
    
    // Récupérer tous les trajets
    public function findAll() {
        $query = $this->db->query("
            SELECT j.*,
                   dep_city.name as departure_city_name,
                   dest_city.name as destination_city_name,
                   dep_del.name as departure_delegation_name,
                   dest_del.name as destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            ORDER BY j.depDate DESC, j.depTime DESC
        ");
        
        $journeys = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $journeys[] = $this->hydrateJourney($data);
        }
        
        return $journeys;
    }
    
    // Rechercher des trajets avec filtres
    public function findWithFilters($filters = []) {
        $sql = "
            SELECT j.*,
                   dep_city.name as departure_city_name,
                   dest_city.name as destination_city_name,
                   dep_del.name as departure_delegation_name,
                   dest_del.name as destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            WHERE 1=1
        ";
        
        $params = [];
        
        // Filtre par date de départ
        if (!empty($filters['depDate'])) {
            $sql .= " AND j.depDate = :depDate";
            $params[':depDate'] = $filters['depDate'];
        }
        
        // Filtre par date future
        if (!empty($filters['futureOnly'])) {
            $sql .= " AND (j.depDate > CURDATE() OR (j.depDate = CURDATE() AND j.depTime > CURTIME()))";
        }
        
        // Filtre par ville de départ
        if (!empty($filters['departure'])) {
            $sql .= " AND j.departure = :departure";
            $params[':departure'] = $filters['departure'];
        }
        
        // Filtre par ville de destination
        if (!empty($filters['destination'])) {
            $sql .= " AND j.destination = :destination";
            $params[':destination'] = $filters['destination'];
        }
        
        // Filtre par délégation de départ
        if (!empty($filters['departureDelegation'])) {
            $sql .= " AND j.departureDelegation = :departureDelegation";
            $params[':departureDelegation'] = $filters['departureDelegation'];
        }
        
        // Filtre par délégation de destination
        if (!empty($filters['destinationDelegation'])) {
            $sql .= " AND j.destinationDelegation = :destinationDelegation";
            $params[':destinationDelegation'] = $filters['destinationDelegation'];
        }
        
        // Filtre par prix maximum
        if (!empty($filters['maxPrice'])) {
            $sql .= " AND j.price <= :maxPrice";
            $params[':maxPrice'] = $filters['maxPrice'];
        }
        
        // Filtre par nombre de sièges minimum
        if (!empty($filters['minSeats'])) {
            $sql .= " AND j.nbSeats >= :minSeats";
            $params[':minSeats'] = $filters['minSeats'];
        }
        
        // Filtre par voiture
        if (!empty($filters['immatCar'])) {
            $sql .= " AND j.immatCar = :immatCar";
            $params[':immatCar'] = $filters['immatCar'];
        }
        
        $sql .= " ORDER BY j.depDate ASC, j.depTime ASC";
        
        $query = $this->db->prepare($sql);
        $query->execute($params);
        
        $journeys = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $journeys[] = $this->hydrateJourney($data);
        }
        
        return $journeys;
    }
    
    // Rechercher des trajets par préférence
    public function findByPreference($preferenceId) {
        $query = $this->db->prepare("
            SELECT j.*,
                   dep_city.name as departure_city_name,
                   dest_city.name as destination_city_name,
                   dep_del.name as departure_delegation_name,
                   dest_del.name as destination_delegation_name
            FROM journey j
            LEFT JOIN city dep_city ON j.departure = dep_city.idCity
            LEFT JOIN city dest_city ON j.destination = dest_city.idCity
            LEFT JOIN delegation dep_del ON j.departureDelegation = dep_del.idDelegation
            LEFT JOIN delegation dest_del ON j.destinationDelegation = dest_del.idDelegation
            WHERE JSON_CONTAINS(j.preferences, :preferenceId)
            ORDER BY j.depDate ASC, j.depTime ASC
        ");
        $query->bindValue(':preferenceId', json_encode($preferenceId));
        $query->execute();
        
        $journeys = [];
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $journeys[] = $this->hydrateJourney($data);
        }
        
        return $journeys;
    }
    
    // Compter le nombre total de trajets
    public function count() {
        $query = $this->db->query("SELECT COUNT(*) FROM journey");
        return $query->fetchColumn();
    }
    
    // Récupérer les trajets d'un conducteur
    public function findByDriver($immatCar) {
        return $this->findWithFilters(['immatCar' => $immatCar]);
    }
    
    // Méthodes utilitaires privées
    private function bindJourneyParams($query, Journey $journey) {
        $query->bindValue(':price', $journey->getPrice());
        $query->bindValue(':nbSeats', $journey->getNbSeats(), PDO::PARAM_INT);
        $query->bindValue(':depDate', $journey->getDepDate());
        $query->bindValue(':depTime', $journey->getDepTime());
        $query->bindValue(':departure', $journey->getDeparture(), PDO::PARAM_INT);
        $query->bindValue(':destination', $journey->getDestination(), PDO::PARAM_INT);
        $query->bindValue(':departureDelegation', $journey->getDepartureDelegation(), PDO::PARAM_INT);
        $query->bindValue(':destinationDelegation', $journey->getDestinationDelegation(), PDO::PARAM_INT);
        $query->bindValue(':immatCar', $journey->getImmatCar());
        $query->bindValue(':preferences', $journey->getPreferences());
    }
    
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
        
        // Ajouter les noms pour l'affichage
        if (isset($data['departure_city_name'])) {
            $journey->departure_city_name = $data['departure_city_name'];
        }
        if (isset($data['destination_city_name'])) {
            $journey->destination_city_name = $data['destination_city_name'];
        }
        if (isset($data['departure_delegation_name'])) {
            $journey->departure_delegation_name = $data['departure_delegation_name'];
        }
        if (isset($data['destination_delegation_name'])) {
            $journey->destination_delegation_name = $data['destination_delegation_name'];
        }
        
        return $journey;
    }
}