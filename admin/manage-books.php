<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    if (isset($_GET['del'])) {
        $id = $_GET['del'];

        // First get the epub file name
        $sql = "SELECT epub_file_path FROM library.tblbooks WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_OBJ);

        // Delete the epub file if it exists
        if ($result && $result->epub_file_path) {
            $epubPath = 'epubfiles/' . $result->epub_file_path;
            if (file_exists($epubPath)) {
                unlink($epubPath);
            }
        }

        // Delete from database
        $sql = "delete from library.tblbooks WHERE id=:id";
        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_STR);
        $query->execute();

        $_SESSION['delmsg'] = "Book deleted successfully";
        header('location:manage-books.php');
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Bookish - Online Book Library | Manage Books</title>
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
</head>

<body>
    <!------MENU SECTION START-->
    <?php include('includes/header.php'); ?>
    <!-- MENU SECTION END-->
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Manage Books</h4>
                </div>
                <div class="col-md-12">
                    <?php if ($_SESSION['error'] != "") { ?>
                        <div class="alert alert-danger">
                            <strong>Error :</strong>
                            <?php echo htmlentities($_SESSION['error']); ?>
                            <?php echo htmlentities($_SESSION['error'] = ""); ?>
                        </div>
                    <?php } ?>
                    <?php if ($_SESSION['msg'] != "") { ?>
                        <div class="alert alert-success">
                            <strong>Success :</strong>
                            <?php echo htmlentities($_SESSION['msg']); ?>
                            <?php echo htmlentities($_SESSION['msg'] = ""); ?>
                        </div>
                    <?php } ?>
                    <?php if ($_SESSION['updatemsg'] != "") { ?>
                        <div class="alert alert-success">
                            <strong>Success :</strong>
                            <?php echo htmlentities($_SESSION['updatemsg']); ?>
                            <?php echo htmlentities($_SESSION['updatemsg'] = ""); ?>
                        </div>
                    <?php } ?>
                    <?php if ($_SESSION['delmsg'] != "") { ?>
                        <div class="alert alert-success">
                            <strong>Success :</strong>
                            <?php echo htmlentities($_SESSION['delmsg']); ?>
                            <?php echo htmlentities($_SESSION['delmsg'] = ""); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <!-- Advanced Tables -->
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Books Listing
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Book Image</th>
                                            <th>Book Name</th>
                                            <th>Category</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Price</th>
                                            <th>Readers</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        error_reporting(E_ALL);
                                        ini_set('display_errors', 1);
                                        
                                        $sql = "SELECT b.*, c.CategoryName, a.AuthorName 
                                               FROM library.tblbooks b 
                                               LEFT JOIN library.tblcategory c ON b.CatId = c.id 
                                               LEFT JOIN library.tblauthors a ON b.AuthorId = a.id";
                                        
                                        try {
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            
                                            if($query->rowCount() > 0) {
                                                $cnt = 1;
                                                foreach($results as $result) { ?>
                                                    <tr class="odd gradeX">
                                                        <td class="center"><?php echo htmlentities($cnt);?></td>
                                                        <td class="center">
                                                            <?php 
                                                            if(isset($result->bookImage) && $result->bookImage) {
                                                                $imagePath = "bookimg/" . htmlentities($result->bookImage);
                                                                if(file_exists($imagePath)) {
                                                                    echo '<img src="'.$imagePath.'" width="100">';
                                                                } else {
                                                                    echo '<span class="text-muted">No Image</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">No Image</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="center"><?php echo htmlentities($result->BookName);?></td>
                                                        <td class="center"><?php echo isset($result->CategoryName) ? htmlentities($result->CategoryName) : 'N/A';?></td>
                                                        <td class="center"><?php echo isset($result->AuthorName) ? htmlentities($result->AuthorName) : 'N/A';?></td>
                                                        <td class="center"><?php echo isset($result->ISBNNumber) ? htmlentities($result->ISBNNumber) : 'N/A';?></td>
                                                        <td class="center"><?php echo isset($result->BookPrice) ? htmlentities($result->BookPrice) : '0.00';?></td>
                                                        <td class="center">
                                                            <?php 
                                                            try {
                                                                $readerSql = "SELECT COUNT(*) as count FROM library.tblissuedbookdetails WHERE BookId = :bookid";
                                                                $readerQuery = $dbh->prepare($readerSql);
                                                                $readerQuery->bindParam(':bookid', $result->id, PDO::PARAM_INT);
                                                                $readerQuery->execute();
                                                                $readers = $readerQuery->fetch(PDO::FETCH_OBJ);
                                                                echo htmlentities($readers->count);
                                                            } catch(PDOException $e) {
                                                                echo '0';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="center">
                                                            <a href="edit-book.php?bookid=<?php echo htmlentities($result->id);?>">
                                                                <button class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</button>
                                                            </a>
                                                            <a href="manage-books.php?del=<?php echo htmlentities($result->id);?>" onclick="return confirm('Are you sure you want to delete?');">
                                                                <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</button>
                                                            </a>
                                                        </td>
                                                    </tr>
                                            <?php $cnt=$cnt+1;}} else { ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">No books found in the database.</td>
                                                </tr>
                                            <?php }
                                        } catch(PDOException $e) {
                                            echo "<tr><td colspan='9' class='text-center text-danger'>Error: Unable to fetch books. Please try again later.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--End Advanced Tables -->
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
    <!-- CUSTOM SCRIPTS  -->
    <script src="assets/js/custom.js"></script>
</body>

</html>
<?php ?>