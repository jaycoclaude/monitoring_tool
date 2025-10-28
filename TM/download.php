<?php
// File preview/download handler
require_once 'data.php';

$fileName = isset($_GET['file']) ? $_GET['file'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'preview'; // preview | download

if (empty($fileName)) {
    header('Location: index.php');
    exit;
}

// Security: Only allow files from uploads directory
$filePath = 'uploads/' . basename($fileName);

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found.');
}

// Determine mime type
$fileType = mime_content_type($filePath);
if (!$fileType) {
    $fileType = 'application/octet-stream';
}

// Common headers
header('Content-Type: ' . $fileType);
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');

// Content disposition based on mode
if ($mode === 'download') {
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
} else {
    // Inline preview by default
    header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
}

// Output file
readfile($filePath);
exit;
?>
