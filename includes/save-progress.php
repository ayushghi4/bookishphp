<?php
session_start();
error_reporting(0);
include('includes/config.php');

if(strlen($_SESSION['login'])==0) { 
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if(!isset($_POST['bookid']) || !isset($_POST['location']) || !isset($_POST['progress'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$bookid = $_POST['bookid'];
$location = $_POST['location'];
$progress = $_POST['progress'];
$userid = $_SESSION['userid'];

try {
    // First try to update existing record
    $sql = "INSERT INTO tblreadingprogress (BookId, UserId, CurrentLocation, Progress) 
            VALUES (:bookid, :userid, :location, :progress)
            ON DUPLICATE KEY UPDATE 
            CurrentLocation = VALUES(CurrentLocation),
            Progress = VALUES(Progress),
            LastReadTime = CURRENT_TIMESTAMP";
            
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $bookid, PDO::PARAM_STR);
    $query->bindParam(':userid', $userid, PDO::PARAM_STR);
    $query->bindParam(':location', $location, PDO::PARAM_STR);
    $query->bindParam(':progress', $progress, PDO::PARAM_STR);
    $query->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Progress saved']);
} catch(Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
