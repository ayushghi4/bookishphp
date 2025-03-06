<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
} else {
    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Online Library Management System | Listed Books</title>
        <!-- BOOTSTRAP CORE STYLE  -->
        <link href="assets/css/bootstrap.css" rel="stylesheet" />
        <!-- FONT AWESOME STYLE  -->
        <link href="assets/css/font-awesome.css" rel="stylesheet" />
        <!-- CUSTOM STYLE  -->
        <link href="assets/css/style.css" rel="stylesheet" />
        <!-- GOOGLE FONT -->
        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
        <!-- EPUB.js -->
        <script src="https://cdn.jsdelivr.net/npm/epubjs/dist/epub.min.js"></script>
        <style>
            #viewer {
                width: 100%;
                height: 600px;
                margin: 0 auto;
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
                        <h4 class="header-line">Listed Books</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                Books List
                            </div>
                            <div class="panel-body">
                                <?php
                                $sql = "SELECT tblbooks.BookName,tblbooks.epub_file_path,tblbooks.id as bookid,
                                        tblauthors.AuthorName, tblcategory.CategoryName
                                        FROM tblbooks 
                                        LEFT JOIN tblauthors ON tblbooks.AuthorId=tblauthors.id 
                                        LEFT JOIN tblcategory ON tblbooks.CatId=tblcategory.id
                                        WHERE tblbooks.epub_file_path IS NOT NULL";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                if ($query->rowCount() > 0) {
                                    echo '<div class="row">';
                                    foreach ($results as $result) {
                                        ?>
                                        <div class="col-md-4 col-sm-6 book-item">
                                            <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <h4><?php echo htmlentities($result->BookName);?></h4>
                                                </div>
                                                <div class="panel-body">
                                                    <p><strong>Author:</strong> <?php echo htmlentities($result->AuthorName);?></p>
                                                    <p><strong>Category:</strong> <?php echo htmlentities($result->CategoryName);?></p>
                                                    <a href="read-book.php?bookid=<?php echo htmlentities($result->bookid);?>" 
                                                       class="btn btn-primary btn-block">
                                                        <i class="fa fa-book"></i> Read Book
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<div class="alert alert-warning">No books available.</div>';
                                }
                                ?>
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
        <!-- CUSTOM SCRIPTS  -->
        <script src="assets/js/custom.js"></script>
    </body>

    </html>
<?php } ?>