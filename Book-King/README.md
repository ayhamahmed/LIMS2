# Book-King Project

## Overview
The Book-King project is a web application that allows users to log in using their credentials. It connects to a MySQL database to verify user credentials stored in the "admin" table.

## Project Structure
```
Book-King
├── database
│   └── db_connection.php
├── login.php
└── README.md
```

## Setup Instructions

1. **Clone the Repository**
   Clone the repository to your local machine using:
   ```
   git clone <repository-url>
   ```

2. **Database Configuration**
   - Ensure you have a MySQL server running.
   - Create a database named `lims`.
   - Create a table named `admin` with the following structure:
     ```sql
     CREATE TABLE admin (
         id INT AUTO_INCREMENT PRIMARY KEY,
         username VARCHAR(50) NOT NULL,
         password VARCHAR(255) NOT NULL
     );
     ```
   - Insert sample data into the `admin` table for testing.

3. **Database Connection**
   - The `db_connection.php` file in the `database` directory establishes a connection to the `lims` database using PHP's PDO or MySQLi. Ensure that the database credentials are correctly set in this file.

4. **Running the Application**
   - Open the `login.php` file in your web browser.
   - Enter your username and password to log in.

## Usage
- The application is designed for user authentication. Users can log in using their credentials stored in the `admin` table.
- Upon successful login, users will be granted access to the application.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License.