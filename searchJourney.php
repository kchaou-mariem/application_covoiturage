<?php
include 'connexion.php';
?>
<!Doctype html>
<head>
        <meta charset="UTF-8">


</head>
<body>

    <form action="search_journey_action.php" method="POST">
        <h2>Rechercher un trajet</h2>
        <label for="departure">Departure</label>
        <select name="departure_city" id="departure_city" required>
            <option value="">-- Choose city --</option>
                <?php
                $cities = $conn->query("SELECT * FROM city ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idCity']}'>{$row['name']}</option>";
                }
                ?>
        </select>
        
        <br>
        <label for="destination">Destination</label>
        <select name="destination_city" id="destination_city" required>
            <option value="">-- Choose city --</option>
                <?php
                $cities = $conn->query("SELECT * FROM city ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idCity']}'>{$row['name']}</option>";
                }
                ?>
        </select>

                <br>

        <label for="date">Date</label>
        <input type="date" id="date" name="date">
        <br>

        <label for="seats">seats available</label>
        <input type="number" id="seats" name="seats" min="0" max="4" required>
        <br>

        <h3 >+ Ajout préférence</h3>
         <?php
        $prefs = $conn->query("SELECT * FROM preferences ORDER BY label");
        while ($p = $prefs->fetch_assoc()) {
            echo "<label><input type='checkbox' name='preferences[]' value='{$p['label']}'> {$p['label']}</label><br>";
        }
        ?>

        <br>

        <!--<label>Gender of Driver</label><br>
        <label><input type="radio" name="driverGender" value="male"> Male</label>
        <label><input type="radio" name="driverGender" value="female"> Female</label>
        <br><br>-->


  <input type="submit" name="search" value="Search Journey">
      
    </form>

    <hr>

<?php
if (isset($_POST['search'])) {
    include 'search_journey_action.php';
}
?>

</body>
</html>
