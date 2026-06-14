<?php 
session_start();
require_once 'includes/db.php';

//  Kick out guests
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'account.php';
    header("Location: login.php");
    exit(); 
}

$user_id = $_SESSION['user_id'];
$is_banned = $_SESSION['is_banned'];
$success_msg = '';
$error_msg = '';

//process apeals and reports
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_ticket') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // If they are banned, forcibly tag the subject line so the admin knows it's an appeal
    if ($is_banned == 1) {
        $subject = "[APPEAL] Account Suspension Review";
    }

    if (!empty($subject) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $subject, $message);
        if ($stmt->execute()) {
            $success_msg = "Your message has been securely sent to our Trust & Safety team.";
        } else {
            $error_msg = "Database error. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}


// Fetch their ticket history
$tickets_stmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$tickets_stmt->bind_param("i", $user_id);
$tickets_stmt->execute();
$tickets_result = $tickets_stmt->get_result();

// Fetch their recent notifications so that they know they are banned
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

include 'includes/header.php'; 
?>

<div class="main-wrapper">
    <main class="content-feed" style="padding: 2rem;">
        
        <?php if (!empty($success_msg)): ?>
            <div class="admin-alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #a7f3d0;">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="admin-alert-success" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid #fecaca;">
             <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="account-container">

            <?php 
            //ghost mode for the guys who are banned
            if ($is_banned == 1): 
            ?>
                <div style="background: #fff; border: 2px solid #dc3545; border-radius: 8px; padding: 3rem; text-align: center; box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);">
                    <h1 style="color: #dc3545; font-size: 2.5rem; margin-bottom: 1rem;">Account Suspended</h1>
                    <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                        Your access to the marketplace has been restricted due to a violation of our Trust & Safety guidelines. All of your active listings have been temporarily delisted.
                    </p>

                    <?php if ($notif_result->num_rows > 0): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 8px; text-align: left; margin-bottom: 2rem;">
                            <h3 style="margin-bottom: 1rem; color: #1e293b;">Recent Notices:</h3>
                            <?php while($notif = $notif_result->fetch_assoc()): ?>
                                <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                                    <strong><?php echo htmlspecialchars($notif['title']); ?></strong><br>
                                    <span style="color: #64748b;"><?php echo htmlspecialchars($notif['message']); ?></span>
                                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;"><?php echo date('M j, Y', strtotime($notif['created_at'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>

                    <h3 style="margin-bottom: 1rem; color: #1e293b; text-align: left;">File an Appeal</h3>
                    <form action="account.php" method="POST" style="text-align: left;">
                        <input type="hidden" name="action" value="submit_ticket">
                        <input type="hidden" name="subject" value="Appeal"> <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Explanation & Appeal</label>
                            <textarea name="message" rows="5" required style="width: 100%; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" placeholder="Please explain the situation and why your account should be reinstated..."></textarea>
                        </div>
                        
                        <button type="submit" style="background: #1e293b; color: #fff; padding: 1rem 2rem; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%;">Submit Appeal to Admin</button>
                    </form>
                </div>

            <?php 
            
            //normal account dashboard
            else: 
            ?>
                <div class="account-header">
                    <h2>Your Account</h2>
                    <p>Manage your orders, security, and personal data</p>
                </div>

                <div class="account-grid" style="margin-bottom: 3rem;">
                    <a href="#" class="account-card"><div class="ac-icon">👤</div><div class="ac-text"><h3>Personal Profile</h3><p>View or edit your display name, email, and bio</p></div></a>
                    <a href="#" class="account-card"><div class="ac-icon">🔒</div><div class="ac-text"><h3>Login & Security</h3><p>Update your password and secure your account</p></div></a>
                    <a href="#" class="account-card"><div class="ac-icon">📦</div><div class="ac-text"><h3>Your Orders</h3><p>Track your purchases, view receipts, and manage returns</p></div></a>
                    <a href="#" class="account-card"><div class="ac-icon">💳</div><div class="ac-text"><h3>Payments</h3><p>Manage payment methods</p></div></a>
                    <a href="#" class="account-card"><div class="ac-icon">❤️</div><div class="ac-text"><h3>Your Wishlist</h3><p>View items you are watching or saving for later</p></div></a>
                    <a href="#support-section" class="account-card"><div class="ac-icon">🎧</div><div class="ac-text"><h3>Contact Support</h3><p>Open a ticket with our help desk</p></div></a>
                </div>

                <div id="support-section" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem; margin-bottom: 3rem;">
                    <h3 style="margin-bottom: 1.5rem; color: #1e293b;">Submit a Support Ticket</h3>
                    <form action="account.php" method="POST">
                        <input type="hidden" name="action" value="submit_ticket">
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Subject</label>
                            <select name="subject" required style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                <option value="">Select an issue...</option>
                                <option value="Where is my order?">Where is my order?</option>
                                <option value="Issue with a Seller">Issue with a Seller</option>
                                <option value="Payment / Refund Issue">Payment / Refund Issue</option>
                                <option value="Report a Bug">Report a Bug</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem;">Message</label>
                            <textarea name="message" rows="4" required style="width: 100%; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" placeholder="Describe your issue in detail..."></textarea>
                        </div>
                        <button type="submit" style="background: orange; color: #fff; padding: 0.8rem 2rem; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Send Message</button>
                    </form>
                </div>

            <?php endif; ?>

            <?php if ($tickets_result->num_rows > 0): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: #1e293b;">Your Support History</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #cbd5e1; text-align: left;">
                                <th style="padding: 1rem 0; color: #64748b;">Date</th>
                                <th style="padding: 1rem 0; color: #64748b;">Subject</th>
                                <th style="padding: 1rem 0; color: #64748b;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($ticket = $tickets_result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 1rem 0; color: #334155;"><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></td>
                                    <td style="padding: 1rem 0; font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td style="padding: 1rem 0;">
                                        <?php if ($ticket['status'] === 'resolved'): ?>
                                            <span style="background: #d1fae5; color: #065f46; padding: 0.2rem 0.6rem; border-radius: 50px; font-size: 0.8rem; font-weight: bold;">Resolved</span>
                                        <?php else: ?>
                                            <span style="background: #fef3c7; color: #b45309; padding: 0.2rem 0.6rem; border-radius: 50px; font-size: 0.8rem; font-weight: bold;">Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>