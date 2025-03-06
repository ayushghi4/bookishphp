<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (strlen($_SESSION['login']) == 0) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get POST data
$bookId = isset($_POST['bookid']) ? intval($_POST['bookid']) : 0;
$cfi = isset($_POST['cfi']) ? trim($_POST['cfi']) : '';
$readerId = $_SESSION['userid'];

if ($bookId == 0 || empty($cfi)) {
    echo json_encode(['success' => false, 'message' => 'Invalid bookmark data']);
    exit();
}

try {
    // First check if a bookmark already exists
    $sql = "SELECT id FROM tblbookmarks WHERE BookId = :bookid AND ReaderId = :readerid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
    $query->bindParam(':readerid', $readerId, PDO::PARAM_STR);
    $query->execute();
    
    if ($query->rowCount() > 0) {
        // Update existing bookmark
        $sql = "UPDATE tblbookmarks SET cfi = :cfi, updated_at = CURRENT_TIMESTAMP 
                WHERE BookId = :bookid AND ReaderId = :readerid";
    } else {
        // Create new bookmark
        $sql = "INSERT INTO tblbookmarks (BookId, ReaderId, cfi, label, created_at) 
                VALUES (:bookid, :readerid, :cfi, '', CURRENT_TIMESTAMP)";
    }
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
    $query->bindParam(':readerid', $readerId, PDO::PARAM_STR);
    $query->bindParam(':cfi', $cfi, PDO::PARAM_STR);
    $query->execute();
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Error saving bookmark: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
