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

// Handle branch addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $branchName = trim($_POST['branch_name'] ?? '');
    $branchLocation = trim($_POST['branch_location'] ?? '');

    $errors = [];

    // Validate inputs
    if (empty($branchName)) {
        $errors[] = "Branch name is required";
    }

    if (empty($branchLocation)) {
        $errors[] = "Branch location is required";
    }

    // If no errors, insert the branch
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO branches (branch_name, branch_location) VALUES (?, ?)");
            $stmt->execute([$branchName, $branchLocation]);

            // Redirect to refresh the page
            header('Location: ../admin/branch-management.php?success=1');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get search query if any
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch branches from database with search functionality
try {
    if (!empty($searchQuery)) {
        $stmt = $pdo->prepare('SELECT branch_id, branch_name, branch_location FROM branches 
                              WHERE branch_id LIKE ? OR branch_name LIKE ? 
                              ORDER BY branch_id');
        $searchParam = "%$searchQuery%";
        $stmt->execute([$searchParam, $searchParam]);
    } else {
        $stmt = $pdo->query('SELECT branch_id, branch_name, branch_location FROM branches ORDER BY branch_id');
    }
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching branches: " . $e->getMessage());
    $branches = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book King - Branch Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/branch-management.css">
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
            <a href="../admin./catalog.php" class="nav-item">
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
            <a href="#" class="nav-item active">
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

        <div class="page-title"></div>

        <!-- Display success message if branch was added -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Branch added successfully!</div>
        <?php endif; ?>

        <!-- Display errors if any -->
        <?php if (!empty($errors ?? [])): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="actions-container">
            <button class="add-book-btn" id="openAddBranchModal">
                <div class="add-book-icon"></div>
                <div class="add-book-text">Add Branch</div>
            </button>
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by ID or Branch Name">
                </div>
            </div>
        </div>

        <div class="content-table">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Branch ID</th>
                            <th>Branch Name</th>
                            <th>Location</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($branches)): ?>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?= htmlspecialchars($branch['branch_id']) ?></td>
                                    <td><?= htmlspecialchars($branch['branch_name']) ?></td>
                                    <td><?= htmlspecialchars($branch['branch_location']) ?></td>
                                    <td class="action-cell">
                                        <button class="action-btn edit-btn" data-id="<?= htmlspecialchars($branch['branch_id']) ?>" 
                                            data-name="<?= htmlspecialchars($branch['branch_name']) ?>" 
                                            data-location="<?= htmlspecialchars($branch['branch_location']) ?>">
                                            <img src="../images/btn edit.png" alt="Edit">
                                        </button>
                                        <button class="action-btn delete-btn" data-id="<?= htmlspecialchars($branch['branch_id']) ?>">
                                            <img src="../images/btn Delete.png" alt="Delete">
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No branches found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Move the edit modal outside the loop -->
    <div id="editBranchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Branch</h3>
                <span class="close">&times;</span>
            </div>
            <form id="editBranchForm">
                <input type="hidden" id="edit_branch_id" name="branch_id">
                <div class="form-group">
                    <label for="edit_branch_name">Branch Name</label>
                    <input type="text" id="edit_branch_name" name="branch_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_branch_location">Branch Location</label>
                    <input type="text" id="edit_branch_location" name="branch_location" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Update Branch</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move all JavaScript to the bottom of the file -->
    <script>
        // Edit functionality
        const editModal = document.getElementById('editBranchModal');
        const editForm = document.getElementById('editBranchForm');
        const closeButtons = document.getElementsByClassName('close');

        // Add click event to all edit buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const branchId = button.dataset.id;
                const branchName = button.dataset.name;
                const branchLocation = button.dataset.location;

                // Populate the edit form
                document.getElementById('edit_branch_id').value = branchId;
                document.getElementById('edit_branch_name').value = branchName;
                document.getElementById('edit_branch_location').value = branchLocation;

                // Show the modal
                editModal.style.display = 'block';
            });
        });

        // Close modal when clicking the close button
        Array.from(closeButtons).forEach(button => {
            button.addEventListener('click', () => {
                editModal.style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        };

        // Handle edit form submission
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const formData = new FormData(editForm);
                const response = await fetch('update_branch.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Branch updated successfully!');
                    window.location.reload();
                } else {
                    alert(result.message || 'Failed to update branch');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the branch');
            }
        });
    </script>

    <!-- Add Branch Modal -->
    <div id="addBranchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Branch</h3>
                <span class="close">&times;</span>
            </div>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="branch_name">Branch Name</label>
                    <input type="text" id="branch_name" name="branch_name" required>
                </div>
                <div class="form-group">
                    <label for="branch_location">Branch Location</label>
                    <input type="text" id="branch_location" name="branch_location" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_branch" class="btn-submit">Add Branch</button>
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

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('tbody');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            let hasVisibleRows = false;

            Array.from(rows).forEach(row => {
                const id = row.cells[0]?.textContent.toLowerCase() || '';
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const location = row.cells[2]?.textContent.toLowerCase() || '';

                if (id.includes(searchTerm) || name.includes(searchTerm) || location.includes(searchTerm)) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show "No branches found" if no matches
            const noResultsRow = tableBody.querySelector('tr[data-no-results]');
            if (!hasVisibleRows) {
                if (!noResultsRow) {
                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-no-results', 'true');
                    newRow.innerHTML = '<td colspan="4" style="text-align: center;">No branches found</td>';
                    tableBody.appendChild(newRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        });
    </script>

    <script>
        // Add Branch Modal Functionality
        const addModal = document.getElementById('addBranchModal');
        const addBtn = document.getElementById('openAddBranchModal');
        const closeBtn = addModal.querySelector('.close');

        // Open modal when clicking the Add Branch button
        addBtn.onclick = function() {
            addModal.style.display = 'block';
        }

        // Close modal when clicking the X button
        closeBtn.onclick = function() {
            addModal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == addModal) {
                addModal.style.display = 'none';
            }
        }

        // Form validation
        const addBranchForm = addModal.querySelector('form');
        addBranchForm.onsubmit = function(e) {
            const branchName = document.getElementById('branch_name').value.trim();
            const branchLocation = document.getElementById('branch_location').value.trim();

            if (!branchName || !branchLocation) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            return true;
        }
    </script>
</body>

</html>