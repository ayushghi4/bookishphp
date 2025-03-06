<?php
// Set upload limits at runtime
ini_set('upload_max_filesize', '5000M');
ini_set('post_max_size', '5000M');
ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '300');

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
    if (isset($_POST['add'])) {
        error_log("Starting book addition process");
        
        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Check PHP upload settings
            error_log("PHP Upload Settings:");
            error_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
            error_log("post_max_size: " . ini_get('post_max_size'));
            error_log("max_execution_time: " . ini_get('max_execution_time'));
            
            // Debug POST and FILES
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            // Check if the form was actually submitted with files
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("Form submitted via POST");
                if (empty($_FILES)) {
                    error_log("No files were uploaded - _FILES array is empty");
                    throw new Exception("No files were uploaded. Please check if the form has enctype='multipart/form-data'");
                }
            }
            
            // Verify form data
            if (!isset($_FILES['bookimg']) || $_FILES['bookimg']['error'] === UPLOAD_ERR_NO_FILE) {
                error_log("Book image not uploaded - FILES array content: " . print_r($_FILES, true));
                throw new Exception("Please select a book cover image");
            }
            
            if ($_FILES['bookimg']['error'] !== UPLOAD_ERR_OK) {
                $upload_errors = array(
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                );
                $error_message = isset($upload_errors[$_FILES['bookimg']['error']]) ? 
                                $upload_errors[$_FILES['bookimg']['error']] : 
                                'Unknown upload error';
                error_log("Upload error: " . $error_message);
                throw new Exception("Image upload failed: " . $error_message);
            }
            
            // Check if files were uploaded correctly
            if (!isset($_FILES['bookimg'])) {
                error_log("No file data received in _FILES['bookimg']");
            } else {
                error_log("File upload details: " . print_r($_FILES['bookimg'], true));
            }
            
            // Verify database connection
            if (!$dbh) {
                error_log("Database connection failed!");
                throw new Exception("Database connection failed");
            }
            error_log("Database connection successful");
            
            // Check if epub_file_path column exists
            try {
                $checkColumn = $dbh->query("SHOW COLUMNS FROM tblbooks LIKE 'epub_file_path'");
                if ($checkColumn->rowCount() == 0) {
                    // Add epub_file_path column if it doesn't exist
                    $dbh->exec("ALTER TABLE tblbooks ADD COLUMN epub_file_path VARCHAR(255) AFTER bookImage");
                    error_log("Added epub_file_path column to tblbooks table");
                }
            } catch (PDOException $e) {
                error_log("Error checking/adding epub_file_path column: " . $e->getMessage());
            }

            // Create epubfiles directory if it doesn't exist
            $epubDir = 'epubfiles/';
            if (!file_exists($epubDir)) {
                if (!mkdir($epubDir, 0777, true)) {
                    error_log("Failed to create directory: " . $epubDir);
                    throw new Exception("Failed to create EPUB upload directory");
                }
            }

            // Verify directory is writable
            if (!is_writable($epubDir)) {
                error_log("Upload directory is not writable: " . $epubDir);
                chmod($epubDir, 0777);
                if (!is_writable($epubDir)) {
                    throw new Exception("EPUB upload directory is not writable");
                }
            }
            
            // Log the attempt to add a book
            error_log("Attempting to add book: " . $_POST['bookname']);
            
            // Check current number of books
            $countSql = "SELECT COUNT(*) as total FROM tblbooks";
            $countQuery = $dbh->query($countSql);
            $currentCount = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
            error_log("Current number of books in database: " . $currentCount);
            
            // Get POST data and log all form data for debugging
            error_log("POST data received: " . print_r($_POST, true));
            error_log("FILES data received: " . print_r($_FILES, true));
            
            // Get POST data
            $bookname = trim($_POST['bookname']);
            $category = trim($_POST['category']);
            $author = trim($_POST['author']);
            $isbn = trim($_POST['isbn']);

            // Debug output
            error_log("Processing book: $bookname");
            error_log("Category ID: $category");
            error_log("Author ID: $author");
            error_log("ISBN: $isbn");

            // Validate required fields
            if (empty($bookname) || empty($category) || empty($author) || empty($isbn)) {
                error_log("Validation error: All fields are required");
                throw new Exception("All fields are required");
            }

            // Handle book cover image upload
            if(isset($_FILES['bookimg']['name'])) {
                error_log("Processing book cover image upload");
                
                // Create directory if it doesn't exist
                $uploadDir = 'bookimg/';
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        error_log("Failed to create directory: " . $uploadDir);
                        throw new Exception("Failed to create upload directory");
                    }
                }
                
                // Verify directory is writable
                if (!is_writable($uploadDir)) {
                    error_log("Upload directory is not writable: " . $uploadDir);
                    chmod($uploadDir, 0777);
                    if (!is_writable($uploadDir)) {
                        throw new Exception("Upload directory is not writable");
                    }
                }

                $file = $_FILES['bookimg'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];
                
                error_log("File details - Name: $fileName, Temp: $fileTmpName, Error: $fileError");
                
                // Check for upload errors
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errorMessages = array(
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                    );
                    $errorMessage = isset($errorMessages[$fileError]) ? $errorMessages[$fileError] : 'Unknown upload error';
                    error_log("Upload error: " . $errorMessage);
                    throw new Exception("Image upload failed: " . $errorMessage);
                }
                
                // Generate unique filename
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $imgnewname = md5($fileName . time()) . "." . $extension;
                $destination = $uploadDir . $imgnewname;
                
                error_log("Attempting to move file to: " . $destination);
                
                // Attempt to move the file
                if (!move_uploaded_file($fileTmpName, $destination)) {
                    error_log("Failed to move uploaded file from $fileTmpName to $destination");
                    throw new Exception("Failed to save uploaded image");
                }
                
                error_log("Successfully uploaded image to: " . $destination);
            } else {
                error_log("No image file was uploaded");
                throw new Exception("Please select a book cover image");
            }

            // Handle EPUB file upload
            if(isset($_FILES['epubfile']) && $_FILES['epubfile']['error'] == 0) {
                $epub_file = $_FILES['epubfile'];
                error_log("EPUB file details: " . print_r($epub_file, true));
                
                // Check file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $epub_file['tmp_name']);
                finfo_close($finfo);
                
                if($mime_type !== 'application/epub+zip') {
                    error_log("Invalid EPUB mime type: " . $mime_type);
                    throw new Exception("Invalid file type. Please upload a valid EPUB file.");
                }
                
                // Create epubfiles directory if it doesn't exist
                $epub_upload_dir = 'epubfiles';
                if (!file_exists($epub_upload_dir)) {
                    mkdir($epub_upload_dir, 0777, true);
                    error_log("Created EPUB upload directory: $epub_upload_dir");
                }
                
                // Generate unique filename
                $epub_filename = md5(uniqid()) . '.epub';
                $epub_path = $epub_upload_dir . '/' . $epub_filename;
                
                error_log("Attempting to move EPUB to: $epub_path");
                if(move_uploaded_file($epub_file['tmp_name'], $epub_path)) {
                    error_log("EPUB file uploaded successfully to: $epub_path");
                    chmod($epub_path, 0644); // Set proper file permissions
                } else {
                    error_log("Failed to move EPUB file. Upload error: " . print_r($_FILES['epubfile']['error'], true));
                    throw new Exception("Failed to upload EPUB file");
                }
            } else {
                $epub_filename = null;
                error_log("No EPUB file uploaded or upload error: " . (isset($_FILES['epubfile']) ? $_FILES['epubfile']['error'] : 'Not set'));
            }

            try {
                // Insert into database with error checking
                $sql = "INSERT INTO tblbooks (BookName, CatId, AuthorId, ISBNNumber, bookImage, epub_file_path, RegDate) 
                       VALUES (:bookname, :category, :author, :isbn, :imgnewname, :epubfile, NOW())";
                
                error_log("SQL Query: " . $sql);
                
                $query = $dbh->prepare($sql);
                $query->bindParam(':bookname', $bookname, PDO::PARAM_STR);
                $query->bindParam(':category', $category, PDO::PARAM_STR);
                $query->bindParam(':author', $author, PDO::PARAM_STR);
                $query->bindParam(':isbn', $isbn, PDO::PARAM_STR);
                $query->bindParam(':imgnewname', $imgnewname, PDO::PARAM_STR);
                $query->bindParam(':epubfile', $epub_filename, PDO::PARAM_STR);
                
                // Debug all parameters
                error_log("Parameters for insert:");
                error_log("bookname: $bookname");
                error_log("category: $category");
                error_log("author: $author");
                error_log("isbn: $isbn");
                error_log("imgnewname: $imgnewname");
                error_log("epubfile: " . ($epub_filename ? $epub_filename : 'null'));
                
                // Log the actual values being inserted
                error_log("Attempting to execute query with values:");
                error_log("bookname: $bookname");
                error_log("category: $category");
                error_log("author: $author");
                error_log("isbn: $isbn");
                error_log("imgnewname: $imgnewname");
                
                if (!$query->execute()) {
                    $error = $query->errorInfo();
                    error_log("Database error occurred: " . print_r($error, true));
                    throw new Exception("Database Error: " . $error[2]);
                }
                
                // Verify the insert by checking new count
                $newCountQuery = $dbh->query("SELECT COUNT(*) as total FROM tblbooks");
                $newCount = $newCountQuery->fetch(PDO::FETCH_ASSOC)['total'];
                error_log("New number of books in database: " . $newCount);

                // Get the inserted ID
                $lastId = $dbh->lastInsertId();
                error_log("Inserted book with ID: " . $lastId);

                // No need for separate update since we're including epubFile in the initial insert

                // Debugging to check authors and categories
                try {
                    // Check authors
                    $auth_check = $dbh->query("SELECT COUNT(*) as count FROM tblauthors");
                    $auth_count = $auth_check->fetch();
                    error_log("Number of authors in database: " . $auth_count->count);

                    // Check categories
                    $cat_check = $dbh->query("SELECT COUNT(*) as count FROM tblcategory");
                    $cat_count = $cat_check->fetch();
                    error_log("Number of categories in database: " . $cat_count->count);

                    // Check if the book was added to "To Kill a Mockingbird"
                    $book_check = $dbh->query("SELECT * FROM tblbooks WHERE BookName LIKE '%Mockingbird%'");
                    $book = $book_check->fetch();
                    if ($book) {
                        error_log("Found 'To Kill a Mockingbird' book: " . print_r($book, true));

                        // Check its category
                        $cat_check = $dbh->query("SELECT * FROM tblcategory WHERE id = " . $book->CatId);
                        $category = $cat_check->fetch();
                        error_log("Book category: " . print_r($category, true));

                        // Check its author
                        $auth_check = $dbh->query("SELECT * FROM tblauthors WHERE id = " . $book->AuthorId);
                        $author = $auth_check->fetch();
                        error_log("Book author: " . print_r($author, true));
                    } else {
                        error_log("'To Kill a Mockingbird' book not found in database");
                    }
                } catch (PDOException $e) {
                    error_log("Error checking database: " . $e->getMessage());
                    throw $e; // Re-throw to be caught by outer catch
                }

                echo "<script>alert('Book added successfully');</script>";
                echo "<script>window.location.href='manage-books.php'</script>";

            } catch (Exception $e) {
                error_log("Outer exception: " . $e->getMessage());
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
            }
        } catch (Exception $e) {
            error_log("Outer exception: " . $e->getMessage());
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
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
        <title>Online Library Management System | Add Book</title>
        <link href="assets/css/bootstrap.css" rel="stylesheet" />
        <link href="assets/css/font-awesome.css" rel="stylesheet" />
        <link href="assets/css/style.css" rel="stylesheet" />
        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    </head>

    <body>
        <?php include('includes/header.php'); ?>
        <div class="content-wrapper">
            <div class="container">
                <div class="row pad-botm">
                    <div class="col-md-12">
                        <h4 class="header-line">Add Book</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">Book Info</div>
                            <div class="panel-body">
                                <form role="form" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label>Book Name<span style="color:red;">*</span></label>
                                        <input class="form-control" type="text" name="bookname" required />
                                    </div>

                                    <div class="form-group">
                                        <label>Category<span style="color:red;">*</span></label>
                                        <select class="form-control" name="category" required>
                                            <option value="">Select Category</option>
                                            <?php
                                            $sql = "SELECT * from tblcategory";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) { ?>
                                                    <option value="<?php echo htmlentities($result->id); ?>">
                                                        <?php echo htmlentities($result->CategoryName); ?>
                                                    </option>
                                                <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Author<span style="color:red;">*</span></label>
                                        <select class="form-control" name="author" required>
                                            <option value="">Select Author</option>
                                            <?php
                                            $sql = "SELECT * from tblauthors";
                                            $query = $dbh->prepare($sql);
                                            $query->execute();
                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                            if ($query->rowCount() > 0) {
                                                foreach ($results as $result) { ?>
                                                    <option value="<?php echo htmlentities($result->id); ?>">
                                                        <?php echo htmlentities($result->AuthorName); ?>
                                                    </option>
                                                <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>ISBN Number<span style="color:red;">*</span></label>
                                        <input class="form-control" type="text" name="isbn" required />
                                    </div>

                                    <div class="form-group">
                                        <label>Book Cover Image<span style="color:red;">*</span></label>
                                        <input class="form-control" type="file" name="bookimg" accept="image/*" required />
                                        <small class="text-muted">Allowed formats: JPG, PNG, GIF</small>
                                    </div>

                                    <div class="form-group">
                                        <label>EPUB File (Optional)</label>
                                        <input class="form-control" type="file" name="epubfile" accept=".epub" />
                                        <small class="text-muted">Upload EPUB format only</small>
                                    </div>

                                    <button type="submit" name="add" class="btn btn-info">Add Book</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('includes/footer.php'); ?>
        <script src="assets/js/jquery-1.10.2.js"></script>
        <script src="assets/js/bootstrap.js"></script>
        <script src="assets/js/custom.js"></script>
    </body>

    </html>
<?php } ?>