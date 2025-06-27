<?php
// components/alerts.php - הודעות הצלחה ושגיאה

function showAlert($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-danger';
    echo "<div class='alert {$class}'>{$message}</div>";
}

// הצגת הודעות מ-GET parameters
if (isset($_GET['success'])) {
    showAlert($_GET['success'], 'success');
}

if (isset($_GET['error'])) {
    showAlert($_GET['error'], 'error');
}

// הצגת הודעות מהסשן
if (isset($_SESSION['success_message'])) {
    showAlert($_SESSION['success_message'], 'success');
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    showAlert($_SESSION['error_message'], 'error');
    unset($_SESSION['error_message']);
}
?>