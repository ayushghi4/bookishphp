<?php 
session_start();
include('includes/config.php');
error_reporting(0);
if(strlen($_SESSION['login'])==0)
    {   
header('location:index.php');
}
else{ 
if(isset($_POST['update']))
{    
$email = $_SESSION['login'];
$fname=$_POST['fullname'];
$mobileno=$_POST['mobileno'];

// Handle profile image upload
$profile_image = '';
if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(in_array($ext, $allowed)) {
        // Check file size (5MB max)
        if($_FILES['profile_image']['size'] <= 5242880) {
            $new_filename = uniqid('profile_') . '.' . $ext;
            $target_path = 'profile_images/' . $new_filename;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                error_log("Profile image uploaded successfully to: " . $target_path);
                $profile_image = $new_filename;
            } else {
                error_log("Failed to move uploaded file. Error: " . error_get_last()['message']);
                $_SESSION['error'] = "Failed to upload profile picture. Please try again.";
            }
        } else {
            $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Please upload JPG, PNG or GIF only.";
    }
}

try {
    // Update user profile
    $sql = "UPDATE tblusers SET FullName=:fname, MobileNumber=:mobileno" . 
           ($profile_image ? ", ProfileImage=:profile_image" : "") . 
           " WHERE EmailId=:email";
           
    $query = $dbh->prepare($sql);
    $query->bindParam(':fname', $fname, PDO::PARAM_STR);
    $query->bindParam(':mobileno', $mobileno, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    
    if($profile_image) {
        $query->bindParam(':profile_image', $profile_image, PDO::PARAM_STR);
    }
    
    $query->execute();
    
    if($query->rowCount() > 0) {
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "No changes were made to your profile.";
    }
    
} catch(PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to update profile. Please try again.";
}

// Redirect to refresh the page
header('Location: my-profile.php');
exit();

}

if(isset($_POST['change']))
{
    $password=md5($_POST['currentpassword']);
    $newpassword=md5($_POST['newpassword']);
    $email=$_SESSION['login'];

    // First verify the current password
    $sql ="SELECT Password FROM tblusers WHERE EmailId=:email";
    $query= $dbh -> prepare($sql);
    $query-> bindParam(':email', $email, PDO::PARAM_STR);
    $query-> execute();
    $result = $query->fetch(PDO::FETCH_OBJ);

    if($result && $result->Password === $password)
    {
        // Update to new password
        $con="UPDATE tblusers SET Password=:newpassword WHERE EmailId=:email";
        $chngpwd1 = $dbh->prepare($con);
        $chngpwd1-> bindParam(':email', $email, PDO::PARAM_STR);
        $chngpwd1-> bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $chngpwd1->execute();
        echo '<script>alert("Your password has been updated successfully")</script>';
    }
    else {
        echo '<script>alert("Your current password is incorrect")</script>';
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <!--[if IE]>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <![endif]-->
    <title>Bookish - Online Book Library | My Profile</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' /> 

</head>
<body>
    <!------MENU SECTION START-->
<?php include('includes/header.php');?>
<!-- MENU SECTION END-->
    <div class="content-wrapper">
         <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">My Profile</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h4><i class="fa fa-user"></i> Profile Information</h4>
                        </div>
                        <div class="panel-body">
                            <form name="signup" method="post" enctype="multipart/form-data">
                            <?php 
                            $email = $_SESSION['login'];
                            $sql = "SELECT UserId, FullName, EmailId, MobileNumber, RegDate, UpdationDate, Status 
                                   FROM tblusers 
                                   WHERE EmailId=:email";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':email', $email, PDO::PARAM_STR);
                            $query->execute();
                            $result = $query->fetch(PDO::FETCH_OBJ);
                            if($result) {
                            ?>
                                <div class="form-group">
                                    <label>User ID : </label>
                                    <?php echo htmlentities($result->UserId);?>
                                </div>

                                <div class="form-group">
                                    <label>Registration Date : </label>
                                    <?php echo htmlentities($result->RegDate);?>
                                </div>

                                <div class="form-group">
                                    <label>Last Updated : </label>
                                    <?php echo $result->UpdationDate ? htmlentities($result->UpdationDate) : 'Not updated yet';?>
                                </div>

                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input class="form-control" type="text" name="fullname" value="<?php echo htmlentities($result->FullName);?>" autocomplete="off" required />
                                </div>

                                <div class="form-group">
                                    <label>Mobile Number</label>
                                    <input class="form-control" type="text" name="mobileno" maxlength="10" value="<?php echo htmlentities($result->MobileNumber);?>" autocomplete="off" required />
                                </div>

                                <div class="form-group">
                                    <label>Profile Picture</label>
                                    <?php 
                                    // Get profile image
                                    $sql = "SELECT ProfileImage FROM tblusers WHERE EmailId=:email";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':email', $email, PDO::PARAM_STR);
                                    $query->execute();
                                    $profileResult = $query->fetch(PDO::FETCH_OBJ);
                                    
                                    if($profileResult && $profileResult->ProfileImage) { ?>
                                        <div class="current-profile-pic">
                                            <img src="profile_images/<?php echo htmlentities($profileResult->ProfileImage);?>" 
                                                 alt="Current Profile Picture" class="img-circle" 
                                                 style="width: 150px; height: 150px; object-fit: cover;">
                                        </div>
                                    <?php } ?>
                                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                                    <span class="help-block">Upload a profile picture (JPG, PNG or GIF). Maximum size: 5MB</span>
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" type="email" name="email" value="<?php echo htmlentities($result->EmailId);?>" readonly />
                                    <span class="help-block">Email cannot be changed.</span>
                                </div>

                                <button type="submit" name="update" class="btn btn-primary" id="submit">
                                    <i class="fa fa-save"></i> Update Profile
                                </button>

                                <a href="logout.php" class="btn btn-danger pull-right">
                                    <i class="fa fa-sign-out"></i> Logout
                                </a>
                            </form>
                            <hr>

                                <h4><i class="fa fa-key"></i> Change Password</h4>

                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input class="form-control" type="password" name="currentpassword" required />
                                </div>

                                <div class="form-group">
                                    <label>New Password</label>
                                    <input class="form-control" type="password" name="newpassword" id="newpassword" required />
                                </div>

                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input class="form-control" type="password" name="confirmpassword" id="confirmpassword" required />
                                </div>

                                <button type="submit" name="change" class="btn btn-info btn-block">
                                    <i class="fa fa-lock"></i> Change Password
                                </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
     <!-- CONTENT-WRAPPER SECTION END-->
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS  -->
    <script src="assets/js/bootstrap.js"></script>
      <!-- CUSTOM SCRIPTS  -->
    <script src="assets/js/custom.js"></script>
    <script type="text/javascript">
function validatePassword() {
    var newpass = document.getElementById("newpassword").value;
    var confpass = document.getElementById("confirmpassword").value;
    if(newpass != confpass) {
        alert("New Password and Confirm Password do not match!");
        return false;
    }
    return true;
}
</script>
<style>
.panel-info > .panel-heading {
    color: #fff;
    background-color: #337ab7;
    border-color: #337ab7;
}
.panel-heading h4 {
    margin: 0;
}
.form-control-static {
    padding-top: 7px;
    margin-bottom: 0;
    background: #f9f9f9;
    padding: 8px 12px;
    border-radius: 4px;
}
.label {
    display: inline-block;
    padding: 5px 10px;
    font-size: 12px;
    border-radius: 3px;
}
.label-success {
    background-color: #5cb85c;
}
.label-danger {
    background-color: #d9534f;
}
hr {
    margin: 30px 0;
    border-color: #eee;
}
.btn {
    padding: 8px 16px;
    font-weight: 600;
    margin-bottom: 15px;
}
.btn i {
    margin-right: 5px;
}
</style>
</body>
</html>
<?php 
    }
} 
?>