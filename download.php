<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['path'])) {
    $file = $_POST['path'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="generated.sql"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
