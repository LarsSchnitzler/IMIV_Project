<?php
//will stay in unixtimecode for all serverside actions. converting to datetime-format when making and sending json data to client.
require_once 'config.php';
header('Content-Type: application/json');

//----------------------------------Functions----------------------------------
function get_startEnd_unixtime() {
    //---------Read out http-parameter 'day' and 'daysBack'---------------
    if(isset($_GET['day'])) {
        $day = $_GET['day'];
    } else {
        $day = date('Y-m-d');
    }

    if(isset($_GET['daysBack'])) {
        $daysBack = $_GET['daysBack'];
    } else {
        $daysBack = 0;
    }
    
    $start = strtotime($day . ' 00:00:00') - 86400 * $daysBack;
    $end = strtotime($day . ' 23:59:59');

    return array('start' => $start, 'end' => $end);
}

function weather_states_separate($ws_tp) {
    try{
        // get associative arrays of time and weather-variables
        $temp = array_column($ws_tp, 'temp', 'datetimeEpoch');
        $new_temp = [];
        foreach($temp as $key => $value) {
            $new_temp[date('Y-m-d H:i:s', $key)] = $value;
        }
        $temp = $new_temp;

        $humidity = array_column($ws_tp, 'humidity', 'datetimeEpoch');
        $new_humidity = [];
        foreach($humidity as $key => $value) {
            $new_humidity[date('Y-m-d H:i:s', $key)] = $value;
        }
        $humidity = $new_humidity;

        $precipitation = array_column($ws_tp, 'precip', 'datetimeEpoch');
        $new_precipitation = [];
        foreach($precipitation as $key => $value) {
            $new_precipitation[date('Y-m-d H:i:s', $key)] = $value;
        }
        $precipitation = $new_precipitation;

        $pressure = array_column($ws_tp, 'pressure', 'datetimeEpoch');
        $new_pressure = [];
        foreach($pressure as $key => $value) {
            $new_pressure[date('Y-m-d H:i:s', $key)] = $value;
        }
        $pressure = $new_pressure;

        $windspeed = array_column($ws_tp, 'windspeed', 'datetimeEpoch');
        $new_windspeed = [];
        foreach($windspeed as $key => $value) {
            $new_windspeed[date('Y-m-d H:i:s', $key)] = $value;
        }
        $windspeed = $new_windspeed;

        $visibility = array_column($ws_tp, 'visibility', 'datetimeEpoch');
        $new_visibility = [];
        foreach($visibility as $key => $value) {
            $new_visibility[date('Y-m-d H:i:s', $key)] = $value;
        }
        $visibility = $new_visibility;

        $cloud_cover = array_column($ws_tp, 'cloudcover', 'datetimeEpoch');
        $new_cloud_cover = [];
        foreach($cloud_cover as $key => $value) {
            $new_cloud_cover[date('Y-m-d H:i:s', $key)] = $value;
        }
        $cloud_cover = $new_cloud_cover;

        $solarenergy = array_column($ws_tp, 'solarenergy', 'datetimeEpoch');
        $new_solarenergy = [];
        foreach($solarenergy as $key => $value) {
            $new_solarenergy[date('Y-m-d H:i:s', $key)] = $value;
        }
        $solarenergy = $new_solarenergy;

        // go through every 24th element of $ws_tp and put value of 'sunrise' as value of new associative array. transform 'datetimeEpoch' to 'datetime', subtract the time of day, so that its only day. then put that as key. 
        $sunrise = [];
        for($i = 0; $i < count($ws_tp); $i+=23){
            $sunrise[$ws_tp[$i]['datetimeEpoch']] = $ws_tp[$i]['sunrise'];
        }
        foreach($sunrise as $key => $value) {
            $sunrise[date('Y-m-d', $key)] = $value;
            unset($sunrise[$key]);
        }

        //return all associative arrays in one big array
        return array('temperature' => $temp, 'humidity' => $humidity, 'precipitation' => $precipitation, 'pressure' => $pressure, 'windspeed' => $windspeed, 'visibility' => $visibility, 'cloudcover' => $cloud_cover, 'solarenergy' => $solarenergy, 'sunrise' => $sunrise);

    } catch(Exception $e) {
        echo json_encode(["error: " . $e->getMessage()]);
        return false;
    }
}

//-------------------------------Database Connection-------------------------------
try {
    $conn = new PDO($dsn, $username, $password, $options);   
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

//----------------Select Weather States (for given days -> by GET Params) and Units from Database-----------------
try {
    $s = get_startEnd_unixtime()['start'];
    $e = get_startEnd_unixtime()['end'];
    
    // Prepare and execute the weather-SQL statement
    $stmt = $conn->prepare("SELECT * FROM weather_states WHERE datetimeEpoch >= " . $s . " AND datetimeEpoch <= " . $e . ";");
    $stmt->execute();
    $weather_states = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(["error: " . $e->getMessage()]);
}

try {
    // Prepare and execute the units-SQL statement
    $stmt = $conn->prepare("SELECT * FROM units");
    $stmt->execute();
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $units = array_column($units, 'unit', 'physical_quantity'); //so array_column goes through an array and takes some values of the associative sub-arrays. And puts them into a new associative array. in this case ist value to 'unit' as the value and the value to 'physical_quantity' as the key.
} catch(PDOException $e) {
    echo json_encode(["error: " . $e->getMessage()]);
}

//-------------------Separate Weather States into Weather Variables-----------------
$weatherVariables_tp_s_data = weather_states_separate($weather_states);

//-----------------------------------Output----------------------------------------
$output = ['data' => $weatherVariables_tp_s_data, 'units' => $units];
echo json_encode($output);

// Close the database connection
$conn = null;
?>