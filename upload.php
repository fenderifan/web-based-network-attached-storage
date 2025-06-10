<?php
/**
 * Chunked File Upload Receiver
 *
 * This script handles files uploaded in chunks, reassembles them,
 * and places them in the final destination.
 */

// --- CONFIGURATION ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Set a reasonable execution time
require_once __DIR__ . '/config.php'; 
$settings = load_settings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

// --- PATH SETUP ---
$baseDir = __DIR__ . '/files';
$tempDir = __DIR__ . '/files/.tmp';

if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// --- VALIDATE REQUEST ---
if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMessage = 'Upload error. Code: ' . ($_FILES['fileToUpload']['error'] ?? 'Unknown');
    error_log($errorMessage);
    echo $errorMessage;
    exit;
}

// --- GET METADATA ---
$chunkNumber = isset($_POST['chunkNumber']) ? (int)$_POST['chunkNumber'] : 0;
$totalChunks = isset($_POST['totalChunks']) ? (int)$_POST['totalChunks'] : 0;
$originalName = isset($_POST['fileName']) ? $_POST['fileName'] : $_FILES['fileToUpload']['name'];
$targetFolder = isset($_POST['targetFolder']) ? trim($_POST['targetFolder'], '/') : '';

// --- SANITIZE AND PREPARE PATHS ---
$safeFileName = preg_replace("/([^a-zA-Z0-9\._\s-]+)/", "", basename($originalName));
$uploadIdentifier = md5($safeFileName); // Unique identifier based on the file name

$finalDirectory = $baseDir . ($targetFolder ? "/$targetFolder" : '');
if (!file_exists($finalDirectory)) {
    mkdir($finalDirectory, 0777, true);
}

// --- PROCESS THE CHUNK ---
$chunkPath = $tempDir . '/' . $uploadIdentifier . '.part' . $chunkNumber;
if (!move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $chunkPath)) {
    http_response_code(500);
    $errorMessage = "Failed to move uploaded chunk to temporary directory.";
    error_log($errorMessage);
    echo $errorMessage;
    exit;
}

// --- ASSEMBLE THE FILE (if it's the last chunk) ---
$isLastChunk = ($chunkNumber === $totalChunks - 1);

if ($isLastChunk) {
    $finalDestinationPath = $finalDirectory . '/' . $safeFileName;

    // --- Auto-rename logic if the final file already exists ---
    $counter = 1;
    $nameWithoutExt = pathinfo($safeFileName, PATHINFO_FILENAME);
    $extension = pathinfo($safeFileName, PATHINFO_EXTENSION);
    while (file_exists($finalDestinationPath)) {
        $newName = $nameWithoutExt . " ($counter)" . ($extension ? '.' . $extension : '');
        $finalDestinationPath = $finalDirectory . '/' . $newName;
        $counter++;
    }

    $finalFile = fopen($finalDestinationPath, 'wb');
    if (!$finalFile) {
        http_response_code(500);
        echo "Failed to open final file for writing.";
        exit;
    }

    // Loop through all chunks and append them using efficient streams
    for ($i = 0; $i < $totalChunks; $i++) {
        $partPath = $tempDir . '/' . $uploadIdentifier . '.part' . $i;
        $chunkStream = fopen($partPath, 'rb');

        if ($chunkStream === false) {
            fclose($finalFile);
            unlink($finalDestinationPath); // Clean up failed assembly
            http_response_code(500);
            echo "Failed to read chunk #$i.";
            exit;
        }
        
        stream_copy_to_stream($chunkStream, $finalFile); // Efficiently append chunk
        
        fclose($chunkStream);
        unlink($partPath); // Clean up the chunk file
    }

    fclose($finalFile);
    
    echo "Upload complete: " . basename($finalDestinationPath);
} else {
    // Acknowledge receipt of the chunk
    http_response_code(200);
    echo "Chunk #$chunkNumber of $totalChunks received.";
}