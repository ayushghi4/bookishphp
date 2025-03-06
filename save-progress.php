<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['login'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Not logged in']));
}

// Get user ID from session
if (!isset($_SESSION['userid'])) {
    // Fallback to getting ID from email if not in session
    $email = $_SESSION['login'];
    $userSql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
    $userQuery = $dbh->prepare($userSql);
    $userQuery->bindParam(':email', $email, PDO::PARAM_STR);
    $userQuery->execute();
    $result = $userQuery->fetch(PDO::FETCH_OBJ);
    if (!$result) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'User not found']));
    }
    $userId = $result->UserId;
    $_SESSION['userid'] = $userId;
} else {
    $userId = $_SESSION['userid'];
}

// Get POST data
$bookId = filter_var($_POST['bookid'], FILTER_VALIDATE_INT);
$position = isset($_POST['position']) ? $_POST['position'] : '';

if (!$bookId || !$position) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing required data']));
}

try {
    // Insert new reading history entry
    $sql = "INSERT INTO tblreadinghistory (UserId, BookId, LastPage, ReadDate, Status) 
            VALUES (:userid, :bookid, :lastpage, NOW(), 'reading')";
    $query = $dbh->prepare($sql);
    $query->bindParam(':userid', $userId, PDO::PARAM_INT);
    $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
    $query->bindParam(':lastpage', $position, PDO::PARAM_STR);
    $query->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Reading progress saved successfully'
    ]);
} catch(PDOException $e) {
    error_log("Error saving reading progress: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not save reading progress'
    ]);
}
