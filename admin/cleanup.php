<?php
$directories = [
    'bookimg' => __DIR__ . '/bookimg',
    'epubfiles' => __DIR__ . '/epubfiles'
];

foreach ($directories as $dir) {
    $files = array_diff(scandir($dir), ['.', '..']);
    $hashes = [];

    foreach ($files as $file) {
        $filePath = $dir . '/' . $file;
        $fileHash = md5_file($filePath);

        if (isset($hashes[$fileHash])) {
            // Duplicate found, delete the file
            unlink($filePath);
            echo "Deleted duplicate file: $filePath\n";
        } else {
            $hashes[$fileHash] = $filePath;
        }
    }
}
?>