<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Check if user is logged in
if (strlen($_SESSION['login']) == 0) {
    http_response_code(401);
    die("Unauthorized");
}

// Get user's email (which we use as UserId)
$userId = $_SESSION['login'];

// Validate input
$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$position = isset($_POST['position']) ? $_POST['position'] : null;
$time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;

try {
    // Begin transaction
    $dbh->beginTransaction();

    // Update reading history
    $sql = "UPDATE tblreadinghistory 
            SET LastPage = :page,
                LastPosition = :position,
                TimeSpent = TimeSpent + :time_spent,
                Status = 'in-progress',
                ReadDate = CURRENT_TIMESTAMP
            WHERE UserId = :userid AND BookId = :bookid";

    $query = $dbh->prepare($sql);
    $query->bindParam(':page', $page, PDO::PARAM_INT);
    $query->bindParam(':position', $position, PDO::PARAM_STR);
    $query->bindParam(':time_spent', $time_spent, PDO::PARAM_INT);
    $query->bindParam(':userid', $userId, PDO::PARAM_STR);
    $query->bindParam(':bookid', $book_id, PDO::PARAM_INT);
    $query->execute();

    // If no rows were updated, insert a new record
    if ($query->rowCount() == 0) {
        $sql = "INSERT INTO tblreadinghistory (UserId, BookId, LastPage, LastPosition, TimeSpent, Status) 
                VALUES (:userid, :bookid, :page, :position, :time_spent, 'in-progress')";
        $query = $dbh->prepare($sql);
        $query->bindParam(':userid', $userId, PDO::PARAM_STR);
        $query->bindParam(':bookid', $book_id, PDO::PARAM_INT);
        $query->bindParam(':page', $page, PDO::PARAM_INT);
        $query->bindParam(':position', $position, PDO::PARAM_STR);
        $query->bindParam(':time_spent', $time_spent, PDO::PARAM_INT);
        $query->execute();
    }

    // If this is the last page, mark as completed
    $sql = "SELECT COUNT(*) as total_pages FROM tblbooks WHERE id = :bookid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $book_id, PDO::PARAM_INT);
    $query->execute();
    $total_pages = $query->fetch(PDO::FETCH_OBJ)->total_pages;

    if ($page >= $total_pages) {
        $sql = "UPDATE tblreadinghistory 
                SET Status = 'completed'
                WHERE UserId = :userid AND BookId = :bookid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':userid', $userId, PDO::PARAM_STR);
        $query->bindParam(':bookid', $book_id, PDO::PARAM_INT);
        $query->execute();
    }

    // Commit transaction
    $dbh->commit();
    
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $dbh->rollBack();
    error_log("Error updating reading history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
