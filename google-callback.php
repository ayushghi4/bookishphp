<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/google-config.php';

try {
    // Get authorization code
    $code = isset($_GET['code']) ? $_GET['code'] : null;
    
    if($code) {
        // Get access token
        $token = $client->fetchAccessTokenWithAuthCode($code);
        
        if(!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            
            // Get user profile data from Google
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            
            $email = $google_account_info->email;
            $name = $google_account_info->name;
            
            // Check if user exists
            $sql = "SELECT * FROM tblusers WHERE EmailId = :email";
            $query = $dbh->prepare($sql);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->execute();
            
            if($query->rowCount() == 0) {
                // Create new user
                $userId = 'UID' . sprintf('%03d', rand(1, 999));
                $sql = "INSERT INTO tblusers (UserId, FullName, EmailId, Password, Status) 
                        VALUES (:userid, :name, :email, :password, 1)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':userid', $userId, PDO::PARAM_STR);
                $query->bindParam(':name', $name, PDO::PARAM_STR);
                $query->bindParam(':email', $email, PDO::PARAM_STR);
                $randomPass = bin2hex(random_bytes(10)); // Generate random password
                $hashedPass = md5($randomPass);
                $query->bindParam(':password', $hashedPass, PDO::PARAM_STR);
                $query->execute();
                
                // Send welcome email with password (in production, use proper email service)
                // mail($email, "Welcome to Bookish", "Your temporary password is: " . $randomPass);
            }
            
            // Set session and redirect
            $_SESSION['login'] = $email;
            $sql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
            $query = $dbh->prepare($sql);
            $query->bindParam(':email', $email, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_OBJ);
            $_SESSION['userid'] = $result->UserId;
            
            header('Location: index.php');
            exit();
        }
    }
    
    // If we get here, something went wrong
    $_SESSION['error'] = "Google login failed. Please try again.";
    header('Location: login.php');
    exit();
    
} catch(Exception $e) {
    $_SESSION['error'] = "An error occurred. Please try again.";
    error_log("Google Login Error: " . $e->getMessage());
    header('Location: login.php');
    exit();
}
?>
