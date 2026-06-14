<?php
session_start();
require_once 'includes/db.php';

// If they are already logged in, redirect them to the home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Grab and cleanup inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Some Basic Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error_msg = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Check if Username or Email already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt_check->bind_param("ss", $email, $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_msg = "An account with that email or username already exists.";
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            //  Insert the new user into the database
            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                // Success! Automatically log them in
                $_SESSION['user_id'] = $stmt_insert->insert_id;
                $_SESSION['username'] = $username;
                
                // Redirect to homepage 
                header("Location: index.php?msg=registered");
                exit();
            } else {
                $error_msg = "Database error. Please try again later.";
            }
        }
        $stmt_check->close();
    }
}

// Include header AFTER processing redirects
include 'includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-card">
        <span class="logo-text">C2C Marketplace</span>
        <h2>Create an Account</h2>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="login-btn">Sign Up</button>
        </form>
        
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            <p style="color: #666;">Already have an account? <a href="login.php" style="color: orange; text-decoration: none; font-weight: bold;">Sign In</a></p>
        </div>
        
        <a href="index.php" class="back-home">← Back to Home</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>