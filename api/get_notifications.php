<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    // Récupération des notifications non lues
    $stmt = $pdo->prepare("
        SELECT n.*, nt.name as type_name, nt.icon
        FROM notifications n
        JOIN notification_types nt ON n.type_id = nt.id
        WHERE n.user_id = ? 
        AND n.read_at IS NULL
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // Récupération du nombre total de notifications non lues
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count
        FROM notifications
        WHERE user_id = ? AND read_at IS NULL
    ");
    $stmt->execute([$user_id]);
    $count = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $count['unread_count']
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des notifications : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des notifications'
    ]);
}
?> 