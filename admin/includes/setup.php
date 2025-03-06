<?php
// Create tables if they don't exist
include('config.php');

try {
    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS tblreviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        BookId INT NOT NULL,
        UserId INT NOT NULL,
        Review TEXT NOT NULL,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (BookId) REFERENCES tblbooks(id) ON DELETE CASCADE,
        FOREIGN KEY (UserId) REFERENCES tblusers(id) ON DELETE CASCADE
    )";
    $dbh->exec($sql);
    
    // Create ratings table
    $sql = "CREATE TABLE IF NOT EXISTS tblratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        BookId INT NOT NULL,
        UserId INT NOT NULL,
        Rating INT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (BookId) REFERENCES tblbooks(id) ON DELETE CASCADE,
        FOREIGN KEY (UserId) REFERENCES tblusers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rating (BookId, UserId)
    )";
    $dbh->exec($sql);
    
    echo "Tables created successfully";
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
