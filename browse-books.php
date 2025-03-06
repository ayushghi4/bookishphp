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
    <title>Bookish | Browse Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include('includes/header.php');?>

    <div class="container-fluid py-4">
        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" action="browse-books.php">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="text" name="search" class="form-control" placeholder="Search books..." 
                                            value="<?php echo isset($_GET['search']) ? htmlentities($_GET['search']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select name="category" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php 
                                        $sql = "SELECT * FROM tblcategory WHERE Status=1";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $categories = $query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($categories as $category) {
                                                $selected = (isset($_GET['category']) && $_GET['category'] == $category->id) ? 'selected' : '';
                                                echo "<option value='".$category->id."' ".$selected.">".$category->CategoryName."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="author" class="form-select">
                                        <option value="">All Authors</option>
                                        <?php 
                                        $sql = "SELECT * FROM tblauthors";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $authors = $query->fetchAll(PDO::FETCH_OBJ);
                                        if($query->rowCount() > 0) {
                                            foreach($authors as $author) {
                                                $selected = (isset($_GET['author']) && $_GET['author'] == $author->id) ? 'selected' : '';
                                                echo "<option value='".$author->id."' ".$selected.">".$author->AuthorName."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Books Grid -->
        <div class="row">
            <?php
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $author = isset($_GET['author']) ? $_GET['author'] : '';

            $sql = "SELECT b.*, c.CategoryName, a.AuthorName, 
                    (SELECT AVG(Rating) FROM tblbookhistory WHERE BookId = b.id) as AvgRating
                    FROM tblbooks b
                    LEFT JOIN tblcategory c ON c.id = b.CatId
                    LEFT JOIN tblauthors a ON a.id = b.AuthorId
                    WHERE 1=1";

            if(!empty($search)) {
                $sql .= " AND (b.BookName LIKE :search OR b.BookDescription LIKE :search)";
            }
            if(!empty($category)) {
                $sql .= " AND b.CatId = :category";
            }
            if(!empty($author)) {
                $sql .= " AND b.AuthorId = :author";
            }

            $query = $dbh->prepare($sql);
            
            if(!empty($search)) {
                $searchParam = "%".$search."%";
                $query->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            if(!empty($category)) {
                $query->bindParam(':category', $category, PDO::PARAM_INT);
            }
            if(!empty($author)) {
                $query->bindParam(':author', $author, PDO::PARAM_INT);
            }

            $query->execute();
            $books = $query->fetchAll(PDO::FETCH_OBJ);
            
            if($query->rowCount() > 0) {
                foreach($books as $book) { ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <?php if($book->bookImage) { ?>
                                <img src="../bookimg/<?php echo htmlentities($book->bookImage);?>" class="card-img-top" alt="Book Cover">
                            <?php } else { ?>
                                <img src="../bookimg/default.jpg" class="card-img-top" alt="Default Cover">
                            <?php } ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlentities($book->BookName);?></h5>
                                <p class="card-text text-muted">By <?php echo htmlentities($book->AuthorName);?></p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Category: <?php echo htmlentities($book->CategoryName);?>
                                    </small>
                                </p>
                                <div class="mb-2">
                                    <?php
                                    $rating = round($book->AvgRating);
                                    for($i = 1; $i <= 5; $i++) {
                                        if($i <= $rating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <a href="read-book.php?bookid=<?php echo htmlentities($book->id);?>" class="btn btn-primary">Read Book</a>
                            </div>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No books found matching your criteria.
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php include('includes/footer.php');?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
</body>
</html>
<?php } ?>
