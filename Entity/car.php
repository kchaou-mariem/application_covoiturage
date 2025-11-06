<?php
class Car {
    private $immat;
    private $model;
    private $color;
    private $seats;

    public function __construct($immat = null, $model = null, $color = null, $seats = null) {
        $this->immat = $immat;
        $this->model = $model;
        $this->color = $color;
        $this->seats = $seats;
    }

    // --- Getters ---
    public function getImmat() { return $this->immat; }
    public function getModel() { return $this->model; }
    public function getColor() { return $this->color; }
    public function getSeats() { return $this->seats; }

    // --- Setters ---
    public function setImmat($immat) { $this->immat = $immat; }
    public function setModel($model) { $this->model = $model; }
    public function setColor($color) { $this->color = $color; }
    public function setSeats($seats) { $this->seats = $seats; }

    // --- Validation ---
    public function isValid() {
    return !empty($this->immat)
        && !empty($this->model)
        && !empty($this->color)
        && !empty($this->seats)
        && is_numeric($this->seats)
        && $this->seats > 0;
}

}
