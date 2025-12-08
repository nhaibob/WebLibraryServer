<?php
// 1. Database Configuration
$servername = "localhost";
$username = "lib_admin";
$password = "Admin@123456"; 
$dbname = "library_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- LOGIC: HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Use prepared statement for security
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Redirect to clear the URL query string (Prevent re-delete on refresh)
        header("Location: index.php?msg=deleted");
        exit();
    } else {
        $message = "Error deleting record: " . $conn->error;
        $msg_type = "error";
    }
    $stmt->close();
}

// Variables for Form
$title = ""; $author = ""; $isbn = "";
$message = ""; $msg_type = "";

// Check for success message from redirect (Delete action)
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = "Book removed successfully!";
    $msg_type = "success";
}

// --- LOGIC: HANDLE ADD NEW BOOK ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    
    if(!empty($title) && !empty($author) && !empty($isbn)) {
        $title_safe = $conn->real_escape_string($title);
        $author_safe = $conn->real_escape_string($author);
        $isbn_safe = $conn->real_escape_string($isbn);

        $sql_insert = "INSERT INTO books (title, author, isbn) VALUES ('$title_safe', '$author_safe', '$isbn_safe')";
        
        if ($conn->query($sql_insert) === TRUE) {
            $message = "Book added successfully!";
            $msg_type = "success";
            $title = ""; $author = ""; $isbn = ""; // Clear form
        } else {
            $message = "Error: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// --- LOGIC: FETCH DATA ---
$sql = "SELECT * FROM books ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Group 18</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background-color: #f4f4f9; color: #333; }
        h1 { text-align: center; color: #2c3e50; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; transition: border-color 0.3s; }
        input:focus { border-color: #3498db; outline: none; }
        
        /* Buttons */
        .btn-add { background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-add:hover { background-color: #2980b9; }
        
        .btn-delete { background-color: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 14px; border: none; cursor: pointer; }
        .btn-delete:hover { background-color: #c0392b; }

        /* Alerts & Table */
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2c3e50; color: white; text-transform: uppercase; font-size: 14px; }
        tr:hover { background-color: #f1f1f1; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .available { background-color: #d4edda; color: #155724; }
        .borrowed { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>📚 Library Management System</h1>
    
    <div class="container">
        <h3>Add New Book</h3>
        <?php if ($message): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="index.php">
            <div class="form-group">
                <label for="title">Book Title <span style="color:red">*</span>:</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>" placeholder="Enter book title...">
            </div>
            <div class="form-group">
                <label for="author">Author <span style="color:red">*</span>:</label>
                <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($author); ?>" placeholder="Enter author name...">
            </div>
            <div class="form-group">
                <label for="isbn">ISBN <span style="color:red">*</span>:</label>
                <input type="text" id="isbn" name="isbn" required value="<?php echo htmlspecialchars($isbn); ?>" placeholder="Enter ISBN code...">
            </div>
            <button type="submit" class="btn-add">Add Book</button>
        </form>
    </div>

    <div class="container">
        <h3>Current Inventory</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Status</th>
                    <th>Action</th> </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = ($row["status"] == 'available') ? 'available' : 'borrowed';
                        echo "<tr>
                                <td>" . $row["id"] . "</td>
                                <td><strong>" . htmlspecialchars($row["title"]) . "</strong></td>
                                <td>" . htmlspecialchars($row["author"]) . "</td>
                                <td>" . htmlspecialchars($row["isbn"]) . "</td>
                                <td><span class='status-badge $statusClass'>" . ucfirst($row["status"]) . "</span></td>
                                <td>
                                    <a href='index.php?delete=" . $row["id"] . "' 
                                       class='btn-delete' 
                                       onclick='return confirm(\"Are you sure you want to remove this book?\");'>
                                       Remove
                                    </a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No books found.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
