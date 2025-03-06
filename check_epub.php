<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB credentials
define('DB_HOST','localhost:3306');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','library');

try {
    // Create database connection
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME,DB_USER, DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    
    // Get book details
    $bookid = 8;
    $sql = "SELECT tblbooks.BookName, tblbooks.epub_file_path, tblbooks.id as bookid 
            FROM tblbooks WHERE id = :bookid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    
    if ($result) {
        echo "Book details:\n";
        print_r($result);
        
        // Check if file exists
        $epub_path = __DIR__ . '/admin/epubfiles/' . $result->epub_file_path;
        echo "\nFull path: " . $epub_path . "\n";
        echo "File exists: " . (file_exists($epub_path) ? "Yes" : "No") . "\n";
        if (file_exists($epub_path)) {
            echo "File size: " . filesize($epub_path) . " bytes\n";
            echo "File permissions: " . substr(sprintf('%o', fileperms($epub_path)), -4) . "\n";
            echo "File readable: " . (is_readable($epub_path) ? "Yes" : "No") . "\n";
        }
    } else {
        echo "Book not found\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
