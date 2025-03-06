<?php
session_start();
error_reporting(0);
include('includes/config.php');

if(isset($_GET['bookid'])) {
    $bookid = intval($_GET['bookid']);
    $sql = "SELECT tblbooks.BookName, tblbooks.id as bookid, tblcategory.CategoryName, 
            tblauthors.AuthorName, tblbooks.ISBNNumber, tblbooks.bookImage, 
            tblbooks.epub_file_path, tblbooks.RegDate as bookregdate 
            FROM tblbooks 
            INNER JOIN tblcategory ON tblcategory.id = tblbooks.CatId 
            INNER JOIN tblauthors ON tblauthors.id = tblbooks.AuthorId 
            WHERE tblbooks.id = :bookid";
    
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookid', $bookid, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() == 0) {
        $_SESSION['error'] = "Book not found or has been deleted.";
        header('location: books.php');
        exit();
    }
    
    $result = $query->fetch(PDO::FETCH_OBJ);

    // Add to reading history if user is logged in
    if(isset($_SESSION['login'])) {
        // Get the user's UserId from tblusers
        $email = $_SESSION['login'];
        error_log("Reading History - Email: " . $email);
        
        $userSql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
        $userQuery = $dbh->prepare($userSql);
        $userQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $userQuery->execute();
        $userResult = $userQuery->fetch(PDO::FETCH_OBJ);
        
        if($userResult) {
            $userId = $userResult->UserId;
            error_log("Reading History - UserId: " . $userId);
            
            // Check if already in reading history
            $checkSql = "SELECT id FROM tblreadinghistory WHERE BookId = :bookid AND UserId = :userid";
            $checkQuery = $dbh->prepare($checkSql);
            $checkQuery->bindParam(':bookid', $bookid, PDO::PARAM_INT);
            $checkQuery->bindParam(':userid', $userId, PDO::PARAM_STR);
            $checkQuery->execute();
            error_log("Reading History - Check Query Executed");

            if($checkQuery->rowCount() == 0) {
                error_log("Reading History - Adding new entry");
                // Add new reading history entry
                $insertSql = "INSERT INTO tblreadinghistory (BookId, UserId, ReadDate, LastPage, Status) 
                             VALUES (:bookid, :userid, NOW(), 1, 'reading')";
                $insertQuery = $dbh->prepare($insertSql);
                $insertQuery->bindParam(':bookid', $bookid, PDO::PARAM_INT);
                $insertQuery->bindParam(':userid', $userId, PDO::PARAM_STR);
                $insertQuery->execute();
                error_log("Reading History - Insert completed");
            } else {
                // Update existing reading history
                $updateSql = "UPDATE tblreadinghistory 
                             SET ReadDate = NOW(), Status = 'reading'
                             WHERE BookId = :bookid AND UserId = :userid";
                $updateQuery = $dbh->prepare($updateSql);
                $updateQuery->bindParam(':bookid', $bookid, PDO::PARAM_INT);
                $updateQuery->bindParam(':userid', $userId, PDO::PARAM_STR);
                $updateQuery->execute();
                error_log("Reading History - Update completed");
            }
        } else {
            error_log("Reading History - User not found");
        }
    } else {
        error_log("Reading History - User not logged in");
    }
    
} else {
    header('location: books.php');
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
    <title>Bookish | <?php echo htmlentities($result->BookName); ?></title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .rating {
            direction: rtl;
            unicode-bidi: bidi-override;
            text-align: left;
            margin: 10px 0;
        }
        .rating input {
            display: none;
        }
        .rating label {
            display: inline-block;
            padding: 0 2px;
            font-size: 35px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating label:before {
            content: '★';
        }
        .rating input:checked ~ label,
        .rating label:hover,
        .rating label:hover ~ label {
            color: #FFD700;
            text-shadow: 0 0 2px #C59B08;
        }
        .book-detail-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .book-detail-image {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .book-detail-actions {
            padding: 15px;
        }
        
        .book-meta {
            padding: 15px 0;
        }
        
        .meta-item {
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .meta-item i {
            width: 25px;
            color: #337ab7;
        }
        
        .meta-label {
            font-weight: bold;
            margin-right: 10px;
            color: #555;
        }
        
        .meta-value {
            color: #333;
        }
        
        .header-line {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #337ab7;
        }
        
        .panel {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .panel-body {
            padding: 25px;
        }
        
        @media (max-width: 768px) {
            .book-detail-image {
                max-width: 300px;
                margin: 0 auto;
                display: block;
            }
            
            .book-meta {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <!-- Book Details Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <i class="fa fa-book"></i> <?php echo htmlentities($result->BookName); ?>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <!-- Book Image -->
                                <div class="col-md-4">
                                    <div class="book-detail-card">
                                        <img src="admin/bookimg/<?php echo htmlentities($result->bookImage);?>" 
                                             alt="<?php echo htmlentities($result->BookName);?>" 
                                             class="book-detail-image img-responsive">
                                        <div class="book-detail-actions">
                                            <?php if(isset($_SESSION['login'])) { ?>
                                                <?php if($result->epub_file_path) { ?>
                                                    <a href="read-book.php?bookid=<?php echo htmlentities($result->bookid);?>" 
                                                       class="btn btn-success btn-block">
                                                        <i class="fa fa-book"></i> Read Now
                                                    </a>
                                                <?php } else { ?>
                                                    <button class="btn btn-default btn-block" disabled>
                                                        <i class="fa fa-book"></i> Not Available
                                                    </button>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <a href="login.php" class="btn btn-info btn-block">
                                                    <i class="fa fa-sign-in"></i> Login to Read
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Book Info -->
                                <div class="col-md-8">
                                    <div class="book-meta">
                                        <div class="meta-item">
                                            <i class="fa fa-user"></i>
                                            <span class="meta-label">Author:</span>
                                            <span class="meta-value"><?php echo htmlentities($result->AuthorName); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa fa-bookmark"></i>
                                            <span class="meta-label">Category:</span>
                                            <span class="meta-value"><?php echo htmlentities($result->CategoryName); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa fa-barcode"></i>
                                            <span class="meta-label">ISBN:</span>
                                            <span class="meta-value"><?php echo htmlentities($result->ISBNNumber); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fa fa-calendar"></i>
                                            <span class="meta-label">Added On:</span>
                                            <span class="meta-value"><?php echo date('F j, Y', strtotime($result->bookregdate)); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reviews Section -->
    <div class="content-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-comments"></i> Reviews</h3>
                        </div>
                        <div class="panel-body">
                            <?php if(isset($_SESSION['login'])) { ?>
                                <div class="review-form-container">
                                    <h4><i class="fa fa-pencil"></i> Write a Review</h4>
                                    <?php if(isset($_SESSION['success'])) { ?>
                                        <div class="alert alert-success">
                                            <i class="fa fa-check-circle"></i> <?php 
                                            echo $_SESSION['success'];
                                            unset($_SESSION['success']);
                                            ?>
                                        </div>
                                    <?php } ?>
                                    <?php if(isset($_SESSION['error'])) { ?>
                                        <div class="alert alert-danger">
                                            <i class="fa fa-exclamation-circle"></i> <?php echo $_SESSION['error']; $_SESSION['error'] = ''; ?>
                                        </div>
                                    <?php } ?>
                                    <form method="post" action="api/submit-review.php" id="reviewForm">
                                        <input type="hidden" name="bookId" value="<?php echo htmlentities($result->bookid); ?>">
                                        <div class="form-group">
                                            <label><i class="fa fa-star"></i> Rating</label>
                                            <div class="rating">
                                                <input type="radio" id="star5" name="rating" value="5" required />
                                                <label for="star5"></label>
                                                <input type="radio" id="star4" name="rating" value="4" required />
                                                <label for="star4"></label>
                                                <input type="radio" id="star3" name="rating" value="3" required />
                                                <label for="star3"></label>
                                                <input type="radio" id="star2" name="rating" value="2" required />
                                                <label for="star2"></label>
                                                <input type="radio" id="star1" name="rating" value="1" required />
                                                <label for="star1"></label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="review"><i class="fa fa-comment"></i> Your Review</label>
                                            <textarea class="form-control" id="review" name="review" rows="4" placeholder="Share your thoughts about this book..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-paper-plane"></i> Submit Review
                                        </button>
                                    </form>
                                </div>
                            <?php } ?>

                            <div id="reviewsList" class="reviews-list">
                                <?php
                                // Fetch existing reviews
                                $sql = "SELECT u.FullName, rt.Rating, r.Review, r.CreatedAt 
                                        FROM tblreviews r 
                                        INNER JOIN tblusers u ON u.UserId = r.UserId 
                                        LEFT JOIN tblratings rt ON rt.BookId = r.BookId AND rt.UserId = r.UserId
                                        WHERE r.BookId = :bookid 
                                        AND r.Status = 1 
                                        ORDER BY r.CreatedAt DESC";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':bookid', $bookid, PDO::PARAM_STR);
                                $query->execute();
                                $reviews = $query->fetchAll(PDO::FETCH_OBJ);

                                if($query->rowCount() > 0) {
                                    foreach($reviews as $review) { ?>
                                        <div class="review-item">
                                            <div class="review-header">
                                                <div class="reviewer-info">
                                                    <span class="reviewer-name">
                                                        <i class="fa fa-user"></i> <?php echo htmlentities($review->FullName); ?>
                                                    </span>
                                                    <span class="review-date">
                                                        <i class="fa fa-calendar"></i> <?php echo date('M d, Y', strtotime($review->CreatedAt)); ?>
                                                    </span>
                                                </div>
                                                <div class="rating-display">
                                                    <?php
                                                    for($i = 1; $i <= 5; $i++) {
                                                        if($i <= $review->Rating) {
                                                            echo "<i class='fa fa-star'></i>";
                                                        } else {
                                                            echo "<i class='fa fa-star-o'></i>";
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="review-content">
                                                <p class="review-text"><?php echo htmlentities($review->Review); ?></p>
                                            </div>
                                        </div>
                                    <?php }
                                } else { ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No reviews yet. Be the first to review this book!
                                    </div>
                                <?php } ?>
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
    <script>
    $(document).ready(function() {
        // Handle form submission
        $('#reviewForm').on('submit', function(e) {
            e.preventDefault();
            var rating = $('input[name="rating"]:checked').val();
            var review = $('#review').val();
            
            if (!rating) {
                alert('Please select a rating');
                return false;
            }
            if (!review) {
                alert('Please write a review');
                return false;
            }
            
            this.submit();
        });
    });
    </script>
    <style>
    .book-detail-card {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .book-detail-image {
        width: 100%;
        height: auto;
        display: block;
    }

    .book-detail-actions {
        padding: 20px;
    }

    .book-detail-info {
        height: 100%;
    }

    .book-meta {
        display: grid;
        gap: 20px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .meta-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .meta-item i {
        font-size: 20px;
        color: #2196F3;
        margin-right: 15px;
        width: 24px;
        text-align: center;
    }

    .meta-label {
        font-weight: 600;
        margin-right: 10px;
        color: #333;
    }

    .meta-value {
        color: #666;
    }

    .review-form {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .review-list {
        margin-top: 30px;
    }

    .review-item {
        background: white;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .review-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    }

    .review-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .reviewer-name {
        font-weight: bold;
        color: #2196F3;
    }

    .rating-display {
        color: #f7d32d;
        font-size: 18px;
        margin: 0 15px;
    }

    .review-date {
        color: #666;
        font-size: 14px;
        margin-left: auto;
    }

    .review-text {
        color: #333;
        line-height: 1.6;
    }

    .no-reviews {
        text-align: center;
        color: #666;
        font-style: italic;
        padding: 20px;
    }

    /* Rating Stars */
    .rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }

    .rating input {
        display: none;
    }

    .rating label {
        cursor: pointer;
        font-size: 30px;
        color: #ddd;
    }

    .rating label:before {
        content: '★';
    }

    .rating input:checked ~ label {
        color: #ffc107;
    }

    .rating label:hover,
    .rating label:hover ~ label {
        color: #ffc107;
    }
    </style>
    <style>
    /* Review System Styles */
    .review-section-title {
        margin: 30px 0 20px;
        color: #2196F3;
    }
    .review-form-container {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    .rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }
    .rating input {
        display: none;
    }
    .rating label {
        cursor: pointer;
        font-size: 30px;
        color: #ddd;
        padding: 5px;
    }
    .rating input:checked ~ label,
    .rating label:hover,
    .rating label:hover ~ label {
        color: #f7d32d;
    }
    .reviews-list {
        margin-top: 30px;
    }
    .review-item {
        background: white;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .reviewer-name {
        font-weight: bold;
        color: #2196F3;
    }
    .rating-display {
        color: #f7d32d;
        font-size: 18px;
    }
    .review-date {
        color: #666;
        font-size: 0.9em;
    }
    .review-text {
        color: #333;
        line-height: 1.6;
    }
    .no-reviews {
        text-align: center;
        color: #666;
        padding: 20px;
    }
    </style>
</body>
</html>
