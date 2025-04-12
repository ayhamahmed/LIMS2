<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';
$adminName = $adminFirstName . ' ' . $adminLastName;

// Include the database connection
$pdo = require '../database/db_connection.php';

// Include the activity logger
require '../helpers/activity_logger.php';

// Replace the existing confirmation handling code
if (isset($_POST['confirm_return'])) {
    $log_id = $_POST['log_id'];
    $book_id = $_POST['book_id'];
    $user_id = $_POST['user_id'];

    try {
        $pdo->beginTransaction();

        // Update borrowed_books table
        $stmt = $pdo->prepare("
            UPDATE borrowed_books 
            SET return_date = CURRENT_TIMESTAMP 
            WHERE book_id = ? AND user_id = ? AND return_date IS NULL
        ");
        $stmt->execute([$book_id, $user_id]);

        // Update log status
        $stmt = $pdo->prepare("
            UPDATE activity_logs 
            SET status = 'completed' 
            WHERE log_id = ?
        ");
        $stmt->execute([$log_id]);

        $pdo->commit();

        // Set success message in session instead of using alert
        $_SESSION['return_success'] = true;
        header('Location: catalog.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error confirming return: " . $e->getMessage());
        $_SESSION['return_error'] = true;
        header('Location: ../catalog.php');
        exit();
    }
}

// Fetch logs from database outside of the HTML
try {
    $stmt = $pdo->query('
        SELECT 
            l.log_id,
            l.action_type,
            l.description,
            l.performed_by,
            l.timestamp,
            l.status,
            l.related_id,
            CASE 
                WHEN l.action_type IN ("RETURN_REQUEST", "BOOK_RETURN", "BORROW") 
                THEN (
                    SELECT user_id 
                    FROM borrowed_books 
                    WHERE book_id = l.related_id 
                    ORDER BY borrow_date DESC 
                    LIMIT 1
                )
                ELSE NULL
            END as user_id
        FROM activity_logs l
        ORDER BY l.timestamp DESC
        LIMIT 100
    ');
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching logs: " . $e->getMessage());
    $logs = [];
}

// Add this after your existing query
try {
    $debug_stmt = $pdo->query("
        SELECT * FROM activity_logs 
        WHERE action_type = 'RETURN_REQUEST' 
        AND status = 'pending' 
        LIMIT 1
    ");
    $debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    if ($debug_result) {
        error_log("Found pending return request: " . print_r($debug_result, true));
    } else {
        error_log("No pending return requests found");
    }
} catch (PDOException $e) {
    error_log("Debug query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book King - Catalog</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/catalog.css">
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Book King Logo">
        </div>
        <div class="nav-group">
            <a href="../admin/admin-dashboard.php" class="nav-item">
                <div class="icon">
                    <img src="../images/element-2 2.svg" alt="Dashboard" width="24" height="24">
                </div>
                <div class="text">Dashboard</div>
            </a>
            <a href="#" class="nav-item active">
                <div class="icon">
                    <img src="../images/Vector.svg" alt="Catalog" width="20" height="20">
                </div>
                <div class="text">Catalog</div>
            </a>
            <a href="../admin/book-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/book.png" alt="Books" width="24" height="24">
                </div>
                <div class="text">Books</div>
            </a>
            <a href="../admin/user-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/people 3.png" alt="Users" width="24" height="24">
                </div>
                <div class="text">Users</div>
            </a>
            <a href="../admin/branch-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/buildings-2 1.png" alt="Branches" width="24" height="24">
                </div>
                <div class="text">Branches</div>
            </a>
            <a href="../admin/borrowers-management.php" class="nav-item">
                <div class="icon">
                    <img src="../images/user.png" alt="Borrowers" width="24" height="24">
                </div>
                <div class="text">Borrowers</div>
            </a>

        </div>
        <a href="../admin/admin-logout.php" class="nav-item logout">
            <div class="icon">
                <img src="../images/logout 3.png" alt="Log Out" width="24" height="24">
            </div>
            <div class="text">Log Out</div>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <div class="user-profile">
                    <div class="user-icon">
                        <img src="../images/user.png" alt="User Icon">
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($adminFirstName . ' ' . $adminLastName) ?></div>
                        <div class="user-role">Admin</div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="datetime-profile">
                    <div class="datetime">
                        <div class="time" id="current-time"></div>
                        <div class="date" id="current-date"></div>
                    </div>
                    <div class="vertical-line"></div>
                    <div class="settings-icon">
                        <img src="../images/Vector.jpg" alt="Settings">
                    </div>
                </div>
            </div>
        </div>

        <div class="page-title">History / Logs</div>

        <?php if (isset($_SESSION['return_success'])): ?>
            <div class="success-message">Book return confirmed successfully</div>
            <?php unset($_SESSION['return_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['return_error'])): ?>
            <div class="error-message">Error confirming return</div>
            <?php unset($_SESSION['return_error']); ?>
        <?php endif; ?>

        <div class="content-table">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Performed By</th>
                            <th>Status</th>
                            <th style="text-align: center;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($log['timestamp'])) ?></td>
                                <td>
                                    <span class="action-type action-type-<?= strtolower($log['action_type']) ?>">
                                        <?= getActivityIcon($log['action_type']) ?>
                                        <?= htmlspecialchars($log['action_type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td><?= htmlspecialchars($log['performed_by']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($log['status']) ?>">
                                        <?= htmlspecialchars($log['status']) ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn view-details"
                                        data-log-id="<?= $log['log_id'] ?>"
                                        data-related-id="<?= $log['related_id'] ?>"
                                        data-user-id="<?= $log['user_id'] ?>">
                                        <img src="../images/btn view.svg" alt="View Details">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No activity logs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2 style="color: #B07154;">Confirm Book Return</h2>
            <p style="color: #495057;">Are you sure you want to confirm this book return?</p>
            <form id="confirmReturnForm" method="POST">
                <input type="hidden" name="log_id" id="confirmLogId">
                <input type="hidden" name="book_id" id="confirmBookId">
                <input type="hidden" name="user_id" id="confirmUserId">
                <input type="hidden" name="confirm_return" value="1">
                <div class="button-group">
                    <button type="submit" class="confirm-btn" style="background-color: #22C55E !important;">Confirm Return</button>
                    <button type="button" class="cancel-btn" style="background-color: #EF4444 !important;" onclick="closeConfirmationModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateDateTime() {
            const now = new Date();

            // Update time
            const timeDiv = document.getElementById('current-time');
            timeDiv.textContent = now.toLocaleString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            // Update date
            const dateDiv = document.getElementById('current-date');
            dateDiv.textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
        }

        // Update immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const logId = this.getAttribute('data-log-id');
                const relatedId = this.getAttribute('data-related-id');
                const userId = this.getAttribute('data-user-id');
                const row = this.closest('tr');
                const actionType = row.querySelector('.action-type').textContent.replace(/\s+/g, ' ').trim();
                const status = row.querySelector('.status-badge').textContent.trim();

                console.log('Action Type:', actionType); // Debug log
                console.log('Status:', status); // Debug log

                if (actionType.includes('RETURN_REQUEST') && status === 'pending') {
                    const modal = document.getElementById('confirmationModal');
                    document.getElementById('confirmLogId').value = logId;
                    document.getElementById('confirmBookId').value = relatedId;
                    document.getElementById('confirmUserId').value = userId;
                    modal.style.display = 'block';
                }
            });
        });

        // Add event listener for clicking outside modal
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                closeConfirmationModal();
            }
        });

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }
    </script>
</body>

</html>