<?php
session_start();

// The Bouncer: Redirect to login if the user ID isn't set in the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | C2C Platform</title>
    <style>
        body { background-color: #ffffff; color: orange; font-family: sans-serif; padding: 50px; }
        .nav { border-bottom: 2px solid orange; padding-bottom: 10px; margin-bottom: 20px; }
        a { color: orange; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="nav">
        <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        | <a href="logout.php">Secure Logout</a>
    </div>

    <h1>Welcome to your Dashboard</h1>
    [cite_start]<p>This is the central hub where you will manage your <strong>inventory, pricing, and bargaining</strong>[cite: 22, 43].</p>

    <div style="border: 1px dashed orange; padding: 20px; margin-top: 20px;">
        <h3>Your Activity</h3>
        <ul>
            <li>Items you are selling</li>
            <li>Offers you have made</li>
            [cite_start]<li>Current Trust Score: [cite: 79]</li>
        </ul>
    </div>

</body>
</html>