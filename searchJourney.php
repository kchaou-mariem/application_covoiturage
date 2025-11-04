<?php
include 'connexion.php';
?>
<!Doctype html>
<head>
        <meta charset="UTF-8">


</head>
<body>

    <form>
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
        <input type="date" id="date">
        <br>

        <label for="seats">seats available</label>
        <input type="number" id="seats" name="seats" min="0" max="4" required>
        <br>

        <h3 >+ Ajout préférence</h3>
        <label><input type="checkbox" name="options" value="air_conditioning"> Air Conditioning</label>
        <br>

        <label><input type="checkbox" name="options" value="smoking"> Smoking</label>
        <br>

        <label><input type="checkbox" name="options" value="luggage"> Luggage</label>
        <br>

        <label>Gender of Driver </label>
            <label><input type="radio" name="driverGender" value="male"> Male</label>
            <label><input type="radio" name="driverGender" value="female"> Female</label>   
<br>

        <label><input type="checkbox" name="options" value="stops"> Stops</label>
        <br>

        <label><input type="checkbox" name="options" value="pets"> Pets</label>
<br>

<button type="submit">Search</button>
      
    </form>
</body>
</html>
