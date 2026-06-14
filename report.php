<?php 
session_start();
include 'includes/db.php';

//  Must be logged in to report (prevents anonymous spam)
if (!isset($_SESSION['user_id'])) {
    // Save the intended destination so they can return after logging in
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?msg=login_to_report");
    exit();
}

$reporter_id = $_SESSION['user_id'];
$reported_type = isset($_GET['type']) && in_array($_GET['type'], ['user', 'listing']) ? $_GET['type'] : null;
$reported_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reported_type || !$reported_id) {
    die("<h2 style='text-align:center; margin-top:5rem;'>Invalid report request.</h2>");
}

// Fetch some basic info just so the user knows exactly who/what they are reporting
$target_name = "Unknown";
if ($reported_type === 'user') {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $reported_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $target_name = $res->fetch_assoc()['username'];
} else {
    $stmt = $conn->prepare("SELECT title FROM listings WHERE id = ?");
    $stmt->bind_param("i", $reported_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) $target_name = $res->fetch_assoc()['title'];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $details = trim($_POST['details']);
    
    // Combine the category and details for the admin to read
    $full_reason = "[$category] " . $details;
    
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_type, reported_id, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isis", $reporter_id, $reported_type, $reported_id, $full_reason);
    
    if ($stmt->execute()) {
        $success_msg = "Your report has been submitted to our moderation team. Thank you for keeping our community safe.";
    } else {
        $error_msg = "Something went wrong. Please try again later.";
    }
}

include 'includes/header.php'; 
?>

<div class="main-wrapper">
    <main class="content-feed" style="display: flex; justify-content: center; align-items: flex-start; padding-top: 3rem; min-height: 70vh;">
        
        <div class="report-form-container">
            <div class="report-header">
                <h2>File a Report</h2>
                <p>You are reporting the <?php echo $reported_type; ?>: <strong><?php echo htmlspecialchars($target_name); ?></strong></p>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="report-success-state">
                    <span style="font-size: 3rem;"></span>
                    <h3>Report Received</h3>
                    <p><?php echo $success_msg; ?></p>
                    <a href="index.php" class="primary-btn" style="display:inline-block; margin-top: 1rem;">Return Home</a>
                </div>
            <?php else: ?>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form action="report.php?type=<?php echo $reported_type; ?>&id=<?php echo $reported_id; ?>" method="POST" class="report-form">
                    
                    <div class="form-group">
                        <label>Reason for Reporting</label>
                        <select name="category" required>
                            <option value="">-- Select a reason --</option>
                            <?php if ($reported_type === 'user'): ?>
                                <option value="Suspicious/Fraudulent Behavior">Suspicious or Fraudulent Behavior</option>
                                <option value="Harassment/Abusive Language">Harassment or Abusive Language</option>
                                <option value="Selling Prohibited Items">Selling Prohibited Items</option>
                                <option value="Fake Profile/Impersonation">Fake Profile or Impersonation</option>
                            <?php else: ?>
                                <option value="Counterfeit/Fake Item">Counterfeit or Fake Item</option>
                                <option value="Prohibited/Illegal Item">Prohibited or Illegal Item</option>
                                <option value="Misleading Description">Misleading Description or Photos</option>
                                <option value="Price Gouging/Scam">Price Gouging or Scam Listing</option>
                            <?php endif; ?>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Additional Details</label>
                        <textarea name="details" rows="5" placeholder="Please provide specific details to help our moderators investigate this issue..." required></textarea>
                    </div>

                    <div class="report-warning">
                        <small>False or malicious reports may result in account suspension. Please ensure your report violates our community guidelines.</small>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="button" class="secondary-btn" onclick="history.back()" style="flex: 1;">Cancel</button>
                        <button type="submit" class="primary-btn" style="flex: 1; background: #dc3545; border-color: #dc3545;">Submit Report</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>