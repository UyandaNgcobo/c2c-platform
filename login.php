<?php
session_start();
include 'includes/db.php'; 

// If they are already logged in, redirect them to the home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

   if ($user && password_verify($password, $user['password'])) {
        // Log the user in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Grab the actual is_admin column directly from your DB
        $_SESSION['is_admin'] = (isset($user['is_admin']) && $user['is_admin'] == 1) ? 1 : 0;
        
        // Grab the ban status
        $_SESSION['is_banned'] = (int)($user['is_banned'] ?? 0); 
        
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}

// Load the universal header, it will also load auth.css along with it
include 'includes/header.php'; 
?>

<div class="login-wrapper">
    <div class="login-card">
        <span class="logo-text">C2C PLATFORM</span>
        <h2>Welcome Back</h2>
        
        <?php if($error_message): ?>
            <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            <p style="color: #666;">New to the marketplace? <a href="register.php" style="color: orange; text-decoration: none; font-weight: bold;">Create an Account</a></p>
        </div>

        <a href="index.php" class="back-home">← Back to Marketplace</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>