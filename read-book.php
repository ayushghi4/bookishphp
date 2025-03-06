<?php
session_start();
error_reporting(E_ALL); // Changed to show all errors
ini_set('display_errors', 1);
include('includes/config.php');

if(!isset($_SESSION['login']) || strlen($_SESSION['login'])==0) { 
    header('location:index.php');
    exit();
}

if(!isset($_GET['bookid'])) {
    header('location:books.php');
    exit();
}

$bookid = filter_var($_GET['bookid'], FILTER_VALIDATE_INT); // Added input validation
if($bookid === false) {
    header('location:books.php');
    exit();
}

// Get book details
$sql = "SELECT * from tblbooks where id=:bookid";
$query = $dbh->prepare($sql);
$query->bindParam(':bookid', $bookid, PDO::PARAM_INT);
$query->execute();
$bookResult = $query->fetch(PDO::FETCH_OBJ);

if(!$bookResult || empty($bookResult->epub_file_path)) {
    $_SESSION['error'] = "Book not found or no EPUB file available";
    header('location:books.php');
    exit();
}

// Get user's ID
$email = $_SESSION['login'];
$userSql = "SELECT UserId FROM tblusers WHERE EmailId = :email";
$userQuery = $dbh->prepare($userSql);
$userQuery->bindParam(':email', $email, PDO::PARAM_STR);
$userQuery->execute();
$userResult = $userQuery->fetch(PDO::FETCH_OBJ);

if (!$userResult) {
    header('location:logout.php');
    exit();
}

$userId = $userResult->UserId;
$_SESSION['userid'] = $userId;

// Get last reading position
$readingSql = "SELECT LastPage FROM tblreadinghistory WHERE UserId = :userid AND BookId = :bookid ORDER BY ReadDate DESC LIMIT 1";
$readingQuery = $dbh->prepare($readingSql);
$readingQuery->bindParam(':userid', $userId, PDO::PARAM_INT);
$readingQuery->bindParam(':bookid', $bookid, PDO::PARAM_INT);
$readingQuery->execute();
$lastPosition = $readingQuery->fetch(PDO::FETCH_OBJ);

// Store the last position in a JavaScript variable
$lastCfi = $lastPosition ? $lastPosition->LastPage : null;

// Verify if the EPUB file exists
$epubPath = "admin/epubfiles/" . $bookResult->epub_file_path;
if (!file_exists($epubPath)) {
    error_log("EPUB file not found: " . $epubPath);
    $_SESSION['error'] = "EPUB file not found";
    header('location:books.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reading: <?php echo htmlspecialchars($bookResult->BookName);?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.5/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/epubjs@0.3.88/dist/epub.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #333333;
        }
        body.dark-theme {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
        }
        body.sepia-theme {
            --bg-color: #f4ecd8;
            --text-color: #5b4636;
        }
        body, html { margin: 0; padding: 0; height: 100%; }
        body { background: var(--bg-color); color: var(--text-color); transition: all 0.3s; }
        #viewer { width: 100%; height: calc(100vh - 60px); }
        #controls {
            height: 60px;
            background: var(--bg-color);
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .control-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        button {
            padding: 8px 15px;
            border: none;
            background: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        #loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
        }
        #error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff4444;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            display: none;
        }
        #bookmark-btn.active {
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div id="loading">Loading book...</div>
    <div id="error-message"></div>
    <div id="viewer"></div>
    <div id="controls">
        <div class="control-group">
            <button id="prev-btn"><i class="fas fa-chevron-left"></i></button>
            <button id="next-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="control-group">
            <button id="theme-light"><i class="fas fa-sun"></i></button>
            <button id="theme-dark"><i class="fas fa-moon"></i></button>
            <button id="theme-sepia"><i class="fas fa-adjust"></i></button>
            <button id="bookmark-btn"><i class="fas fa-bookmark"></i></button>
            <button onclick="window.location.href='my-reading.php'"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <script>
        let book;
        let rendition;
        let currentTheme = 'light';
        let bookmarks = JSON.parse(localStorage.getItem('bookmarks-<?php echo $bookid; ?>') || '[]');
        const loadingIndicator = document.getElementById('loading');
        const errorMessage = document.getElementById('error-message');

        // Initialize UI controls
        document.getElementById('prev-btn').addEventListener('click', () => {
            if (rendition) rendition.prev();
        });
        
        document.getElementById('next-btn').addEventListener('click', () => {
            if (rendition) rendition.next();
        });

        document.getElementById('theme-light').addEventListener('click', () => toggleTheme('light'));
        document.getElementById('theme-dark').addEventListener('click', () => toggleTheme('dark'));
        document.getElementById('theme-sepia').addEventListener('click', () => toggleTheme('sepia'));
        document.getElementById('bookmark-btn').addEventListener('click', () => {
            if (rendition) {
                const cfi = book.getCurrentLocationCfi();
                toggleBookmark(cfi);
            }
        });

        // Handle keyboard navigation
        document.addEventListener('keyup', (e) => {
            if (!rendition) return;
            if (e.key === 'ArrowLeft') rendition.prev();
            if (e.key === 'ArrowRight') rendition.next();
        });

        window.onload = function() {
            loadingIndicator.style.display = 'block';
            
            var filePath = "<?php echo $epubPath; ?>";
            console.log("Loading book from:", filePath);

            book = ePub(filePath);
            rendition = book.renderTo("viewer", {
                width: "100%",
                height: "100%",
                spread: "none"
            });

            book.ready.then(() => {
                loadingIndicator.style.display = 'none';
                <?php if($lastCfi): ?>
                rendition.display("<?php echo $lastCfi; ?>");
                <?php else: ?>
                rendition.display();
                <?php endif; ?>

                // Load saved theme
                const savedTheme = localStorage.getItem('bookTheme') || 'light';
                toggleTheme(savedTheme);

                // Update bookmark button state
                updateBookmarkButton();
            }).catch(error => {
                console.error("Error loading book:", error);
                loadingIndicator.style.display = 'none';
                errorMessage.textContent = "Error loading book. Please try again.";
                errorMessage.style.display = 'block';
            });

            // Save reading progress when page changes
            rendition.on('relocated', function(location) {
                saveProgress(location.start.cfi);
                updateBookmarkButton();
            });
        };

        function toggleTheme(theme) {
            // Remove all theme classes
            document.body.classList.remove('dark-theme', 'sepia-theme');
            
            // Add new theme class if not light
            if (theme !== 'light') {
                document.body.classList.add(theme + '-theme');
            }
            
            // Update rendition theme
            if (rendition) {
                let themeStyles = {
                    light: { body: { background: '#ffffff', color: '#333333' }},
                    dark: { body: { background: '#1a1a1a', color: '#ffffff' }},
                    sepia: { body: { background: '#f4ecd8', color: '#5b4636' }}
                };
                rendition.themes.register(theme, themeStyles[theme]);
                rendition.themes.select(theme);
            }
            
            // Save theme preference
            localStorage.setItem('bookTheme', theme);
            currentTheme = theme;
        }

        function toggleBookmark(cfi) {
            if (!cfi) return;
            
            const bookmarkIndex = bookmarks.indexOf(cfi);
            if (bookmarkIndex === -1) {
                // Add bookmark
                bookmarks.push(cfi);
                showMessage('Bookmark added');
            } else {
                // Remove bookmark
                bookmarks.splice(bookmarkIndex, 1);
                showMessage('Bookmark removed');
            }
            
            // Save bookmarks to localStorage
            localStorage.setItem('bookmarks-<?php echo $bookid; ?>', JSON.stringify(bookmarks));
            updateBookmarkButton();
        }

        function updateBookmarkButton() {
            const bookmarkBtn = document.getElementById('bookmark-btn');
            const currentCfi = book ? book.getCurrentLocationCfi() : null;
            
            if (currentCfi && bookmarks.includes(currentCfi)) {
                bookmarkBtn.classList.add('active');
                bookmarkBtn.style.color = '#ffd700';
            } else {
                bookmarkBtn.classList.remove('active');
                bookmarkBtn.style.color = '';
            }
        }

        function showMessage(text) {
            errorMessage.textContent = text;
            errorMessage.style.display = 'block';
            errorMessage.style.background = '#28a745';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 2000);
        }

        // Debounced save progress
        const saveProgress = debounce(function(cfi) {
            fetch('save-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'bookid=<?php echo $bookid; ?>&position=' + encodeURIComponent(cfi)
            }).catch(error => console.error('Error saving progress:', error));
        }, 1000);

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html>
