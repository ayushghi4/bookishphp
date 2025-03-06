<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <nav class="navbar navbar-default">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php">
                    <i class="fa fa-book"></i> BOOKISH
                </a>
            </div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li <?php echo ($currentPage == 'index.php') ? 'class="active"' : ''; ?>>
                        <a href="index.php">
                            <i class="fa fa-home"></i> Home
                        </a>
                    </li>
                    <li <?php echo ($currentPage == 'books.php') ? 'class="active"' : ''; ?>>
                        <a href="books.php">
                            <i class="fa fa-book"></i> Books
                        </a>
                    </li>
                    <?php if(isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) { ?>
                        <li <?php echo ($currentPage == 'my-reading.php') ? 'class="active"' : ''; ?>>
                            <a href="my-reading.php">
                                <i class="fa fa-bookmark"></i> My Reading
                            </a>
                        </li>
                        <li <?php echo ($currentPage == 'my-reviews.php') ? 'class="active"' : ''; ?>>
                            <a href="my-reviews.php">
                                <i class="fa fa-comments"></i> My Reviews
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php if(isset($_SESSION['login']) && strlen($_SESSION['login']) > 0) { 
                        // Get user profile info
                        $email = $_SESSION['login'];
                        $sql = "SELECT FullName, ProfileImage FROM tblusers WHERE EmailId=:email";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':email', $email, PDO::PARAM_STR);
                        $query->execute();
                        $userInfo = $query->fetch(PDO::FETCH_OBJ);
                    ?>
                        <li <?php echo ($currentPage == 'my-profile.php') ? 'class="active"' : ''; ?>>
                            <a href="my-profile.php" style="padding: 10px;">
                                <?php if($userInfo && $userInfo->ProfileImage) { ?>
                                    <img src="profile_images/<?php echo htmlentities($userInfo->ProfileImage);?>" 
                                         alt="Profile" class="img-circle" 
                                         style="width: 30px; height: 30px; object-fit: cover; margin-right: 5px;">
                                <?php } else { ?>
                                    <i class="fa fa-user"></i>
                                <?php } ?>
                                <?php echo $userInfo ? htmlentities($userInfo->FullName) : 'My Profile'; ?>
                            </a>
                        </li>
                    <?php } else { ?>
                        <li <?php echo ($currentPage == 'signup.php') ? 'class="active"' : ''; ?>>
                            <a href="signup.php" class="nav-btn nav-btn-primary">
                                <i class="fa fa-user-plus"></i> Sign Up
                            </a>
                        </li>
                        <li <?php echo ($currentPage == 'login.php') ? 'class="active"' : ''; ?>>
                            <a href="login.php" class="nav-btn">
                                <i class="fa fa-sign-in"></i> Login
                            </a>
                        </li>
                        <li <?php echo ($currentPage == 'adminlogin.php') ? 'class="active"' : ''; ?>>
                            <a href="adminlogin.php">
                                <i class="fa fa-lock"></i> Admin
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>
