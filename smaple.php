<?php
// config.php - Configuration for the database and session management
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'my_database');

session_start();

// Database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function loginUser($username, $password) {
    global $conn;
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $user, $hashedPassword);
        
        if ($stmt->fetch()) {
            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $user;
                return true;
            }
        }
    }
    return false;
}

function registerUser($username, $password) {
    global $conn;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $hashedPassword);
        return $stmt->execute();
    }
    return false;
}

function getPosts() {
    global $conn;
    $sql = "SELECT posts.id, posts.title, posts.content, users.username FROM posts
            JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC";
    $result = $conn->query($sql);
    return $result;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (loginUser($username, $password)) {
        header('Location: SamplePage.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: SamplePage.php');
    exit();
}

// Handle form submission to create new posts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $sql = "INSERT INTO posts (title, content, user_id) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssi", $title, $content, $_SESSION['user_id']);
        $stmt->execute();
    }
}

// Fetch posts to display
$posts = getPosts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMKEL CONSULTING LLC.</title>
</head>
<body>
    <h1>LMKEL CONSULTING LLC.</h1>

    <?php if (!isLoggedIn()): ?>
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <p style="color:red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="SamplePage.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required><br>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required><br>
            <button type="submit" name="login">Login</button>
        </form>

    <?php else: ?>
        <p>Hello, <?php echo $_SESSION['username']; ?>!</p>
        <a href="?logout=true">Logout</a>

        <h2>Post a New Entry</h2>
        <form action="SamplePage.php" method="POST">
            <label for="title">Title:</label><br>
            <input type="text" name="title" id="title" required><br>
            <label for="content">Content:</label><br>
            <textarea name="content" id="content" required></textarea><br>
            <button type="submit" name="submit_post">Submit Post</button>
        </form>

        <h2>Recent Posts</h2>
        <?php if ($posts->num_rows > 0): ?>
            <?php while ($post = $posts->fetch_assoc()): ?>
                <div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <p><small>Posted by <?php echo htmlspecialchars($post['username']); ?> on <?php echo $post['created_at']; ?></small></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No posts available.</p>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html>
