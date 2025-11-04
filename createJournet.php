<?php
include 'connexion.php';
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <script>
       
        //city and delegation new option toggles
        function toggleNewCity(type) {
            const select = document.getElementById(type + '_city');
            const div = document.getElementById('new_' + type + '_city');
            if (select.value === 'new') { 
                div.style.display = 'block';
            } else {
                div.style.display = 'none';
            }
        }

        function toggleNewDelegation(type) {
            const select = document.getElementById(type + '_delegation');
            const div = document.getElementById('new_' + type + '_delegation');
            if (select.value === 'new') { 
                div.style.display = 'block';
            } else {
                div.style.display = 'none';
            }
        }

        //car info toggles and fetch
        function toggleCarFields() {
            const hasCar = document.getElementById('hasCar').checked; //true si coché
            document.getElementById('existingCar').style.display = hasCar ? 'block' : 'none'; //affiche div existingCar  si coché //block=>afficher
            document.getElementById('newCar').style.display = hasCar ? 'none' : 'block';
        }
        function fetchCarInfo() {
            const immat = document.getElementById('immat_existing').value.trim();
            if (!immat) {
                alert("Please enter a license plate!");
                return;
                }
            // debug: log the response text before parsing if needed
            fetch('add_journey.php?immat=' + encodeURIComponent(immat)) //envoyer une requête HTTP au serveur => il exécute la requete de recuperaion voiture avec immat,encodeURIComponent:pour éviter des problèmes avec les caractères spéciaux (espaces, accents, tirets…)
                .then(response => response.text()) //recevoir la réponse sous forme de texte brut
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        const display = document.getElementById('car_info_display');//div où afficher les infos de la voiture
                        if (data && data.model)  { 
                            display.innerHTML = `<p><strong>Model:</strong> ${data.model}</p>
                                                <p><strong>Color:</strong> ${data.color}</p>
                                                <p><strong>Seats:</strong> ${data.seats}</p>`;
                        } else {
                            display.innerHTML = "<p style='color:red;'>No car found for this license plate.</p>";
                        }
                    } catch (e) { //si la réponse n'est pas un JSON valide
                        console.error('Invalid JSON response:', text);
                        alert('Error: server response not JSON. Check console (Network tab) for details.');
                    }
                })
                .catch(err => { //gestion des erreurs de réseau
                    console.error(err);
                    alert("Error while loading car data.");
                });
        }


            </script>


</head>
<body>
    <form method="POST" action="add_journey.php">

        <h2>Create a journey</h2>
 
        <h3>Departure :</h3>
        <label for="departure_city">City </label>
        <select name="departure_city" id="departure_city" onchange="toggleNewCity('departure')" required>
            <option value="">-- Choose city --</option>
                <?php
                $cities = $conn->query("SELECT * FROM city ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idCity']}'>{$row['name']}</option>";
                }
                ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_departure_city" style="display:none;">
        <input type="text" name="new_departure_city" placeholder="Enter new city name">
        </div>
        <br>



        <label for="departure_delegation">Neighborhood / Area</label>
        <select name="departure_delegation" id="departure_delegation" onchange="toggleNewDelegation('departure')" required>
            <option value="">-- Choose delegation --</option>
                <?php
                $cities = $conn->query("SELECT * FROM delegation ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idDelegation']}'>{$row['name']}</option>";
                }
                ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_departure_delegation" style="display:none;">
        <input type="text" name="new_departure_delegation" placeholder="Enter new delegation name">
        </div>        
        <br>


        <h3>Destination :</h3>

       <label for="destination_city">Destination</label>
       <select name="destination_city" id="destination_city" onchange="toggleNewCity('destination')" required>
            <option value="">-- Choose city --</option>
                <?php
                $cities = $conn->query("SELECT * FROM city ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idCity']}'>{$row['name']}</option>";
                }
                ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_destination_city" style="display:none;">
        <input type="text" name="new_destination_city" placeholder="Enter new city name">
        </div>
<br>

        <label for="destination_delegation">Neighborhood / Area</label>
        <select name="destination_delegation" id="destination_delegation" onchange="toggleNewDelegation('destination')" required>
            <option value="">-- Choose delegation --</option>
                <?php
                $cities = $conn->query("SELECT * FROM delegation ORDER BY name");
                while ($row = $cities->fetch_assoc()) {
                    echo "<option value='{$row['idDelegation']}'>{$row['name']}</option>";
                }
                ?>
            <option value="new">+ Add new city</option>
        </select>
        <div id="new_destination_delegation" style="display:none;">
        <input type="text" name="new_destination_delegation" placeholder="Enter new delegation name">
        </div>        
        <br>

<br>
        <label for="date">Departure Date</label>
        <input type="date" id="date" name="date">
<br>
        <label for="time">Departure Time</label>
        <input type="time" id="time" name="time">
        <br>
        <label for="time">Price</label>
        <input type="number" id="price" name="price">
        <br>
      
        <h5>Car Information</h5>

        <label>
        <input type="checkbox" id="hasCar" name="hasCar" onchange="toggleCarFields()"> 
        I have already registered my car
        </label>
        <br><br>

        <!-- Existing car -->
        <div id="existingCar" style="display:none;">
            <label for="immat_existing">Car License Plate</label>
            <input type="text" id="immat_existing" name="immat_existing" placeholder="Enter your car plate">
            <button type="button" onclick="fetchCarInfo()">Load Info</button>
            <div id="car_info_display" style="margin-top:10px;"></div>
        </div>

        <!-- New car -->
        <div id="newCar">
            <label for="carModel">Car Model</label>
            <input type="text" id="carModel" name="carModel" placeholder="Car Model"><br>

            <label for="immat">Car License Plate</label>
            <input type="text" id="immat" name="immat" placeholder="License Plate"><br>

            <label for="color">Car Color</label>
            <input type="text" id="color" name="color" placeholder="Color"><br>

            <label for="seats">Seats </label>
            <input type="number" id="seats" name="seats" min="0" max="8" >
        </div>

<br>

        <label for="seatsA">seats available</label>
        <input type="number" id="seatsA" name="seatsA" min="0" max="8" required >
        <br>
        <h3 >+ Ajout préférence</h3>
        <label><input type="checkbox" name="options[]" value="air_conditioning"> Air Conditioning</label>
        <br>
        <label><input type="checkbox" name="options[]" value="smoking"> Smoking</label>
        <br>
        <label><input type="checkbox" name="options[]" value="luggage"> Luggage</label>
        <br>
        <label>Gender of Driver </label>
            <label><input type="radio" name="driverGender" value="male">   Male</label>        
            <label><input type="radio" name="driverGender" value="female"> Female</label>
        <br>
        <label><input type="checkbox" name="options[]" value="stops"> Stops</label>
        <br>
        <label><input type="checkbox" name="options[]" value="pets"> Pets</label>
        <br>
        <button type="submit">Create</button>

        </form>


</body>
</html>