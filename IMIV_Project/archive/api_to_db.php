<?php
//----check wether file has been executed in the last 23h----
$lastExecFile = '../private/last_execution.txt';

if (file_exists($lastExecFile)) {
    $lastExec = strtotime(file_get_contents($lastExecFile));
    
    if (!isset($lastExec) || empty($lastExec)) {
        $lastExec = time();
    }

    $elapsed = time() - $lastExec;

    if ($elapsed < 23 * 60 * 60) {
        die("The script has already run within the last 23 hours.\n");
    }
}

file_put_contents($lastExecFile, time());

//-------------get log file-------------
$logFile = '../private/api_to_db_logfile.log';

$message = "-----------Start of script-----------\n";
$message .= "Today is: " . date("Y-m-d H:i:s") . "\n";

file_put_contents($logFile, $message, FILE_APPEND);

//------------------Variables------------------
$servername = "localhost";
$username = "512430_4_1";
$password = "PETQsVWrx@J0";
$dbname = "512430_4_1";

//----------------Functions----------------

//-------------Database Connection-------------
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    file_put_contents($logFile, "Database connection successful\n", FILE_APPEND);        
} catch (Exception $e) {
    file_put_contents($logFile, "Database connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
}

//----------------------------API Request--------------------------------
$url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/Chur%2C%20Switzerland/today?unitGroup=metric&elements=datetimeEpoch%2Ctemp%2Chumidity%2Cprecip%2Cwindspeed%2Cpressure%2Ccloudcover%2Cvisibility%2Csolarenergy%2Csunrise&include=hours%2Cdays&key=XCSJ35ABYDTZUGVYUJTZ23HDP&contentType=json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);

// logging the HTTP response code
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
file_put_contents($logFile, 'API - HTTP response code: ' . $httpcode . "\n", FILE_APPEND);

// logging the error message
if (curl_errno($ch)) {
    file_put_contents($logFile, 'Error:' . curl_error($ch) . "\n", FILE_APPEND);
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
        file_put_contents($logFile, 'Error:' . curl_error($ch) . "\n", FILE_APPEND);
    }

    // Commit the transaction
    $conn->commit();
} catch(PDOException $e) {
    // Roll back the transaction if something failed
    $conn->rollBack();
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

//-------------Delete all weather states older than a month-------------
try {
    $stmt = $conn->prepare("DELETE FROM weather_states WHERE FROM_UNIXTIME(datetimeEpoch) < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute();
    file_put_contents($logFile, "Older weather states deleted\n", FILE_APPEND);
} catch(PDOException $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

//-------------Database Connection Close-------------
$conn = null;

file_put_contents($logFile, "Database connection closed\n", FILE_APPEND);

//-------------end log message-------------
file_put_contents($logFile, "-----------End of script-----------\n", FILE_APPEND);

?> 