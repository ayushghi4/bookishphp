<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit();
}

$readerid = $_SESSION['rdid'];
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Reading History | Bookish</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .history-item {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: #fff;
            transition: all 0.3s ease;
        }
        .history-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
        .progress {
            margin-top: 10px;
            height: 5px;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-started { background: #e3f2fd; color: #1976d2; }
        .status-in-progress { background: #fff3e0; color: #f57c00; }
        .status-completed { background: #e8f5e9; color: #388e3c; }
        .time-spent {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <div class="content-wrapper">
        <div class="container">
            <div class="row pad-botm">
                <div class="col-md-12">
                    <h4 class="header-line">Reading History</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?php
                    $sql = "SELECT 
                            h.*, 
                            b.BookName,
                            b.id as bookid,
                            b.bookImage,
                            a.AuthorName,
                            CASE 
                                WHEN h.TimeSpent < 60 THEN CONCAT(h.TimeSpent, ' seconds')
                                WHEN h.TimeSpent < 3600 THEN CONCAT(FLOOR(h.TimeSpent/60), ' minutes')
                                ELSE CONCAT(FLOOR(h.TimeSpent/3600), ' hours ', FLOOR((h.TimeSpent%3600)/60), ' minutes')
                            END as ReadingTime
                            FROM tblreadinghistory h
                            JOIN tblbooks b ON h.BookId = b.id
                            LEFT JOIN tblauthors a ON b.AuthorId = a.id
                            WHERE h.UserId = :readerid
                            ORDER BY h.ReadDate DESC";
                    
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':readerid', $readerid, PDO::PARAM_INT);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);

                    if ($query->rowCount() > 0) {
                        foreach ($results as $result) {
                            $statusClass = 'status-' . $result->Status;
                            $progressPercent = ($result->LastPage / $result->TotalPages) * 100;
                            ?>
                            <div class="history-item">
                                <div class="row">
                                    <div class="col-md-2">
                                        <img src="admin/bookimg/<?php echo htmlentities($result->bookImage);?>" 
                                             alt="" width="100">
                                    </div>
                                    <div class="col-md-8">
                                        <h4>
                                            <a href="read-book.php?bookid=<?php echo htmlentities($result->bookid);?>">
                                                <?php echo htmlentities($result->BookName);?>
                                            </a>
                                        </h4>
                                        <p>by <?php echo htmlentities($result->AuthorName);?></p>
                                        <p class="time-spent">
                                            <i class="fa fa-clock-o"></i> 
                                            <?php echo $result->ReadingTime;?> spent reading
                                        </p>
                                        <p>
                                            Last read: <?php echo date('F j, Y g:i A', strtotime($result->ReadDate));?>
                                        </p>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped active" 
                                                 role="progressbar"
                                                 style="width: <?php echo $progressPercent;?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <span class="status-badge <?php echo $statusClass;?>">
                                            <?php echo ucfirst($result->Status);?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="alert alert-info">No reading history found. Start reading books to see your history!</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php include('includes/footer.php');?>
    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>
</body>
</html>
