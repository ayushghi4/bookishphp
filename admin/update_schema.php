<?php
require_once('../includes/config.php');

try {
    // Drop the old table if it exists
    $sql = "DROP TABLE IF EXISTS tblreadinghistory";
    $dbh->exec($sql);
    
    // Create the new table with correct structure
    $sql = "CREATE TABLE IF NOT EXISTS tblreadinghistory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        UserId VARCHAR(100) NOT NULL,
        BookId INT NOT NULL,
        LastPage INT DEFAULT 1,
        LastPosition VARCHAR(255),
        ReadDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        Status ENUM('started', 'in-progress', 'completed') DEFAULT 'started',
        TimeSpent INT DEFAULT 0,
        FOREIGN KEY (UserId) REFERENCES tblusers(UserId) ON DELETE CASCADE,
        FOREIGN KEY (BookId) REFERENCES tblbooks(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book (UserId, BookId)
    )";
    $dbh->exec($sql);
    
    echo "Database schema updated successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
