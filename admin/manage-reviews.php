<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

if(strlen($_SESSION['alogin'])==0) {   
    header('location:index.php');
} else { 
    // Handle review deletion
    if(isset($_GET['del'])) {
        $id = $_GET['del'];
        $sql = "DELETE FROM tblreviews WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
        $_SESSION['delmsg'] = "Review deleted successfully";
        header('location:manage-reviews.php');
        return;
    }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Bookish | Manage Reviews</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- DATATABLE STYLE  -->
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Manage Reviews</h4>
                </div>
            </div>
            <?php if(isset($_SESSION['delmsg'])) { ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?php echo htmlentities($_SESSION['delmsg']); ?>
                <?php unset($_SESSION['delmsg']); ?>
            </div>
            <?php } ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Reviews Listing
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Book</th>
                                            <th>User</th>
                                            <th>Review</th>
                                            <th>Rating</th>
                                            <th>Posted On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        error_reporting(E_ALL);
                                        ini_set('display_errors', 1);

                                        // First check if table exists and has data
                                        try {
                                            $checkSql = "SELECT COUNT(*) as count FROM tblreviews";
                                            $checkQuery = $dbh->prepare($checkSql);
                                            $checkQuery->execute();
                                            $count = $checkQuery->fetch(PDO::FETCH_OBJ)->count;
                                            echo "<!-- Total reviews in database: " . $count . " -->";

                                            // Get a direct look at the reviews table
                                            $rawSql = "SELECT * FROM tblreviews LIMIT 1";
                                            $rawQuery = $dbh->prepare($rawSql);
                                            $rawQuery->execute();
                                            $rawResult = $rawQuery->fetch(PDO::FETCH_OBJ);
                                            echo "<!-- Raw review data: " . print_r($rawResult, true) . " -->";
                                        } catch(PDOException $e) {
                                            echo "<!-- Check error: " . $e->getMessage() . " -->";
                                        }

                                        // Now try the full query
                                        $sql = "SELECT r.*, b.BookName, u.FullName, COALESCE(rt.Rating, 0) as Rating,
                                                r.CreatedAt as ReviewDate
                                                FROM tblreviews r 
                                                LEFT JOIN tblbooks b ON b.id = r.BookId 
                                                LEFT JOIN tblusers u ON u.id = r.UserId 
                                                LEFT JOIN tblratings rt ON rt.BookId = r.BookId AND rt.UserId = r.UserId 
                                                ORDER BY r.CreatedAt DESC";
                                        
                                        try {
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;
                                            
                                            if($query->rowCount() > 0) {
                                                foreach($results as $result) { ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt);?></td>
                                                        <td>
                                                            <a href="../book-details.php?bookid=<?php echo htmlentities($result->BookId);?>">
                                                                <?php echo htmlentities($result->BookName);?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlentities($result->FullName);?></td>
                                                        <td><?php echo htmlentities($result->Review);?></td>
                                                        <td>
                                                            <?php 
                                                            $rating = isset($result->Rating) ? intval($result->Rating) : 0;
                                                            for($i = 1; $i <= 5; $i++) {
                                                                if($i <= $rating) {
                                                                    echo '<i class="fa fa-star text-warning"></i>';
                                                                } else {
                                                                    echo '<i class="fa fa-star-o text-warning"></i>';
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($result->ReviewDate));?></td>
                                                        <td>
                                                            <a href="manage-reviews.php?del=<?php echo htmlentities($result->id);?>" 
                                                               onclick="return confirm('Are you sure you want to delete this review?');" 
                                                               class="btn btn-danger btn-xs">
                                                                <i class="fa fa-trash-o"></i> Delete
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php $cnt++; }
                                            } else { ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No reviews found</td>
                                                </tr>
                                            <?php }
                                        } catch(PDOException $e) { ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-danger">
                                                    Error: <?php echo $e->getMessage(); ?>
                                                </td>
                                            </tr>
                                        <?php }
                                        ?>
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
    <!-- JAVASCRIPT FILES PLACED AT THE BOTTOM TO REDUCE THE LOADING TIME  -->
    <!-- CORE JQUERY  -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- BOOTSTRAP SCRIPTS  -->
    <script src="assets/js/bootstrap.js"></script>
    <!-- DATATABLE SCRIPTS  -->
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <!-- CUSTOM SCRIPTS  -->
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>
