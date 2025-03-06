<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/logs/php_error.log');
include('../includes/config.php');

// Debug log
error_log("\n\n=== NEW REVIEW SUBMISSION ===\n");
error_log("Review submission started at: " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if tables exist
try {
    $tables = ['tblreviews', 'tblratings', 'tblbooks', 'tblusers'];
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $query = $dbh->query($sql);
        error_log("Table $table exists: " . ($query->rowCount() > 0 ? 'Yes' : 'No'));
        
        if ($query->rowCount() > 0) {
            $sql = "DESCRIBE $table";
            $query = $dbh->query($sql);
            $columns = $query->fetchAll(PDO::FETCH_ASSOC);
            error_log("$table columns: " . print_r($columns, true));
        }
    }
} catch (Exception $e) {
    error_log("Error checking tables: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['login'])) {
    error_log("User not logged in");
    $_SESSION['error'] = "Please login to submit a review";
    header('Location: ../login.php');
    exit();
}

// Get the data from POST
$bookId = isset($_POST['bookId']) ? intval($_POST['bookId']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

error_log("Extracted data - BookId: $bookId, Rating: $rating, Review: $review");

if ($bookId <= 0 || $rating <= 0 || empty($review)) {
    error_log("Missing or invalid required fields");
    $_SESSION['error'] = "Please provide all required information";
    header('Location: ../book-details.php?bookid=' . $bookId);
    exit();
}

try {
    error_log("\n=== Starting Review Process ===\n");
    error_log("Database connection status: " . ($dbh ? 'Connected' : 'Not connected'));
    
    // Get the user's ID from tblusers
    error_log("Getting user ID for email: " . $_SESSION['login']);
    $sql = "SELECT id FROM tblusers WHERE EmailId = :emailid";
    error_log("SQL Query: " . $sql);
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':emailid', $_SESSION['login'], PDO::PARAM_STR);
    $query->execute();
    
    if ($query->rowCount() == 0) {
        error_log("User not found: " . $_SESSION['login']);
        $_SESSION['error'] = "User account not found";
        header('Location: ../logout.php');
        exit();
    }
    
    $userId = $query->fetch(PDO::FETCH_OBJ)->id;
    error_log("Found user ID: " . $userId);
    
    // Start transaction
    $dbh->beginTransaction();
    error_log("Starting transaction with userId: " . $userId . ", bookId: " . $bookId);
    
    try {
        // Check if book exists
        $sql = "SELECT id FROM tblbooks WHERE id = :bookid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
        $query->execute();
        
        if ($query->rowCount() == 0) {
            throw new Exception("Book not found");
        }
        
        // Insert or update review
        error_log("\n=== Handling Review ===\n");
        error_log("Parameters - BookId: $bookId, UserId: $userId, Review length: " . strlen($review));
        
        // Check if review already exists
        $sql = "SELECT id FROM tblreviews WHERE BookId = :bookid AND UserId = :userid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
        $query->bindParam(':userid', $userId, PDO::PARAM_INT);
        $query->execute();
        
        if ($query->rowCount() > 0) {
            // Update existing review
            $sql = "UPDATE tblreviews SET Review = :review, Status = 1, CreatedAt = NOW() WHERE BookId = :bookid AND UserId = :userid";
        } else {
            // Insert new review
            $sql = "INSERT INTO tblreviews (BookId, UserId, Review, CreatedAt, Status) VALUES (:bookid, :userid, :review, NOW(), 1)";
        }
        error_log("Review SQL: " . $sql);
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
        $query->bindParam(':userid', $userId, PDO::PARAM_INT);
        $query->bindParam(':review', $review, PDO::PARAM_STR);
        $query->execute();
        error_log("Review saved successfully");
        
        // Handle rating using REPLACE INTO
        error_log("Handling rating - BookId: $bookId, UserId: $userId, Rating: $rating");
        $sql = "REPLACE INTO tblratings (BookId, UserId, Rating, CreatedAt) VALUES (:bookid, :userid, :rating, NOW())";
        error_log("Rating SQL: " . $sql);
        
        $query = $dbh->prepare($sql);
        $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
        $query->bindParam(':userid', $userId, PDO::PARAM_INT);
        $query->bindParam(':rating', $rating, PDO::PARAM_INT);
        $query->execute();
        error_log("Rating saved successfully");
        
        $dbh->commit();
        error_log("Transaction committed successfully");
        
        $_SESSION['success'] = "Your review has been submitted successfully!";
        header('Location: ../book-details.php?bookid=' . $bookId);
        exit();
        
    } catch (Exception $e) {
        $dbh->rollBack();
        error_log("Error during transaction: " . $e->getMessage());
        $_SESSION['error'] = "Failed to submit review: " . $e->getMessage();
        header('Location: ../book-details.php?bookid=' . $bookId);
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while submitting your review";
    header('Location: ../book-details.php?bookid=' . $bookId);
    exit();
}
?>
