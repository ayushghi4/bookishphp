<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    // Get the user ID from URL
    $userid = isset($_GET['userid']) ? $_GET['userid'] : '';

    if (empty($userid)) {
        $_SESSION['error'] = "Invalid User ID";
        header('location:reg-users.php');
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Bookish - Online Book Library | User History</title>
        <!-- BOOTSTRAP CORE STYLE  -->
        <link href="assets/css/bootstrap.css" rel="stylesheet" />
        <!-- FONT AWESOME STYLE  -->
        <link href="assets/css/font-awesome.css" rel="stylesheet" />
        <!-- DATATABLE STYLE  -->
        <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
        <!-- CUSTOM STYLE  -->
        <link href="assets/css/style.css" rel="stylesheet" />
        <!-- GOOGLE FONT -->
        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
        <style>
            .star-rating {
                color: #ffd700;
            }
        </style>
    </head>

    <body>
        <!------MENU SECTION START-->
        <?php include('includes/header.php'); ?>
        <!-- MENU SECTION END-->
        <div class="content-wrapper">
            <div class="container">
                <div class="row pad-botm">
                    <div class="col-md-12">
                        <h4 class="header-line">User History</h4>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <!-- User Info -->
                        <div class="panel panel-default">
                            <div class="panel-heading">User Information</div>
                            <div class="panel-body">
                                <?php
                                $sql = "SELECT * FROM tblusers WHERE UserId = :userid";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':userid', $userid, PDO::PARAM_STR);
                                $query->execute();
                                $result = $query->fetch(PDO::FETCH_OBJ);
                                if ($query->rowCount() > 0) {
                                    ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>User ID:</strong> <?php echo htmlentities($result->UserId); ?></p>
                                            <p><strong>Full Name:</strong> <?php echo htmlentities($result->FullName); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlentities($result->EmailId); ?></p>
                                            <p><strong>Mobile Number:</strong>
                                                <?php echo htmlentities($result->MobileNumber); ?></p>
                                            <p><strong>Registration Date:</strong> <?php echo htmlentities($result->RegDate); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Books History -->
                        <div class="panel panel-default">
                            <div class="panel-heading">Book Usage History</div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="book-history">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Book Name</th>
                                                <th>ISBN</th>
                                                <th>Read Date</th>
                                                <th>Rating</th>
                                                <th>Review</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // First get the user's numeric ID
                                            $userSql = "SELECT id FROM tblusers WHERE UserId = :userid";
                                            $userQuery = $dbh->prepare($userSql);
                                            $userQuery->bindParam(':userid', $userid, PDO::PARAM_STR);
                                            $userQuery->execute();
                                            $userId = $userQuery->fetch(PDO::FETCH_OBJ)->id;

                                            // Now use the numeric ID for the main query
                                            $sql = "SELECT b.BookName, b.ISBNNumber, 
                                                    rh.ReadDate, rh.LastPage, rh.Status,
                                                    rt.Rating,
                                                    rv.Review
                                                    FROM tblreadinghistory rh
                                                    JOIN tblbooks b ON b.id = rh.BookId
                                                    LEFT JOIN tblreviews rv ON rv.BookId = rh.BookId AND rv.UserId = :numericId
                                                    LEFT JOIN tblratings rt ON rt.BookId = rh.BookId AND rt.UserId = :numericId
                                                    WHERE rh.UserId = :userid
                                                    ORDER BY rh.ReadDate DESC";

                                            error_log("User numeric ID: " . $userId);
                                            error_log("SQL Query: " . $sql);

                                            $query = $dbh->prepare($sql);
                                            $query->bindParam(':userid', $userid, PDO::PARAM_STR);
                                            $query->bindParam(':numericId', $userId, PDO::PARAM_INT);
                                            $query->execute();
                                            error_log("Query executed with userid: " . $userid . " and numericId: " . $userId);
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt = 1;

                                            if($query->rowCount() > 0) {
                                                error_log("Found " . $query->rowCount() . " results");
                                                foreach($results as $result) { ?>
                                                    <tr>
                                                        <td><?php echo htmlentities($cnt);?></td>
                                                        <td><?php echo htmlentities($result->BookName);?></td>
                                                        <td><?php echo htmlentities($result->ISBNNumber);?></td>
                                                        <td><?php echo date('Y-m-d H:i:s', strtotime($result->ReadDate));?></td>
                                                        <td>
                                                            <?php if($result->Rating) { ?>
                                                                <div class="star-rating">
                                                                    <?php
                                                                    for($i = 1; $i <= 5; $i++) {
                                                                        if($i <= $result->Rating) {
                                                                            echo '<i class="fa fa-star"></i>';
                                                                        } else {
                                                                            echo '<i class="fa fa-star-o"></i>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </div>
                                                            <?php } else {
                                                                echo "Not rated";
                                                            } ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if($result->Review) {
                                                                echo htmlentities($result->Review);
                                                            } else {
                                                                echo "No review";
                                                            }
                                                            ?>
                                                            <br>
                                                            <small>
                                                                Status: <?php echo ucfirst($result->Status); ?><br>
                                                                Last Page: <?php echo $result->LastPage; ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                    <?php $cnt++;
                                                }
                                            } else {
                                                error_log("No results found for user");
                                            ?>

                                                <tr>
                                                    <td colspan="6">No reading history found</td>
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
        <!-- CONTENT-WRAPPER SECTION END-->
        <?php include('includes/footer.php'); ?>
        <!-- FOOTER SECTION END-->
        <!-- JAVASCRIPT FILES PLACED AT THE BOTTOM TO REDUCE THE LOADING TIME  -->
        <!-- CORE JQUERY  -->
        <script src="assets/js/jquery-1.10.2.js"></script>
        <!-- BOOTSTRAP SCRIPTS  -->
        <script src="assets/js/bootstrap.js"></script>
        <!-- DATATABLE SCRIPTS  -->
        <script src="assets/js/dataTables/jquery.dataTables.js"></script>
        <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
        <script>
            $(document).ready(function () {
                // Disable DataTables warning messages
                $.fn.dataTable.ext.errMode = 'none';

                var table = $('#book-history').DataTable({
                    "bSort": true,
                    "pageLength": 10,
                    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                    "order": [[3, "desc"]], // Sort by read date
                    "columnDefs": [{
                        "targets": 0,
                        "orderable": false
                    }]
                });
            });
        </script>
    </body>

    </html>
<?php } ?>