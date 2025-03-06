    <?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Redirect if not logged in
if (!isset($_SESSION['login']) || strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit();
}

// Handle deletion of reading history
if (isset($_GET['del'])) {
    $bookId = filter_var($_GET['del'], FILTER_VALIDATE_INT);
    if ($bookId === false) {
        $_SESSION['error'] = "Invalid book ID";
    } else {
        try {
            $email = $_SESSION['login'];
            
            // Get user ID
            $userSql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
            $userQuery = $dbh->prepare($userSql);
            $userQuery->bindParam(':email', $email, PDO::PARAM_STR);
            $userQuery->execute();
            $userResult = $userQuery->fetch(PDO::FETCH_OBJ);
            
            if ($userResult) {
                // Delete reading history
                $sql = "DELETE FROM tblreadinghistory WHERE BookId = :bookid AND UserId = :userid";
                $query = $dbh->prepare($sql);
                $query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
                $query->bindParam(':userid', $userResult->UserId, PDO::PARAM_INT);
                $query->execute();
                
                $_SESSION['msg'] = "Book removed from reading history successfully";
                header('location:my-reading.php');
                exit();
            }
        } catch(PDOException $e) {
            error_log("Reading history deletion error: " . $e->getMessage());
            $_SESSION['error'] = "Could not remove book from reading history";
        }
    }
}

// Get user's ID from email
$email = $_SESSION['login'];
$userSql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
$userQuery = $dbh->prepare($userSql);
$userQuery->bindParam(':email', $email, PDO::PARAM_STR);
$userQuery->execute();
$userResult = $userQuery->fetch(PDO::FETCH_OBJ);

if (!$userResult) {
    header('location:index.php');
    exit();
}

$userId = $userResult->UserId;
$_SESSION['userid'] = $userId;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>My Reading List | Bookish</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .book-item {
            display: flex;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .book-cover {
            width: 120px;
            height: 180px;
            object-fit: cover;
            margin-right: 20px;
        }
        .book-info { flex: 1; }
        .book-meta { margin: 5px 0; }
        .reading-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            color: white;
            margin: 10px 0;
        }
        .status-reading { background-color: #17a2b8; }
        .status-completed { background-color: #28a745; }
        .status-abandoned { background-color: #dc3545; }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">My Reading History</h4>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['msg']); 
                    unset($_SESSION['msg']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <?php
                            try {
                                $sql = "SELECT DISTINCT b.BookName, b.bookImage, b.id as bookid,
                                        c.CategoryName, a.AuthorName,
                                        rh.ReadDate, rh.LastPage, rh.Status,
                                        COUNT(rh2.id) as read_count
                                        FROM tblbooks b
                                        INNER JOIN tblreadinghistory rh ON b.id = rh.BookId
                                        INNER JOIN tblcategory c ON c.id = b.CatId
                                        INNER JOIN tblauthors a ON a.id = b.AuthorId
                                        LEFT JOIN tblreadinghistory rh2 ON rh2.BookId = b.id AND rh2.UserId = :userid
                                        WHERE rh.UserId = :userid
                                        GROUP BY b.id, b.BookName, b.bookImage, c.CategoryName, a.AuthorName, rh.ReadDate, rh.LastPage, rh.Status
                                        ORDER BY rh.ReadDate DESC";

                                $query = $dbh->prepare($sql);
                                $query->bindParam(':userid', $userId, PDO::PARAM_INT);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);

                                if (empty($results)): ?>
                                    <div class="alert alert-info">
                                        <h4><i class="fa fa-info-circle"></i> No Reading History</h4>
                                        <p>You haven't started reading any books yet. Browse our collection and start reading!</p>
                                        <a href="books.php" class="btn btn-primary mt-3">Browse Books</a>
                                    </div>
                                <?php else:
                                    foreach($results as $result): ?>
                                        <div class="book-item">
                                            <img src="admin/bookimg/<?php echo htmlspecialchars($result->bookImage); ?>" 
                                                 alt="<?php echo htmlspecialchars($result->BookName); ?>"
                                                 class="book-cover">
                                            <div class="book-info">
                                                <h3 class="book-title">
                                                    <a href="book-details.php?bookid=<?php echo htmlspecialchars($result->bookid); ?>">
                                                        <?php echo htmlspecialchars($result->BookName); ?>
                                                    </a>
                                                </h3>
                                                <div class="book-meta">
                                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($result->AuthorName); ?></p>
                                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($result->CategoryName); ?></p>
                                                    <p><strong>Progress:</strong> 
                                                        <?php 
                                                        if($result->LastPage) {
                                                            echo '<span class="text-success"><i class="fa fa-book-reader"></i> Reading in Progress</span>';
                                                            if($result->read_count > 1) {
                                                                echo ' <small class="text-muted">(' . htmlspecialchars($result->read_count) . ' reading sessions)</small>';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">Not started</span>';
                                                        }
                                                        ?>
                                                    </p>
                                                    <p><strong>Last Read:</strong> <?php echo date('F j, Y', strtotime($result->ReadDate)); ?></p>
                                                </div>
                                                <span class="reading-status status-<?php echo strtolower(htmlspecialchars($result->Status)); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($result->Status)); ?>
                                                </span>
                                                <div class="book-actions">
                                                    <a href="read-book.php?bookid=<?php echo htmlspecialchars($result->bookid); ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fa fa-book-reader"></i> Continue Reading
                                                    </a>
                                                    <a href="my-reading.php?del=<?php echo htmlspecialchars($result->bookid); ?>" 
                                                       onclick="return confirm('Are you sure you want to remove this book from your reading history?');" 
                                                       class="btn btn-danger btn-sm">
                                                        <i class="fa fa-trash"></i> Remove
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                endif;
                            } catch(PDOException $e) {
                                error_log("Reading history retrieval error: " . $e->getMessage());
                                echo '<div class="alert alert-danger">Unable to retrieve reading history. Please try again later.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
</body>
</html>
