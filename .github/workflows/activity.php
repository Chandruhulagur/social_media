<?php
session_start();
include('includes/db.php');

$user_id = $_SESSION['user_id'];

// Fetch all notifications (read and unread)
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count = 0;
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if (!$row['is_read']) $unread_count++;
}

// Mark notifications as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'mark_read') {
        $notification_ids = $_POST['notification_ids'] ?? [];
        if (!empty($notification_ids)) {
            $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
            $types = str_repeat('i', count($notification_ids));
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$notification_ids);
            $stmt->execute();
        }
        echo json_encode(['status' => 'success', 'unread_count' => 0]);
    } elseif ($_POST['action'] == 'clear_all') {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'unread_count' => 0]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | SocialApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4361ee;
        --primary-light: #4895ef;
        --secondary: #3f37c9;
        --dark: #1f2937;
        --light: #f8f9fa;
        --danger: #f72585;
        --success: #4cc9f0;
        --gray: #6b7280;
        --gray-light: #e5e7eb;
    }
    
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fb;
        color: var(--dark);
        line-height: 1.6;
    }
    
    .notification-container {
        max-width: 600px;
        margin: 2rem auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .notification-header {
        padding: 1.5rem;
        background: var(--primary);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .notification-header h2 {
        font-weight: 600;
        font-size: 1.5rem;
    }
    
    .badge {
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: bold;
    }
    
    .notification-actions {
        display: flex;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: var(--light);
        border-bottom: 1px solid var(--gray-light);
    }
    
    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--secondary);
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid var(--gray);
        color: var(--gray);
    }
    
    .btn-outline:hover {
        border-color: var(--dark);
        color: var(--dark);
    }
    
    .btn-danger {
        background: var(--danger);
        color: white;
    }
    
    .btn-danger:hover {
        background: #d1146a;
    }
    
    #notifications {
        list-style: none;
        max-height: 600px;
        overflow-y: auto;
    }
    
    .notification {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-light);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .notification:hover {
        background: rgba(67, 97, 238, 0.05);
    }
    
    .notification.unread {
        background: rgba(67, 97, 238, 0.08);
        border-left: 3px solid var(--primary);
    }
    
    .notification.read {
        opacity: 0.9;
    }
    
    .notification-content {
        display: flex;
        gap: 1rem;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        flex-shrink: 0;
    }
    
    .notification-details {
        flex-grow: 1;
    }
    
    .notification-message {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .notification-meta {
        display: flex;
        justify-content: space-between;
        color: var(--gray);
        font-size: 0.85rem;
    }
    
    .notification-time {
        font-size: 0.8rem;
    }
    
    .notification-type {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .type-like {
        background: rgba(244, 63, 94, 0.1);
        color: #f43f5e;
    }
    
    .type-comment {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .type-follow {
        background: rgba(22, 163, 74, 0.1);
        color: #16a34a;
    }
    
    .empty-state {
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--gray);
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--gray-light);
    }
    
    .empty-state p {
        margin-top: 0.5rem;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .notification {
        animation: fadeIn 0.3s ease forwards;
    }
    
    .notification:nth-child(1) { animation-delay: 0.1s; }
    .notification:nth-child(2) { animation-delay: 0.2s; }
    .notification:nth-child(3) { animation-delay: 0.3s; }
    /* Add more as needed */
    
    @media (max-width: 640px) {
        .notification-container {
            margin: 0;
            border-radius: 0;
            min-height: 100vh;
        }
    }
    </style>
</head>
<body>
    <div class="notification-container">
        <div class="notification-header">
            <h2>Notifications</h2>
            <?php if ($unread_count > 0): ?>
                <div class="badge"><?= $unread_count ?></div>
            <?php endif; ?>
        </div>
        
        <div class="notification-actions">
            <button class="btn btn-primary" id="mark-read">
                <i class="fas fa-check-circle"></i> Mark All Read
            </button>
            <button class="btn btn-outline" id="clear-all">
                <i class="fas fa-trash-alt"></i> Clear All
            </button>
        </div>
        
        <ul id="notifications">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="far fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>When you get notifications, they'll appear here</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <li class="notification <?= $notification['is_read'] ? 'read' : 'unread' ?>" 
                        data-id="<?= $notification['id'] ?>"
                        onclick="window.location.href='<?= $notification['link'] ?? '#' ?>'">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <?php 
                                $icon = 'fa-bell';
                                $type_class = '';
                                if (strpos($notification['message'], 'like') !== false) {
                                    $icon = 'fa-heart';
                                    $type_class = 'type-like';
                                } elseif (strpos($notification['message'], 'comment') !== false) {
                                    $icon = 'fa-comment';
                                    $type_class = 'type-comment';
                                } elseif (strpos($notification['message'], 'follow') !== false) {
                                    $icon = 'fa-user-plus';
                                    $type_class = 'type-follow';
                                }
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <div class="notification-details">
                                <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                    </span>
                                    <?php if ($type_class): ?>
                                        <span class="notification-type <?= $type_class ?>">
                                            <?= strtoupper(explode('-', $type_class)[1]) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const markReadBtn = document.getElementById('mark-read');
        const clearAllBtn = document.getElementById('clear-all');
        const notifications = document.querySelectorAll('.notification.unread');
        
        // Mark all as read
        markReadBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const notificationIds = Array.from(notifications).map(n => n.dataset.id);
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_ids=${JSON.stringify(notificationIds)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    notifications.forEach(notification => {
                        notification.classList.remove('unread');
                        notification.classList.add('read');
                    });
                    document.querySelector('.badge')?.remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'Marked as read',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        });
        
        // Clear all notifications
        clearAllBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            Swal.fire({
                title: 'Clear all notifications?',
                text: "You won't be able to undo this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4361ee',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=clear_all'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('notifications').innerHTML = `
                                <div class="empty-state">
                                    <i class="far fa-bell-slash"></i>
                                    <h3>No notifications yet</h3>
                                    <p>When you get notifications, they'll appear here</p>
                                </div>
                            `;
                            document.querySelector('.badge')?.remove();
                        }
                    });
                }
            });
        });
        
        // Mark individual notification as read when clicked
        document.querySelectorAll('.notification.unread').forEach(notification => {
            notification.addEventListener('click', function() {
                if (this.classList.contains('unread')) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=mark_read&notification_ids=${JSON.stringify([this.dataset.id])}`
                    });
                    
                    this.classList.remove('unread');
                    this.classList.add('read');
                    
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }
                }
            });
        });
    });
    </script>
</body>
</html>