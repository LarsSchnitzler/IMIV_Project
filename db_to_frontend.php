<?php

//-------------Variables-------------
$servername = "localhost";
$username = "512430_4_1";
$password = "PETQsVWrx@J0";
$dbname = "512430_4_1";

//-------------Database Connection-------------
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful\n";            
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Retrieve data from  weather_states-table
$sql = "SELECT * FROM weather_states";
$result = $conn->query($sql);

//------------------Output----------------------
if ($result->rowCount() > 0) {

    $rows = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = $row;
    }

    $json = json_encode($rows);

    header('Content-Type: application/json');

    // Output the JSON data
    echo $json;
} else {
    echo "No data found.";
}

// Close the database connection
$conn = null;

?>