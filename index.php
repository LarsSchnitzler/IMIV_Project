<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test-Document</title>
</head>
<body>
    <?php
        //------------------Variables------------------
        $servername = "localhost";
        $username = "512430_2_1";
        $password = "QumwPH08TVid";
        $dbname = "512430_2_1";

        //-------------Database Connection-------------
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Database connection successful";            
        } catch (Exception $e) {
            echo "Database connection failed: " . $e->getMessage();
        }
    ?>
</body>
</html>