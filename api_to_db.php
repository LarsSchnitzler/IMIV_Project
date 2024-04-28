<?php
//-------------start log message-------------
echo "-----------Start of script-----------\n";
$today = date("Y-m-d H:i:s");
echo "Today is: " . $today . "\n";

//------------------Variables------------------
$servername = "localhost";
$username = "512430_4_1";
$password = "PETQsVWrx@J0";
$dbname = "512430_4_1";

//----------------Functions----------------

//function that takes the api array of the form: "year-month-dayThour:minute" and returns datetime-string in the form: "year-month-day hour:minute:second"
function convertTime($time) {
    $date = substr($time, 0, 10); //substr takes startindex and length
    $time = substr($time, 11, 5);
    return $date . " " . $time . ":00";
}

//-------------Database Connection-------------
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful\n";            
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

$url = "https://api.open-meteo.com/v1/dwd-icon";

//------------------API Request----------------------
$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'latitude' => 46.84805485136281,
    'longitude' => 9.501732327669313,
    'hourly' => 'temperature_2m,precipitation,surface_pressure,wind_speed_10m,cloud_cover,lightning_potential',
    'forecast_days' => 1,
]));

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

curl_close($ch);

$data = json_decode($result, true); // Convert JSON to PHP Array, although php array can be indexed by strings

$units = $data['hourly_units']; // units of the weather variables

$time = $data['hourly']['time']; // timestamps-array of the weather variables

// take the data apart into its weather variables.
$temperature_2m = $data['hourly']['temperature_2m'];
$precipitation = $data['hourly']['precipitation'];
$surface_pressure = $data['hourly']['surface_pressure'];
$wind_speed_10m = $data['hourly']['wind_speed_10m'];
$cloud_cover = $data['hourly']['cloud_cover'];
$lightning_potential = $data['hourly']['lightning_potential'];

//-------------Update units in Database-------------
try {
    // Check if units table is empty
    $stmt = $conn->prepare("SELECT COUNT(*) FROM units");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // If units table is empty, insert new entries
        $stmt = $conn->prepare("INSERT INTO units (physical_quantity, unit) VALUES (:physical_quantity, :unit)");
        foreach ($units as $physical_quantity => $unit) { //The arrow syntax (=>) in PHP's foreach loop is used to assign the current element's key to one variable and the current element's value to another variable.
            $stmt->bindParam(':physical_quantity', $physical_quantity);
            $stmt->bindParam(':unit', $unit);
            $stmt->execute();
        }
        echo "Units inserted successfully\n";
    } else {
        // If units table is not empty, update entries
        $stmt = $conn->prepare("UPDATE units SET unit = :unit WHERE physical_quantity = :physical_quantity AND unit != :unit");
        foreach ($units as $physical_quantity => $unit) {
            $stmt->bindParam(':physical_quantity', $physical_quantity);
            $stmt->bindParam(':unit', $unit);
            $stmt->execute();
        }
        echo "Units updated successfully\n";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

//-------------Insert hourly Weather States of current day into Database-------------
try {
    $stmt = $conn->prepare("INSERT INTO weather_states (date_time,temperature_2m, precipitation, surface_pressure, wind_speed_10m, cloud_cover, lightning_potential) VALUES (:date_time, :temperature_2m_hour, :precipitation_hour, :surface_pressure_hour, :wind_speed_10m_hour, :cloud_cover_hour, :lightning_potential_hour)");

    for ($i = 0; $i < 24; $i++) {
        // Bind parameters
        $date_time = convertTime($time[$i]);
        $stmt->bindParam(':date_time', $date_time);
        $stmt->bindParam(':temperature_2m_hour', $temperature_2m[$i]);
        $stmt->bindParam(':precipitation_hour', $precipitation[$i]);
        $stmt->bindParam(':surface_pressure_hour', $surface_pressure[$i]);
        $stmt->bindParam(':wind_speed_10m_hour', $wind_speed_10m[$i]);
        $stmt->bindParam(':cloud_cover_hour', $cloud_cover[$i]);
        $stmt->bindParam(':lightning_potential_hour', $lightning_potential[$i]);
        // Execute the prepared statement
        $stmt->execute();
    }
    echo "New records created successfully\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

//-------------Delete all weather states older than a month-------------
try {
    $stmt = $conn->prepare("DELETE FROM weather_states WHERE date_time < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute();
    echo "Old records deleted successfully\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

//-------------Database Connection Close-------------
$conn = null;

echo "Connection closed\n";

//-------------end log message-------------
echo "-----------End of script-----------\n";

?> 