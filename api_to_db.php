<?php
//-------------start log message-------------

use function PHPSTORM_META\type;

echo "-----------Start of script-----------\n";
$today = date("Y-m-d H:i:s");
echo "Today is: " . $today . "\n";

//-------------set html header to json
header('Content-Type: application/json');

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

//----------------------------API Request--------------------------------
$url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/Chur%2C%20Switzerland/today?unitGroup=metric&elements=datetimeEpoch%2Ctemp%2Chumidity%2Cprecip%2Cwindspeed%2Cpressure%2Ccloudcover%2Cvisibility%2Csolarenergy%2Csunrise&include=hours%2Cdays&key=XCSJ35ABYDTZUGVYUJTZ23HDP&contentType=json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);

// echoing the HTTP response code
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo 'API - HTTP response code: ' . $httpcode . "\n";

// echoing the error message
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch) . "\n";
}

curl_close($ch);

// reducing the response
$data = json_decode($result, true); // Convert JSON to PHP Array, although php array can be indexed by strings

$sunrise = $data['days'][0]['sunrise']; // gets sunrise time for that day
$dataHourly = $data['days'][0]['hours']; // reduces response to array of hourly states

/* echo "sunrise: " . $sunrise . "\n";
var_dump($dataHourly); */

//-------------Insert hourly Weather States of current day into Database-------------
try {
    // Start the transaction
    $conn->beginTransaction();

    foreach ($dataHourly as $hour) {
        //Extract hourly Weather Variables of current day
        $datetimeEpoch = ($hour['datetimeEpoch']);
        $temp = $hour['temp'];
        $humidity = $hour['humidity'];
        $precip = $hour['precip'];
        $pressure = $hour['pressure'];
        $windspeed = $hour['windspeed'];
        $visibility = $hour['visibility'];
        $cloudcover = $hour['cloudcover'];
        $solarenergy = $hour['solarenergy'];

/*         echo "datetimeEpoch: " . gettype($datetimeEpoch) . "\n";
        echo "temp: " . gettype($temp) . "\n";
        echo "humidity: " . gettype($humidity) . "\n";
        echo "precip: " . gettype($precip) . "\n";
        echo "pressure: " . gettype($pressure) . "\n";
        echo "windspeed: " . gettype($windspeed) . "\n";
        echo "visibility: " . gettype($visibility) . "\n";
        echo "cloudcover: " . gettype($cloudcover) . "\n";
        echo "solarenergy: " . gettype($solarenergy) . "\n";
        echo "sunrise: " . gettype($sunrise) . "\n"; */

        //Insert hourly Weather Variables of current day into one new entry in Database
        $stmt = $conn->prepare("INSERT INTO weather_states (datetimeEpoch, temp, humidity, precip, pressure, windspeed, visibility, cloudcover, solarenergy, sunrise) VALUES (:datetimeEpoch, :temp, :humidity, :precip, :pressure, :windspeed, :visibility, :cloudcover, :solarenergy, :sunrise)");
        $stmt->bindParam(':datetimeEpoch', $datetimeEpoch);
        $stmt->bindParam(':temp', $temp);
        $stmt->bindParam(':humidity', $humidity);
        $stmt->bindParam(':precip', $precip);
        $stmt->bindParam(':pressure', $pressure);
        $stmt->bindParam(':windspeed', $windspeed);
        $stmt->bindParam(':visibility', $visibility);
        $stmt->bindParam(':cloudcover', $cloudcover);
        $stmt->bindParam(':solarenergy', $solarenergy);

        $stmt->bindParam(':sunrise', $sunrise);

        $stmt->execute();
        echo "New entry with datetimeEpoch: " .$datetimeEpoch . " created successfully\n";
    }

    // Commit the transaction
    $conn->commit();
} catch(PDOException $e) {
    // Roll back the transaction if something failed
    $conn->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

//-------------Delete all weather states older than a month-------------
try {
    $stmt = $conn->prepare("DELETE FROM weather_states WHERE FROM_UNIXTIME(datetimeEpoch) < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute();
    echo "Old entries deleted successfully\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

//-------------Database Connection Close-------------
$conn = null;

echo "Connection closed\n";

//-------------end log message-------------
echo "-----------End of script-----------\n";

?> 