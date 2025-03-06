<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Check if admin is logged in
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        $dbh->beginTransaction();

        // Delete the user
        $id = intval($_GET['id']);
        $stmt = $dbh->prepare("DELETE FROM tblusers WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $dbh->commit();
            $_SESSION['msg'] = "User deleted successfully";
            header('location:reg-users.php');
            exit();
        } else {
            $dbh->rollBack();
            $_SESSION['error'] = "Error deleting user";
            header('location:reg-users.php');
            exit();
        }
    } catch (PDOException $e) {
        $dbh->rollBack();
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('location:reg-users.php');
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request";
    header('location:reg-users.php');
    exit();
}
?>