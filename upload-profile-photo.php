<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Check if user is logged in
if(strlen($_SESSION['login']) == 0) {
    die(json_encode(['success' => false, 'error' => 'Not logged in']));
}

if(!isset($_FILES['profile_image'])) {
    die(json_encode(['success' => false, 'error' => 'No file uploaded']));
}

$file = $_FILES['profile_image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];
$fileType = $file['type'];

// Validate file
if($fileError !== 0) {
    die(json_encode(['success' => false, 'error' => 'Error uploading file']));
}

// Check file size (2MB max)
if($fileSize > 2 * 1024 * 1024) {
    die(json_encode(['success' => false, 'error' => 'File too large. Maximum size is 2MB']));
}

// Check file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
if(!in_array($fileType, $allowedTypes)) {
    die(json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, JPEG and PNG allowed']));
}

// Create unique filename
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$newFileName = uniqid('profile_', true) . '.' . $fileExt;
$uploadPath = 'profileimg/' . $newFileName;

// Create directory if it doesn't exist
if(!file_exists('profileimg')) {
    mkdir('profileimg', 0777, true);
}

// Move file to upload directory
if(!move_uploaded_file($fileTmpName, $uploadPath)) {
    die(json_encode(['success' => false, 'error' => 'Error moving uploaded file']));
}

// Update database
$email = $_SESSION['login'];
$sql = "UPDATE tblusers SET ProfileImage = :image WHERE EmailId = :email";
$query = $dbh->prepare($sql);
$query->bindParam(':image', $newFileName, PDO::PARAM_STR);
$query->bindParam(':email', $email, PDO::PARAM_STR);

if($query->execute()) {
    // Delete old profile image if it exists
    $sql = "SELECT ProfileImage FROM tblusers WHERE EmailId = :email";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();
    $oldImage = $query->fetch(PDO::FETCH_OBJ)->ProfileImage;
    
    if($oldImage && $oldImage != $newFileName && file_exists('profileimg/' . $oldImage)) {
        unlink('profileimg/' . $oldImage);
    }
    
    echo json_encode(['success' => true, 'filename' => $newFileName]);
} else {
    // Delete uploaded file if database update fails
    unlink($uploadPath);
    echo json_encode(['success' => false, 'error' => 'Error updating database']);
}
?>
