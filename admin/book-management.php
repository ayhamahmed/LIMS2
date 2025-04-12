<?php
// Start session at the very beginning of the file
session_start();

// At the top of the file, after session_start()
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/admin-login.php');
    exit();
}

// Get admin name from session
$adminFirstName = $_SESSION['admin_first_name'] ?? 'Admin';
$adminLastName = $_SESSION['admin_last_name'] ?? '';

// Include the database connection
$pdo = require '../database/db_connection.php';

// Fetch books from database outside of the HTML
try {
    $stmt = $pdo->query('SELECT * FROM books ORDER BY book_id');
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $books = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book King - Book Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/book-management.css">
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
            <a href="../admin/book-management.php" class="nav-item active">
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

        <div class="page-title"></div>

        <div class="actions-container">
            <button class="add-book-btn">
                <div class="add-book-icon"></div>
                <div class="add-book-text">Add Book</div>
            </button>
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by ID or Type">
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
                            <th>Type</th>
                            <th>Language</th>
                            <th>Availability</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= htmlspecialchars($book['book_id']) ?></td>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['type']) ?></td>
                                <td><?= htmlspecialchars($book['language']) ?></td>
                                <td class="status-<?= strtolower($book['availability']) ?>">
                                    <?= htmlspecialchars($book['availability']) ?>
                                </td>
                                <td class="action-cell">
                                    <button class="action-btn edit-btn" data-book-id="<?= $book['book_id'] ?>"
                                        data-title="<?= htmlspecialchars($book['title']) ?>"
                                        data-author="<?= htmlspecialchars($book['author']) ?>"
                                        data-type="<?= htmlspecialchars($book['type']) ?>"
                                        data-language="<?= htmlspecialchars($book['language']) ?>">
                                        <img src="../images/btn edit.png" alt="Edit">
                                    </button>
                                    <button class="action-btn delete-btn" data-book-id="<?= $book['book_id'] ?>">
                                        <img src="../images/btn Delete.png" alt="Delete">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No books found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div id="addBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Book</h2>
                <span class="close">&times;</span>
            </div>
            <form id="addBookForm" method="POST">
                <div class="form-group">
                    <label for="title">Book Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Romance">Romance</option>
                        <option value="Educational">Educational</option>
                        <option value="Drama">Drama</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Reference">Reference</option>
                        <option value="Magazine">Magazine</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language" required>
                        <option value="">Select Language</option>
                        <option value="English">English</option>
                        <option value="Filipino">Filipino</option>
                        <option value="Arabic">Arabic</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="submit-btn">Add Book</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Book</h2>
                <span class="close-edit">&times;</span>
            </div>
            <form id="editBookForm" method="POST">
                <input type="hidden" id="edit_book_id" name="book_id">
                <div class="form-group">
                    <label for="edit_title">Book Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_author">Author</label>
                    <input type="text" id="edit_author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <select id="edit_type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Educational">Educational</option>
                        <option value="Drama">Drama</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Reference">Reference</option>
                        <option value="Magazine">Magazine</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_language">Language</label>
                    <select id="edit_language" name="language" required>
                        <option value="">Select Language</option>
                        <option value="English">English</option>
                        <option value="Filipino">Filipino</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="submit-btn">Update Book</button>
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

        // Get modal elements
        const modal = document.getElementById('addBookModal');
        const addBookBtn = document.querySelector('.add-book-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.querySelector('.cancel-btn');
        const form = document.getElementById('addBookForm');

        // Open modal
        addBookBtn.onclick = function() {
            modal.style.display = 'block';
        }

        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission
        form.onsubmit = async function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            try {
                const response = await fetch('add-book.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success popup
                    const successPopup = document.createElement('div');
                    successPopup.className = 'modal success-modal';
                    successPopup.innerHTML = `
                        <div class="modal-content success-content">
                            <div class="success-header">
                                <img src="images/logo1.jpg" alt="Logo" class="success-logo">
                                <h2>Success!</h2>
                            </div>
                            <p>Book added successfully!</p>
                            <div class="success-footer">
                                <img src="images/logo3.png" alt="Footer Logo" class="footer-logo">
                            </div>
                        </div>
                    `;
                    document.body.appendChild(successPopup);

                    // Close add book modal
                    modal.style.display = 'none';

                    // Clear form
                    form.reset();

                    // Remove success popup and refresh page after delay
                    setTimeout(() => {
                        successPopup.remove();
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error adding book: ' + error.message);
            }
        }

        // Add after existing modal code
        const editModal = document.getElementById('editBookModal');
        const editForm = document.getElementById('editBookForm');
        const closeEditBtn = document.querySelector('.close-edit');

        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.onclick = function() {
                const bookId = this.dataset.bookId;
                const title = this.dataset.title;
                const author = this.dataset.author;
                const type = this.dataset.type;
                const language = this.dataset.language;

                // Populate the edit form
                document.getElementById('edit_book_id').value = bookId;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_author').value = author;
                document.getElementById('edit_type').value = type;
                document.getElementById('edit_language').value = language;

                editModal.style.display = 'block';
            };
        });

        // Close edit modal
        closeEditBtn.onclick = function() {
            editModal.style.display = 'none';
        }

        // Handle edit form submission
        editForm.onsubmit = async function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);

            try {
                const response = await fetch('edit-book.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                if (result.success) {
                    alert('Book updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to update book'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating book: ' + error.message);
            }
        };

        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.onclick = async function() {
                if (confirm('Are you sure you want to delete this book?')) {
                    const bookId = this.dataset.bookId;
                    try {
                        const response = await fetch('delete-book.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                book_id: bookId
                            })
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const result = await response.json();
                        if (result.success) {
                            alert('Book deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (result.message || 'Failed to delete book'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error deleting book: ' + error.message);
                    }
                }
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('tbody');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            let hasVisibleRows = false;

            // Remove existing "no results" row if it exists
            const existingNoResults = document.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // Filter rows
            Array.from(rows).forEach(row => {
                // Skip the "no results" row if it exists
                if (row.classList.contains('no-results')) return;

                const cells = row.getElementsByTagName('td');
                const rowText = Array.from(cells).reduce((text, cell) => {
                    return text + ' ' + cell.textContent.toLowerCase();
                }, '');

                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show "No results" message if no matches
            if (!hasVisibleRows) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = '<td colspan="6" style="text-align: center;">No matching books found</td>';
                tableBody.appendChild(noResultsRow);
            }
        });
    </script>
</body>

</html>