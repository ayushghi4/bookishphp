<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB credentials.
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','library');

// Establish database connection.
try {
    error_log("Attempting to connect to database: " . DB_NAME);
    
    $dbh = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME,
        DB_USER,
        DB_PASS,
        array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        )
    );
    
    error_log("Database connection successful");
    
    // Test the connection with a simple query
    $test = $dbh->query("SELECT COUNT(*) as count FROM tblbooks");
    $count = $test->fetch();
    error_log("Total books in database: " . $count->count);
    
    // Create tblreadinghistory table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS tblreadinghistory (
        id int(11) NOT NULL AUTO_INCREMENT,
        UserId int(11) NOT NULL,
        BookId int(11) NOT NULL,
        LastPage varchar(255) NOT NULL,
        ReadDate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        Status varchar(50) NOT NULL DEFAULT 'reading',
        PRIMARY KEY (id),
        KEY UserId (UserId),
        KEY BookId (BookId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    try {
        $dbh->exec($sql);
    } catch(PDOException $e) {
        error_log("Error creating tblreadinghistory table: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        die("Error: Database 'library' does not exist. Please import the database schema first.");
    }
    die("Database connection failed: " . $e->getMessage());
}
?>