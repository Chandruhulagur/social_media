document.addEventListener('DOMContentLoaded', () => {
    const markReadButton = document.getElementById('mark-read');
    const notificationsList = document.getElementById('notifications');

    // Mark all notifications as read
    markReadButton.addEventListener('click', () => {
        const unreadNotifications = document.querySelectorAll('.notification.unread');
        const notificationIds = Array.from(unreadNotifications).map(notification => notification.dataset.id);

        fetch('activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notification_ids: notificationIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                unreadNotifications.forEach(notification => {
                    notification.classList.remove('unread');
                    notification.classList.add('read');
                });
            }
        });
    });

    // Poll for new notifications every 30 seconds
    setInterval(() => {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.new_notifications) {
                    data.notifications.forEach(notification => {
                        const li = document.createElement('li');
                        li.classList.add('notification', 'unread');
                        li.dataset.id = notification.id;
                        li.innerHTML = `<p>${notification.message}</p><span>${notification.created_at}</span>`;
                        notificationsList.insertBefore(li, notificationsList.firstChild);
                    });
                }
            });
    }, 30000);
});
