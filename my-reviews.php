<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/XAMPP/xamppfiles/logs/php_error.log');
include('includes/config.php');

error_log("\n\n=== MY REVIEWS PAGE ===\n");
error_log("Session data: " . print_r($_SESSION, true));
    
if(strlen($_SESSION['login'])==0) {   
    header('location:index.php');
} else {
    error_log("\n=== Getting User ID ===\n");
    error_log("Looking up user with email: " . $_SESSION['login']);
    
    // Get user's ID from tblusers
    $sql = "SELECT id FROM tblusers WHERE EmailId = :emailid";
    error_log("SQL: " . $sql);
    $query = $dbh->prepare($sql);
    $query->bindParam(':emailid', $_SESSION['login'], PDO::PARAM_STR);
    $query->execute();
    
    if ($query->rowCount() == 0) {
        $_SESSION['error'] = "User account not found";
        header('location:logout.php');
        exit();
    }
    
    $userId = $query->fetch(PDO::FETCH_OBJ)->id;
    error_log("Found user ID: " . $userId);
    
    // Handle review deletion
    if(isset($_GET['del'])) {
        $reviewId = $_GET['del'];
        try {
            // Start transaction
            $dbh->beginTransaction();
            
            // Delete review
            $sql = "DELETE FROM tblreviews WHERE id=:reviewid AND UserId=:userid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':reviewid', $reviewId, PDO::PARAM_INT);
            $query->bindParam(':userid', $userId, PDO::PARAM_INT);
            $query->execute();
            
            // Delete associated rating
            $sql = "DELETE FROM tblratings WHERE BookId IN (SELECT BookId FROM tblreviews WHERE id=:reviewid) AND UserId=:userid";
            $query = $dbh->prepare($sql);
            $query->bindParam(':reviewid', $reviewId, PDO::PARAM_INT);
            $query->bindParam(':userid', $userId, PDO::PARAM_INT);
            $query->execute();
            
            $dbh->commit();
            $_SESSION['msg'] = "Review deleted successfully";
        } catch (Exception $e) {
            $dbh->rollBack();
            $_SESSION['error'] = "Unable to delete review: " . $e->getMessage();
        }
        header('location:my-reviews.php');
        exit();
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Bookish | My Reviews</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .fa-star { color: #ffc107; }
        .fa-star-o { color: #ccc; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-sm i { margin-right: 5px; }
        .table td { vertical-align: middle; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; border-color: #bd2130; }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">My Reviews</h4>
                </div>
            </div>
            <?php if(isset($_SESSION['msg'])) { ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo htmlentities($_SESSION['msg']); ?>
                <?php unset($_SESSION['msg']);?>
            </div>
            <?php } ?>
            <?php if(isset($_SESSION['error'])) { ?>
            <div class="alert alert-danger">
                <strong>Error!</strong> <?php echo htmlentities($_SESSION['error']); ?>
                <?php unset($_SESSION['error']);?>
            </div>
            <?php } ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            My Book Reviews
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Book</th>
                                            <th>Review</th>
                                            <th>Rating</th>
                                            <th>Posted On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Get user's reviews with book details and ratings
                                        error_log("Fetching reviews for user ID: " . $userId);
                                        error_log("\n=== Checking Reviews ===\n");
                                        
                                        $sql = "SELECT r.id as reviewId, 
                                                      b.BookName, 
                                                      r.Review, 
                                                      r.CreatedAt as PostedOn,
                                                      COALESCE(rt.Rating, 0) as Rating
                                               FROM tblreviews r
                                               JOIN tblbooks b ON b.id = r.BookId
                                               LEFT JOIN tblratings rt ON rt.BookId = r.BookId AND rt.UserId = r.UserId
                                               WHERE r.UserId = :userid
                                               ORDER BY r.CreatedAt DESC";
                                        
                                        error_log("Review SQL: " . $sql);
                                        
                                        error_log("Executing review query for user ID: " . $userId);
                                        $query = $dbh->prepare($sql);
                                        $query->bindParam(':userid', $userId, PDO::PARAM_INT);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        error_log("Found " . $query->rowCount() . " reviews");
                                        error_log("Results: " . print_r($results, true));
                                        
                                        if($query->rowCount() > 0) {
                                            $cnt = 1;
                                            foreach($results as $result) { ?>
                                                <tr>
                                                    <td><?php echo htmlentities($cnt);?></td>
                                                    <td><?php echo htmlentities($result->BookName);?></td>
                                                    <td><?php echo htmlentities($result->Review);?></td>
                                                    <td>
                                                        <?php 
                                                        $rating = intval($result->Rating);
                                                        for($i = 1; $i <= 5; $i++) {
                                                            if($i <= $rating) {
                                                                echo '<i class="fa fa-star"></i>';
                                                            } else {
                                                                echo '<i class="fa fa-star-o"></i>';
                                                            }
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($result->PostedOn));?></td>
                                                    <td>
                                                        <a href="my-reviews.php?del=<?php echo htmlentities($result->reviewId);?>" 
                                                           class="btn btn-danger btn-sm" 
                                                           onclick="return confirm('Are you sure you want to delete this review?');">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php $cnt++;
                                            }
                                        } else { ?>
                                            <tr>
                                                <td colspan="6" style="text-align: center;">No reviews found</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
</body>
</html>
<?php } ?>
