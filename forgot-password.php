<?php
session_start();
error_reporting(0);
include('includes/config.php');

if(isset($_POST['reset'])) {
    $email = $_POST['emailid'];
    $mobile = $_POST['mobile'];
    $newpassword = md5($_POST['newpassword']);
    
    // Verify email and mobile match
    $sql = "SELECT EmailId, MobileNumber FROM tblusers WHERE EmailId=:email AND MobileNumber=:mobile";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() > 0) {
        // Update password
        $sql = "UPDATE tblusers SET Password=:newpassword WHERE EmailId=:email AND MobileNumber=:mobile";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->bindParam(':mobile', $mobile, PDO::PARAM_STR);
        $query->bindParam(':newpassword', $newpassword, PDO::PARAM_STR);
        $query->execute();
        
        echo "<script>alert('Password successfully changed');</script>";
        echo "<script>window.location.href='login.php'</script>";
    } else {
        echo "<script>alert('Email or Mobile number is invalid');</script>";
    }
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Bookish - Online Book Library | Password Recovery</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>

<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Password Recovery</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            Reset Your Password
                        </div>
                        <div class="panel-body">
                            <form role="form" method="post" onsubmit="return validateForm();">
                                <div class="form-group">
                                    <label>Enter Registered Email</label>
                                    <input class="form-control" type="email" name="emailid" required />
                                </div>
                                <div class="form-group">
                                    <label>Enter Registered Mobile Number</label>
                                    <input class="form-control" type="text" name="mobile" maxlength="10" required />
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input class="form-control" type="password" name="newpassword" id="newpassword" required />
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input class="form-control" type="password" name="confirmpassword" id="confirmpassword" required />
                                </div>
                                <button type="submit" name="reset" class="btn btn-info">Reset Password</button>
                                <a href="login.php" class="btn btn-default">Back to Login</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
    function validateForm() {
        var newpass = document.getElementById("newpassword").value;
        var confpass = document.getElementById("confirmpassword").value;
        
        if(newpass != confpass) {
            alert("New Password and Confirm Password do not match!");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>
