<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for EPUB content
header('Content-Type: application/epub+zip');
header('Accept-Ranges: bytes');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Range');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the EPUB file path
$epubFile = 'admin/epubfiles/d2b6327c18bda7f98bbad49c68659014.epub';
$filePath = __DIR__ . '/' . $epubFile;

// Check if file exists
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

$fileSize = filesize($filePath);

// Handle range requests
$start = 0;
$end = $fileSize - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    $ranges = array_map('trim', explode(',', $_SERVER['HTTP_RANGE']));
    $ranges = array_filter($ranges);
    
    if (count($ranges) > 0) {
        $range = str_replace('bytes=', '', $ranges[0]);
        list($start, $end) = array_map('trim', explode('-', $range));
        
        if ($end === '') {
            $end = $fileSize - 1;
        }
        
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes */$fileSize");
            exit;
        }
        
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
        header('Content-Length: ' . ($end - $start + 1));
    }
} else {
    header('Content-Length: ' . $fileSize);
}

// Open file and seek to start position
$fp = fopen($filePath, 'rb');
fseek($fp, $start);

// Output file content
$bytesToRead = $end - $start + 1;
while ($bytesToRead > 0 && !feof($fp)) {
    $buffer = fread($fp, min(1024 * 16, $bytesToRead));
    echo $buffer;
    flush();
    $bytesToRead -= strlen($buffer);
}

fclose($fp);
?>
