<?php
   echo "<pre>";
   print_r(PDO::getAvailableDrivers());
   
   
$servername = "DESKTOP-QFU816L\SQLEXPRESS";
$username = "nubuilder";
$password = "nu";
$database = "nubuilder4";
$port = "1433";

/*
try {
    $conn = new PDO("sqlsrv:server=$servername,$port;Database=$database;ConnectionPooling=0", $username, $password);
} catch (PDOException $e) {
    echo ("Error connecting to SQL Server: " . $e->getMessage());
}   
*/

/* Connect using Windows Authentication. */
try {
    $pdo = new PDO("sqlsrv:server=$servername,$port;Database=$database;ConnectionPooling=0", "", "");
} catch (PDOException $e) {
    echo ("Error connecting to SQL Server: " . $e->getMessage());
}  


$sql =  "SELECT set_smtp_username FROM zzzzsys_setup";
		
$statement = $pdo->prepare($sql);
$statement->execute();
$rows = $statement->fetch(PDO::FETCH_OBJ);

echo "1. Query: <br><br>";
print "set_smtp_username: ".$rows->set_smtp_username;