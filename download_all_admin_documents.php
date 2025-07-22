<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin_name']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

$zip = new ZipArchive();
$zipFile = sys_get_temp_dir() . '/admin_documents_' . time() . '.zip';
if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
    exit('Could not create ZIP file.');
}

$result = $conn->query("SELECT file_name, file_path FROM admin_documents");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        $fileName = $row['file_name'];
        if (file_exists($filePath)) {
            $zip->addFile($filePath, $fileName);
        }
    }
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="admin_documents.zip"');
header('Content-Length: ' . filesize($zipFile));
readfile($zipFile);
@unlink($zipFile);
exit(); 