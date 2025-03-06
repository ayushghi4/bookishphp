<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Bookish Library Management System | </title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .book-card {
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .book-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transform: translateY(-5px);
        }
        .book-thumb {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }
        .book-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .book-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .book-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .book-actions {
            margin-top: auto;
            padding-top: 15px;
        }
        .action-btn {
            display: block;
            width: 100%;
            padding: 8px;
            text-align: center;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 8px;
            transition: background 0.3s ease;
        }
        .read-btn {
            background: #28a745;
        }
        .read-btn:hover {
            background: #218838;
            color: #fff;
            text-decoration: none;
        }
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: #007bff;
        }
        .star-rating {
            color: #ffd700;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">WELCOME TO BOOKISH</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h3 class="section-title">Latest Books</h3>
                </div>
                <?php
                try {
                    $sql = "SELECT b.BookName, b.id, b.bookImage, b.epub_file_path, 
                           c.CategoryName, a.AuthorName,
                           COALESCE(AVG(rt.Rating), 0) as AverageRating,
                           COUNT(rt.Rating) as RatingCount
                           FROM tblbooks b 
                           INNER JOIN tblcategory c ON c.id = b.CatId 
                           INNER JOIN tblauthors a ON a.id = b.AuthorId 
                           LEFT JOIN tblratings rt ON rt.BookId = b.id
                           GROUP BY b.id, b.BookName, b.bookImage, b.epub_file_path, c.CategoryName, a.AuthorName
                           ORDER BY b.id DESC LIMIT 8";
                    error_log("Executing SQL: " . $sql);
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    error_log("Found " . count($results) . " books");
                    
                    if($query->rowCount() > 0) {
                        foreach($results as $result) { 
                            error_log("Processing book: " . $result->BookName);
                            error_log("EPUB file: " . ($result->epub_file_path ? $result->epub_file_path : 'none'));
                            ?>
                            <div class="col-md-3 col-sm-6">
                                <div class="book-card">
                                    <div style="position: relative;">
                                        <?php if($result->bookImage) { ?>
                                            <img src="admin/bookimg/<?php echo htmlentities($result->bookImage);?>" class="book-thumb" alt="<?php echo htmlentities($result->BookName);?>"/>
                                        <?php } else { ?>
                                            <img src="assets/img/book-placeholder.jpg" class="book-thumb" alt="No image available"/>
                                        <?php } ?>
                                    </div>
                                    <div class="book-info">
                                        <h4 class="book-title"><?php echo htmlentities($result->BookName);?></h4>
                                        <p class="book-meta">Category: <?php echo htmlentities($result->CategoryName);?></p>
                                        <p class="book-meta">Author: <?php echo htmlentities($result->AuthorName);?></p>
                                        <div class="star-rating">
                                            <?php 
                                            $rating = round($result->AverageRating);
                                            for($i = 1; $i <= 5; $i++) {
                                                if($i <= $rating) {
                                                    echo '<i class="fa fa-star"></i>';
                                                } else {
                                                    echo '<i class="fa fa-star-o"></i>';
                                                }
                                            }
                                            echo " (" . $result->RatingCount . " ratings)";
                                            ?>
                                        </div>
                                        <div class="book-actions">
                                            <?php if(strlen($_SESSION['login']) > 0) { 
                                                // Check EPUB file in admin/epubfiles directory
                                                $epubPath = 'admin/epubfiles/' . $result->epub_file_path;
                                                $fullEpubPath = __DIR__ . '/' . $epubPath;
                                                error_log("Checking EPUB at: " . $fullEpubPath);
                                                
                                                if($result->epub_file_path && file_exists($fullEpubPath)) { ?>
                                                    <a href="read-book.php?bookid=<?php echo htmlentities($result->id); ?>" class="btn btn-success btn-sm read-now">
                                                        <i class="fa fa-book"></i> Read Now
                                                    </a>
                                                <?php } else { ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fa fa-exclamation-circle"></i> EPUB Not Available
                                                    </button>
                                                <?php }
                                            } else { ?>
                                                <a href="login.php" class="btn btn-info btn-sm">
                                                    <i class="fa fa-sign-in"></i> Login to Read
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="col-md-12">
                            <div class="alert alert-info">No books found in the library.</div>
                        </div>
                    <?php }
                } catch(PDOException $e) {
                    error_log("Error fetching books: " . $e->getMessage());
                    ?>
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            Sorry, there was a problem loading the books.
                        </div>
                    </div>
                <?php }
                ?>
            </div>
            <div class="row">
                <div class="col-md-12 text-center" style="margin-top: 20px;">
                    <a href="books.php" class="btn btn-primary">View All Books</a>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
