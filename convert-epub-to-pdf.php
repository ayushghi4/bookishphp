<?php
require_once('includes/config.php');

// Ensure user is logged in
if(!$_SESSION['login']) {
    header('location:index.php');
    exit();
}

// Check if file was uploaded
if(!isset($_FILES['file'])) {
    http_response_code(400);
    exit('No file provided');
}

// CloudConvert API key - you'll need to replace this with your actual API key
$apiKey = 'YOUR_CLOUDCONVERT_API_KEY';

// Initialize cURL
$ch = curl_init();

// Setup the CloudConvert API request
$data = array(
    'tasks' => array(
        array(
            'name' => 'import/upload',
            'result' => true
        ),
        array(
            'name' => 'convert',
            'input' => array('type' => 'import/upload'),
            'output_format' => 'pdf'
        ),
        array(
            'name' => 'export/url',
            'input' => array('type' => 'convert'),
            'inline' => false,
            'archive_multiple_files' => false
        )
    ),
    'tag' => 'bookish-conversion'
);

// Set cURL options
curl_setopt_array($ch, array(
    CURLOPT_URL => 'https://api.cloudconvert.com/v2/jobs',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    )
));

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if($httpCode !== 200) {
    http_response_code(500);
    exit('Error creating conversion job');
}

$job = json_decode($response, true);

// Upload the file
$uploadTask = $job['data']['tasks'][0];
$ch = curl_init();

// Prepare file upload
$file = new CURLFile($_FILES['file']['tmp_name'], 'application/epub+zip', $_FILES['file']['name']);

curl_setopt_array($ch, array(
    CURLOPT_URL => $uploadTask['result']['form']['url'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => array_merge(
        $uploadTask['result']['form']['parameters'],
        array('file' => $file)
    )
));

// Execute upload
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if($httpCode !== 201) {
    http_response_code(500);
    exit('Error uploading file');
}

// Wait for conversion to complete
$jobId = $job['data']['id'];
$maxAttempts = 30;
$attempts = 0;

while($attempts < $maxAttempts) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://api.cloudconvert.com/v2/jobs/' . $jobId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $apiKey
        )
    ));

    $response = curl_exec($ch);
    $job = json_decode($response, true);

    if($job['data']['status'] === 'finished') {
        // Get the download URL
        $exportTask = end($job['data']['tasks']);
        $pdfUrl = $exportTask['result']['files'][0]['url'];

        // Download the PDF
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $pdfUrl,
            CURLOPT_RETURNTRANSFER => true
        ));

        $pdfContent = curl_exec($ch);

        // Send PDF to client
        header('Content-Type: application/pdf');
        echo $pdfContent;
        exit();
    }

    if($job['data']['status'] === 'error') {
        http_response_code(500);
        exit('Conversion failed');
    }

    $attempts++;
    sleep(1);
}

http_response_code(504);
exit('Conversion timeout');
?>
