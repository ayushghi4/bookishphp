<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

if(strlen($_SESSION['alogin'])==0) {   
    header('location:index.php');
} else { 
    // Get books count
    $sql = "SELECT COUNT(id) as count FROM tblbooks";
    $query = $dbh->prepare($sql);
    $query->execute();
    $result = $query->fetch(PDO::FETCH_OBJ);
    $listdbooks = $result->count;

    // Get users count
    $sql1 = "SELECT COUNT(id) as count FROM tblusers";
    $query1 = $dbh->prepare($sql1);
    $query1->execute();
    $result1 = $query1->fetch(PDO::FETCH_OBJ);
    $regusers = $result1->count;

    // Get categories count
    $sql2 = "SELECT COUNT(id) as count FROM tblcategory";
    $query2 = $dbh->prepare($sql2);
    $query2->execute();
    $result2 = $query2->fetch(PDO::FETCH_OBJ);
    $listdcats = $result2->count;

    // Get reviews count
    $sql3 = "SELECT COUNT(id) as count FROM tblreviews WHERE Status = 1";
    $query3 = $dbh->prepare($sql3);
    $query3->execute();
    $result3 = $query3->fetch(PDO::FETCH_OBJ);
    $reviews = $result3->count;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Bookish | Admin Dashboard</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- DATATABLE SCRIPTS  -->
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">ADMIN DASHBOARD</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-3 col-xs-6">
                    <a href="manage-books.php" class="text-decoration-none">
                        <div class="alert alert-success back-widget-set text-center">
                            <i class="fa fa-book fa-4x"></i>
                            <h3><?php echo htmlentities($listdbooks);?></h3>
                            <p class="widget-label">Books Listed</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-6">
                    <a href="reg-users.php" class="text-decoration-none">
                        <div class="alert alert-info back-widget-set text-center">
                            <i class="fa fa-users fa-4x"></i>
                            <h3><?php echo htmlentities($regusers);?></h3>
                            <p class="widget-label">Registered Users</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-6">
                    <a href="manage-categories.php" class="text-decoration-none">
                        <div class="alert alert-warning back-widget-set text-center">
                            <i class="fa fa-list fa-4x"></i>
                            <h3><?php echo htmlentities($listdcats);?></h3>
                            <p class="widget-label">Listed Categories</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-6">
                    <a href="manage-reviews.php" class="text-decoration-none">
                        <div class="alert alert-danger back-widget-set text-center">
                            <i class="fa fa-comments fa-4x"></i>
                            <h3><?php echo htmlentities($reviews);?></h3>
                            <p class="widget-label">Book Reviews</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <!-- JAVASCRIPT FILES PLACED AT THE BOTTOM TO REDUCE THE LOADING TIME  -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
</body>
</html>
<?php } ?>