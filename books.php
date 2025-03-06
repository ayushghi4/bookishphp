<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Debug function
function debug($data) {
    error_log(print_r($data, true));
}

try {
    // Get categories for filter
    $cat_sql = "SELECT * FROM tblcategory ORDER BY CategoryName";
    $cat_query = $dbh->prepare($cat_sql);
    $cat_query->execute();
    $categories = $cat_query->fetchAll(PDO::FETCH_OBJ);
    debug("Categories found: " . count($categories));

    // Get authors for filter
    $auth_sql = "SELECT * FROM tblauthors ORDER BY AuthorName";
    $auth_query = $dbh->prepare($auth_sql);
    $auth_query->execute();
    $authors = $auth_query->fetchAll(PDO::FETCH_OBJ);
    debug("Authors found: " . count($authors));

    // Get the user ID if logged in
    $userid = isset($_SESSION['login']) ? $_SESSION['login'] : null;

    // Build query based on filters
    $conditions = array();
    $params = array();
    
    // Base query with LEFT JOINs to be more permissive
    $base_sql = "SELECT DISTINCT b.BookName, b.id, b.bookImage, b.epub_file_path,
                 COALESCE(c.CategoryName, 'Uncategorized') as CategoryName,
                 COALESCE(a.AuthorName, 'Unknown Author') as AuthorName
                 FROM tblbooks b
                 LEFT JOIN tblcategory c ON c.id = b.CatId 
                 LEFT JOIN tblauthors a ON a.id = b.AuthorId";

    // Add search condition
    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $conditions[] = "(b.BookName LIKE :search OR a.AuthorName LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    // Add category filter
    if(isset($_GET['category']) && !empty($_GET['category'])) {
        $conditions[] = "c.id = :category";
        $params[':category'] = $_GET['category'];
    }

    // Add author filter
    if(isset($_GET['author']) && !empty($_GET['author'])) {
        $conditions[] = "a.id = :author";
        $params[':author'] = $_GET['author'];
    }

    // Complete the SQL query
    if(!empty($conditions)) {
        $base_sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $base_sql .= " ORDER BY b.id DESC";

    // Debug information
    debug("Final SQL Query: " . $base_sql);
    debug("Query Parameters: " . print_r($params, true));

    try {
        $query = $dbh->prepare($base_sql);
        
        // Bind parameters
        foreach($params as $key => $value) {
            $query->bindValue($key, $value);
            debug("Binding parameter $key with value $value");
        }

        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        debug("Query executed successfully");
        debug("Number of books found: " . $query->rowCount());
        
        if($query->rowCount() > 0) {
            debug("First book in results: " . print_r($results[0], true));
        } else {
            debug("No books found in the query");
            echo '<div class="alert alert-info">No books found matching your criteria.</div>';
        }
    } catch(PDOException $e) {
        $error_msg = "Database error in books.php: " . $e->getMessage();
        error_log($error_msg);
        echo '<div class="alert alert-danger">Sorry, there was a problem fetching the books. Error: ' . htmlspecialchars($error_msg) . '</div>';
    }
} catch(Exception $e) {
    $error_msg = "General error in books.php: " . $e->getMessage();
    error_log($error_msg);
    echo '<div class="alert alert-danger">An error occurred while processing your request. Error: ' . htmlspecialchars($error_msg) . '</div>';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Bookish | Book Listing</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- DATATABLE STYLE  -->
    <link href="assets/css/dataTables.bootstrap.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .book-title {
            color: #2196F3;
            text-decoration: none;
            font-weight: 500;
        }
        .book-title:hover {
            color: #0d47a1;
            text-decoration: underline;
        }
        .table > tbody > tr > td {
            vertical-align: middle;
        }
        .d-flex {
            display: flex;
        }
        .align-items-center {
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Browse Books</h4>
                </div>
            </div>
            
            <!-- Filters Panel -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-filter"></i> Filter Books</h3>
                        </div>
                        <div class="panel-body">
                            <form method="get" class="form-horizontal">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Category</label>
                                            <select name="category" class="form-control">
                                                <option value="">All Categories</option>
                                                <?php foreach($categories as $category): ?>
                                                    <option value="<?php echo $category->id; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category->id) ? 'selected' : ''; ?>>
                                                        <?php echo htmlentities($category->CategoryName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Author</label>
                                            <select name="author" class="form-control">
                                                <option value="">All Authors</option>
                                                <?php foreach($authors as $author): ?>
                                                    <option value="<?php echo $author->id; ?>" <?php echo (isset($_GET['author']) && $_GET['author'] == $author->id) ? 'selected' : ''; ?>>
                                                        <?php echo htmlentities($author->AuthorName); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Search</label>
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" placeholder="Search books..." value="<?php echo isset($_GET['search']) ? htmlentities($_GET['search']) : ''; ?>">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Books Table View -->
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title"><i class="fa fa-book"></i> Available Books</h4>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="booksTable">
                                    <thead>
                                        <tr>
                                            <th>Book</th>
                                            <th>Category</th>
                                            <th>Author</th>
                                            <th width="120">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if($query->rowCount() > 0) {
                                            foreach($results as $result) { ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if($result->bookImage): ?>
                                                                <img src="admin/bookimg/<?php echo htmlentities($result->bookImage);?>" 
                                                                     alt="<?php echo htmlentities($result->BookName);?>" 
                                                                     style="width: 50px; height: 70px; object-fit: cover; margin-right: 10px;">
                                                            <?php endif; ?>
                                                            <span class="book-title">
                                                                <?php echo htmlentities($result->BookName);?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlentities($result->CategoryName);?></td>
                                                    <td><?php echo htmlentities($result->AuthorName);?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="book-details.php?bookid=<?php echo htmlentities($result->id);?>" class="btn btn-primary btn-sm">
                                                                <i class="fa fa-info-circle"></i> Details
                                                            </a>
                                                            <?php if(strlen($_SESSION['login']) > 0) { ?>
                                                                <?php if($result->epub_file_path) { ?>
                                                                    <a href="read-book.php?bookid=<?php echo htmlentities($result->id);?>" class="btn btn-success btn-sm">
                                                                        <i class="fa fa-book"></i> Read
                                                                    </a>
                                                                <?php } else { ?>
                                                                    <button class="btn btn-default btn-sm" disabled>
                                                                        <i class="fa fa-book"></i> N/A
                                                                    </button>
                                                                <?php } ?>
                                                            <?php } else { ?>
                                                                <a href="login.php" class="btn btn-info btn-sm">
                                                                    <i class="fa fa-lock"></i> Login
                                                                </a>
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } ?>
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
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script>
        $(document).ready(function() {
            $('#booksTable').DataTable({
                "pageLength": 10,
                "order": [[0, "asc"]],
                "language": {
                    "emptyTable": "No books found matching your criteria."
                }
            });
        });
    </script>
</body>
</html>