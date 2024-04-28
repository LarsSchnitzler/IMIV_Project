<?php

//-------------Variables-------------
$servername = "localhost";
$username = "512430_4_1";
$password = "PETQsVWrx@J0";
$dbname = "512430_4_1";

$result_weather;
$result_units;

$data = array(); //will be the output array

//-------------Functions-------------
function convertTime_backTo_withT($time) {
    $date = substr($time, 0, 10); //substr takes startindex and length
    $time = substr($time, 11, 5);
    return $date . "T" . $time . ":00";
}

//Set the header  
header("Content-Type: application/json");

//-------------Database Connection-------------
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $data['DB_Connection'] = "successful";            
} catch (Exception $e) {
    $data['DB_Connection'] = "failed: " . $e->getMessage();
}

//-------------Database Queries-------------
try {
    // Retrieve data from weather_states-table
    $sql = "SELECT date_time, temperature_2m, precipitation, surface_pressure, wind_speed_10m, cloud_cover, lightning_potential FROM weather_states";
    $result_weather = $conn->query($sql);
} catch (PDOException $e) {
    $data['DB_WeatherQuery'] = $e->getMessage();
}

try {
    // Retrieve data from units-table
    $sql = "SELECT physical_quantity, unit FROM units";
    $result_units = $conn->query($sql);
} catch (PDOException $e) {
    $data['DB_UnitsQuery'] = $e->getMessage();
}

//------------------Output----------------------
//Set units data
if ($result_units->rowCount() > 0) {
    $units_array = array();
    while ($element = $result_units->fetch(PDO::FETCH_ASSOC)) {
        $units_array[$element['physical_quantity']] = $element['unit'];
    }
    $data['units'] = $units_array;
}
//Set weather data
if ($result_weather->rowCount() > 0) {
    $data['weather'] = $result_weather->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data['weather'] as $key => $value) {
        $data['weather'][$key]['date_time'] = convertTime_backTo_withT($value['date_time']);
    }
}

echo json_encode($data);

// Close the database connection
$conn = null;

?>