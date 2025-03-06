<?php
session_start();
error_reporting(0);
include('../includes/config.php');
if(strlen($_SESSION['login'])==0) {   
    header('location:../index.php');
} else { 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookish | User Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('includes/header.php');?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Books Read</p>
                                    <?php 
                                    $sid=$_SESSION['login'];
                                    $sql1 ="SELECT * from tblbookhistory where UserId=:sid";
                                    $query1 = $dbh -> prepare($sql1);
                                    $query1->bindParam(':sid',$sid,PDO::PARAM_STR);
                                    $query1->execute();
                                    $results1=$query1->fetchAll(PDO::FETCH_OBJ);
                                    $readbooks=$query1->rowCount();
                                    ?>
                                    <h5 class="font-weight-bolder mb-0"><?php echo htmlentities($readbooks);?></h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fas fa-book text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Reviews Written</p>
                                    <?php 
                                    $sql2 ="SELECT * from tblbookhistory where UserId=:sid AND Review IS NOT NULL";
                                    $query2 = $dbh -> prepare($sql2);
                                    $query2->bindParam(':sid',$sid,PDO::PARAM_STR);
                                    $query2->execute();
                                    $reviews=$query2->rowCount();
                                    ?>
                                    <h5 class="font-weight-bolder mb-0"><?php echo htmlentities($reviews);?></h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                    <i class="fas fa-star text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recently Read Books -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <h6>Recently Read Books</h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Book</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Author</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Last Read</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sql = "SELECT b.BookName, a.AuthorName, h.ViewDate, h.Rating 
                                            FROM tblbooks b 
                                            JOIN tblauthors a ON b.AuthorId = a.id 
                                            JOIN tblbookhistory h ON b.id = h.BookId 
                                            WHERE h.UserId=:sid 
                                            ORDER BY h.ViewDate DESC LIMIT 5";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':sid',$sid,PDO::PARAM_STR);
                                    $query->execute();
                                    $results=$query->fetchAll(PDO::FETCH_OBJ);
                                    if($query->rowCount() > 0) {
                                        foreach($results as $result) { ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?php echo htmlentities($result->BookName);?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?php echo htmlentities($result->AuthorName);?></p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <p class="text-xs font-weight-bold mb-0"><?php echo htmlentities($result->ViewDate);?></p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?php 
                                                    for($i = 1; $i <= 5; $i++) {
                                                        if($i <= $result->Rating) {
                                                            echo '<i class="fas fa-star text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-warning"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php }} ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php');?>
    
    <!-- Bootstrap core JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts -->
    <script src="../assets/js/scripts.js"></script>
</body>
</html>
<?php } ?>
