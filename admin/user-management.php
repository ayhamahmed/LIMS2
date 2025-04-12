<?php
// Start session at the beginning of the file
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

// Include the database connection
$pdo = require '../database/db_connection.php';

// Fetch users from database with search functionality
try {
    $search = $_GET['search'] ?? '';
    $query = 'SELECT * FROM users';
    $params = [];

    if (!empty($search)) {
        $query .= ' WHERE user_id LIKE :search 
                    OR FirstName LIKE :search_name 
                    OR LastName LIKE :search_name
                    OR username LIKE :search_name';
        $params['search'] = $search . '%';
        $params['search_name'] = '%' . $search . '%';
    }

    $query .= ' ORDER BY user_id';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book King - User Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user-management.css">
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
            <a href="../admin/catalog.php" class="nav-item">
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
            <a href="../admin/user-management.php" class="nav-item active">
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

        <div class="page-title">User Management</div>

        <div class="actions-container">
            <button class="add-book-btn">
                <div class="add-book-icon"></div>
                <div class="add-book-text">Add User</div>
            </button>
            <div class="search-container">
                <div class="search-wrapper">
                    <div class="search-icon"></div>
                    <form method="GET" action="" id="searchForm">
                        <input type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search by ID or Name"
                            value="<?= htmlspecialchars($search ?? '') ?>">
                    </form>
                </div>
            </div>
        </div>

        <div class="content-table">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['user_id']) ?></td>
                                    <td><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="action-cell">
                                        <button class="action-btn edit-btn" data-userid="<?= $user['user_id'] ?>">
                                            <img src="../images/btn edit.png" alt="Edit">
                                        </button>
                                        <button class="action-btn delete-btn" data-userid="<?= $user['user_id'] ?>">
                                            <img src="../images/btn Delete.png" alt="Delete">
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New User</h2>
            <form id="addUserForm">
                <div class="form-group">
                    <input type="text" name="firstName" required placeholder="First Name">
                </div>
                <div class="form-group">
                    <input type="text" name="lastName" required placeholder="Last Name">
                </div>
                <div class="form-group">
                    <input type="email" name="email" required placeholder="Email">
                </div>
                <div class="form-group">
                    <input type="text" name="username" required placeholder="Username">
                </div>
                <div class="form-group">
                    <input type="password" name="password" required placeholder="Password">
                </div>
                <div class="form-group">
                    <input type="text" name="contactNo" required placeholder="Contact Number">
                </div>
                <button type="submit" class="submit-btn">Add User</button>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit User</h2>
            <form id="editUserForm">
                <input type="hidden" name="user_id">
                <div class="form-group">
                    <input type="text" name="firstName" required placeholder="First Name">
                </div>
                <div class="form-group">
                    <input type="text" name="lastName" required placeholder="Last Name">
                </div>
                <div class="form-group">
                    <input type="email" name="email" required placeholder="Email">
                </div>
                <div class="form-group">
                    <input type="text" name="username" required placeholder="Username">
                </div>
                <div class="form-group">
                    <input type="text" name="contactNo" required placeholder="Contact Number">
                </div>
                <button type="submit" class="submit-btn">Update User</button>
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
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const searchForm = document.getElementById('searchForm');
            let typingTimer;

            searchInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    searchForm.submit();
                }, 500);
            });

            searchInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Cancel the timer if the user presses a key
            searchInput.addEventListener('keydown', function() {
                clearTimeout(typingTimer);
            });

            // Add User button
            const addUserBtn = document.querySelector('.add-book-btn');
            const addUserModal = document.getElementById('addUserModal');
            const addUserForm = document.getElementById('addUserForm');

            addUserBtn.addEventListener('click', function() {
                addUserModal.style.display = 'block';
            });

            // Edit buttons
            const editBtns = document.querySelectorAll('.edit-btn');
            const editModal = document.getElementById('editUserModal');
            const editForm = document.getElementById('editUserForm');

            editBtns.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = this.dataset.userid;
                    try {
                        const response = await fetch(`get_user.php?id=${userId}`);
                        if (!response.ok) throw new Error('Failed to fetch user data');
                        
                        const userData = await response.json();
                        if (userData.error) throw new Error(userData.error);

                        // Populate form
                        editForm.elements['user_id'].value = userData.user_id;
                        editForm.elements['firstName'].value = userData.FirstName;
                        editForm.elements['lastName'].value = userData.LastName;
                        editForm.elements['email'].value = userData.email;
                        editForm.elements['username'].value = userData.username;
                        editForm.elements['contactNo'].value = userData.contactNo;

                        editModal.style.display = 'block';
                    } catch (error) {
                        console.error('Error:', error);
                        alert(error.message);
                    }
                });
            });

            // Delete buttons
            const deleteBtns = document.querySelectorAll('.delete-btn');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (confirm('Are you sure you want to delete this user?')) {
                        const userId = this.dataset.userid;
                        try {
                            const response = await fetch('delete_user.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `user_id=${userId}`
                            });

                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                // Show success message and refresh
                                window.location.reload();
                            } else {
                                throw new Error(data.message || 'Failed to delete user');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            // Only show alert if there's a real error
                            if (!error.message.includes('Failed to parse JSON')) {
                                alert(error.message);
                            }
                        }
                    }
                });
            });

            // Close modal buttons
            const closeBtns = document.querySelectorAll('.close');
            closeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    addUserModal.style.display = 'none';
                    editModal.style.display = 'none';
                });
            });

            // Form submissions
            addUserForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                try {
                    const response = await fetch('add_user.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        alert('User added successfully!');
                        addUserModal.style.display = 'none'; // Hide the modal
                        this.reset(); // Reset the form
                        window.location.reload(); // Reload the page to show new user
                    } else {
                        alert(data.message || 'Failed to add user');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while adding the user');
                }
            });

            editForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                try {
                    const response = await fetch('update_user.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('User updated successfully!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to update user');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user');
                }
            });
        });
    </script>
</body>

</html>