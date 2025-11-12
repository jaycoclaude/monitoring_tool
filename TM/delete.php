<?php
require_once '../includes/auth.php';
require_once 'data.php';

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $deleted = deleteTask($id);
    $_SESSION['flash'] = [
        'type' => $deleted ? 'success' : 'error',
        'msg' => $deleted ? 'Task deleted successfully!' : 'Failed to delete task.'
    ];
}

header('Location: index.php');
exit;
