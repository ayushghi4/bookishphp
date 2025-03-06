<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>Bookish - Online Book Library | Admin Panel</title>
    <!-- BOOTSTRAP CORE STYLE  -->
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <!-- FONT AWESOME STYLE  -->
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <!-- CUSTOM STYLE  -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(strlen($_SESSION['alogin'])==0) {   
    header('location:index.php');
    exit();
}
?>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <img src="assets/img/logo.png" style="height: 30px; margin-right: 10px;" alt="Logo">
            </a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Books <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="add-book.php">Add Book</a></li>
                        <li><a href="manage-books.php">Manage Books</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Categories <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="add-category.php">Add Category</a></li>
                        <li><a href="manage-categories.php">Manage Categories</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Authors <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="add-author.php">Add Author</a></li>
                        <li><a href="manage-authors.php">Manage Authors</a></li>
                    </ul>
                </li>
                <li><a href="manage-reviews.php">Reviews</a></li>
                <li><a href="reg-users.php">Users</a></li>
            </ul>
            <div class="navbar-right">
                <a href="logout.php" class="btn btn-danger btn-sm" style="margin: 10px; padding: 5px 10px; font-size: 12px;">LOG ME OUT</a>
            </div>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>

<!-- Initialize Bootstrap Dropdowns -->
<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script>
$(document).ready(function(){
    $('.dropdown-toggle').dropdown();
});
</script>

<!-- Initialize Bootstrap Dropdowns -->
<script>
$(document).ready(function(){
    $('.dropdown-toggle').dropdown();
    
    // Add hover functionality for desktop
    if(window.innerWidth > 768) {
        $('.dropdown').hover(
            function() { $(this).addClass('open'); },
            function() { $(this).removeClass('open'); }
        );
    }
});
</script>