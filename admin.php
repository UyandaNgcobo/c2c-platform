<?php
session_start();
require_once 'includes/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

//  STRICT SECURITY GATE
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php?msg=unauthorized");
    exit();
}

$success_msg = "";
$error_msg = "";
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'insights'; // Remembers the tab

//  HANDLE ADMIN POST ACTIONS 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Ban User 
    if ($_POST['action'] === 'ban_user') {
        $target_user_id = (int)$_POST['target_user_id'];
        $ban_reason = trim($_POST['ban_reason']);

        if ($target_user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $stmt_wipe = $conn->prepare("UPDATE listings SET status = 'delisted' WHERE seller_id = ?");
                $stmt_wipe->bind_param("i", $target_user_id);
                $stmt_wipe->execute();

                $notif_title = "Account Suspended";
                $notif_msg = "Your account has been suspended by a moderator. Reason: " . $ban_reason;
                $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $target_user_id, $notif_title, $notif_msg);
                $stmt_notif->execute();

                $success_msg = "User ID #$target_user_id has been banned.";
                $active_tab = 'moderation';
            }
        }
    }

    //  Unban User
    if ($_POST['action'] === 'unban_user') {
        $target_user_id = (int)$_POST['target_user_id'];
        if ($target_user_id > 0) {
            $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $success_msg = "User ID #$target_user_id has been successfully unbanned.";
                $active_tab = 'support';
            }
        }
    }
    
    //  Resolve Support Ticket
    if ($_POST['action'] === 'resolve_ticket') {
        $ticket_id = (int)$_POST['ticket_id'];
        if ($ticket_id > 0) {
            $stmt = $conn->prepare("UPDATE support_tickets SET status = 'resolved' WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            if ($stmt->execute()) {
                $success_msg = "Ticket #$ticket_id has been marked as resolved.";
                $active_tab = 'support';
            }
        }
    }
}

//  FETCH DATA (Insights & Moderation)
// imple Ping
$ping_start = microtime(true);
$conn->query("SELECT 1"); 
$db_ping_ms = round((microtime(true) - $ping_start) * 1000);
$db_status_class = ($db_ping_ms < 100) ? 'status-healthy' : 'status-degraded';
$db_status_text = ($db_ping_ms < 100) ? 'Healthy' : 'Slow';

// Online Users
$online_res = $conn->query("SELECT COUNT(*) as count FROM users WHERE last_active >= NOW() - INTERVAL 15 MINUTE");
$online_users = $online_res->fetch_assoc()['count'];

// Charts 
$chart_sql = "SELECT DATE(created_at) as order_date, COUNT(id) as daily_revenue FROM orders WHERE created_at >= NOW() - INTERVAL 30 DAY GROUP BY DATE(created_at) ORDER BY order_date ASC";
$chart_res = $conn->query($chart_sql);
$dates = []; $revenues = [];
while($row = $chart_res->fetch_assoc()) { $dates[] = $row['order_date']; $revenues[] = $row['daily_revenue']; }

// Moderation Queue 
$reports_query = "SELECT r.*, u.username AS reporter_name FROM reports r LEFT JOIN users u ON r.reporter_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC";
$reports_result = $conn->query($reports_query);

// Support Desk Queue
$tickets_query = "SELECT t.*, u.username, u.email FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE t.status = 'open' ORDER BY t.created_at ASC";
$tickets_result = $conn->query($tickets_query);


//  GLOBAL SEARCH LOGIC (User 360)
$searched_user = null;
if (isset($_GET['search_user']) && !empty(trim($_GET['search_user']))) {
    $active_tab = 'support'; // Force tab open
    $search_term = trim($_GET['search_user']);
    
    $stmt_search = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt_search->bind_param("ss", $search_term, $search_term);
    $stmt_search->execute();
    $searched_user = $stmt_search->get_result()->fetch_assoc();
}

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-dashboard-bg">
    <div class="admin-layout">
        
        <aside class="admin-sidebar">
            <div class="admin-brand">Admin Center</div>
            <ul class="admin-nav">
                <li><a href="#" onclick="switchTab('insights', this)" id="nav-insights">Insights & Health</a></li>
                <li><a href="#" onclick="switchTab('moderation', this)" id="nav-moderation">Moderation Queue</a></li>
                <li><a href="#" onclick="switchTab('support', this)" id="nav-support">Support & Users</a></li>
                <li><a href="index.php" style="margin-top: 2rem;">← Back to Store</a></li>
            </ul>
        </aside>

        <main class="admin-workspace">
            
            <?php if (!empty($success_msg)): ?><div class="admin-alert-success"><?php echo htmlspecialchars($success_msg); ?></div><?php endif; ?>
            <?php if (!empty($error_msg)): ?><div class="admin-alert-success" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>

            <div id="tab-insights" class="admin-tab-content">
                <div class="admin-header"><h2>Platform Insights</h2></div>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <h4>Users Online Now</h4>
                        <div class="metric-value"><?php echo $online_users; ?></div>
                        <span class="status-badge status-healthy">Active in last 15m</span>
                    </div>
                    <div class="metric-card">
                        <h4>Database Health</h4>
                        <div class="metric-value"><?php echo $db_ping_ms; ?> ms</div>
                        <span class="status-badge <?php echo $db_status_class; ?>"><?php echo $db_status_text; ?></span>
                    </div>
                </div>
                <div class="chart-container">
                    <h3 style="margin-bottom: 1rem; color: #1e293b;">Daily Order Volume (Last 30 Days)</h3>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>

            <div id="tab-moderation" class="admin-tab-content">
                <div class="admin-header">
                    <h2>Moderation Queue</h2>
                    <span class="report-count"><?php echo $reports_result->num_rows; ?> Pending Reports</span>
                </div>
                <div class="admin-table-container">
                    <table class="admin-data-table">
                        <thead>
                            <tr><th>ID</th><th>Target</th><th>Reason</th><th>Reporter</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($reports_result->num_rows > 0): while($row = $reports_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if ($row['reported_type'] === 'user'): ?>
                                            <span class="type-badge type-user">User #<?php echo $row['reported_id']; ?></span>
                                        <?php else: ?>
                                            <span class="type-badge type-listing">Item #<?php echo $row['reported_id']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="violation-text"><?php echo htmlspecialchars($row['reason']); ?></div></td>
                                    <td><?php echo htmlspecialchars($row['reporter_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <?php if ($row['reported_type'] === 'user'): ?>
                                            <button class="admin-btn-action" onclick="openBanModal(<?php echo $row['reported_id']; ?>)">Ban</button>
                                        <?php endif; ?>
                                        <button class="admin-btn-action">Dismiss</button>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem;">No pending reports.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tab-support" class="admin-tab-content">
                <div class="admin-header">
                    <h2>Support Desk & CRM</h2>
                </div>

                <div class="chart-container" style="padding: 1.5rem; margin-bottom: 2rem; background: #f8fafc;">
                    <form action="admin.php" method="GET" style="display: flex; gap: 1rem;">
                        <input type="hidden" name="tab" value="support">
                        <input type="text" name="search_user" class="admin-input" placeholder="Search by exact Username or Email..." style="flex: 1;" required value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>">
                        <button type="submit" class="admin-btn-action" style="background: #1e293b; color: white;">Look Up User</button>
                        <?php if($searched_user): ?>
                            <a href="admin.php?tab=support" class="admin-btn-action" style="text-decoration:none; display:flex; align-items:center;">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if(isset($_GET['search_user'])): ?>
                    <?php if($searched_user): ?>
                        <div class="metric-card" style="margin-bottom: 2rem; border-left: 4px solid orange;">
                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($searched_user['username']); ?></h3>
                            <p style="color: #64748b; margin-bottom: 1rem;">
                                Email: <?php echo htmlspecialchars($searched_user['email']); ?> | 
                                Status: <strong style="color: <?php echo $searched_user['is_banned'] == 1 ? '#dc3545' : '#10b981'; ?>;">
                                    <?php echo $searched_user['is_banned'] == 1 ? 'BANNED' : 'ACTIVE'; ?>
                                </strong>
                            </p>
                            
                            <?php if ($searched_user['is_banned'] == 0): ?>
                                <button class="admin-btn-action" style="background: #dc3545; color: white;" onclick="openBanModal(<?php echo $searched_user['id']; ?>)">🔨 Ban User</button>
                            <?php else: ?>
                                <form action="admin.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="unban_user">
                                    <input type="hidden" name="target_user_id" value="<?php echo $searched_user['id']; ?>">
                                    <button type="submit" class="admin-btn-action" style="background: #10b981; color: white;">Unban User</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-alert-success" style="background:#fee2e2;color:#991b1b;">User not found. Check spelling.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <h3 style="margin-bottom: 1rem; color: #1e293b;">Open Tickets (<?php echo $tickets_result->num_rows; ?>)</h3>
                <div class="admin-table-container">
                    <table class="admin-data-table">
                        <thead>
                            <tr><th>User</th><th>Subject</th><th>Message</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($tickets_result->num_rows > 0): while($row = $tickets_result->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight:bold;"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><div class="violation-text" title="<?php echo htmlspecialchars($row['message']); ?>"><?php echo htmlspecialchars($row['message']); ?></div></td>
                                    <td>
                                        <form action="admin.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="resolve_ticket">
                                            <input type="hidden" name="ticket_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="admin-btn-action" style="background: #d1fae5; color: #065f46; border-color: #a7f3d0;">Mark Resolved</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 2rem;">No open tickets. Help Desk is clear!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>
</div>

<div class="admin-modal-overlay" id="banModal" style="display: none;">
    <div class="admin-modal-box">
        <h3 style="margin-bottom: 1rem; color: #1e293b;">Issue User Ban</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Banning a user will immediately lock their account and delist all of their active items.</p>
        <form action="admin.php" method="POST">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="target_user_id" id="modalUserId" value="">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 0.5rem;">Reason for Ban</label>
                <textarea name="ban_reason" class="admin-input" rows="4" required></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="button" class="admin-btn-action" onclick="closeModal()" style="flex: 1;">Cancel</button>
                <button type="submit" class="admin-btn-action" style="flex: 1; background: #dc3545; color: white; border-color: #dc3545;">Confirm Ban</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Initial Load Tab State
    const currentTab = '<?php echo $active_tab; ?>';
    
    // Tab Switching Logic
    function switchTab(tabId, clickedLink = null) {
        document.querySelectorAll('.admin-tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.admin-nav a').forEach(link => link.classList.remove('active-admin-tab'));

        document.getElementById('tab-' + tabId).classList.add('active');
        
        if (clickedLink) {
            clickedLink.classList.add('active-admin-tab');
        } else {
            // If loaded by PHP state, find the correct nav link manually
            const targetLink = document.getElementById('nav-' + tabId);
            if(targetLink) targetLink.classList.add('active-admin-tab');
        }
    }

    // Force the correct tab to open on page load
    document.addEventListener("DOMContentLoaded", function() {
        switchTab(currentTab);
        
        // Chart.js Initialization
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const chartDates = <?php echo json_encode($dates); ?>;
        const chartRevenues = <?php echo json_encode($revenues); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartDates.length > 0 ? chartDates : ['No Data'],
                datasets: [{
                    label: 'Daily Order Volume',
                    data: chartRevenues.length > 0 ? chartRevenues : [0],
                    borderColor: 'orange',
                    backgroundColor: 'rgba(255, 165, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    });

    // Ban Modal Logic
    const banModal = document.getElementById('banModal');
    const modalUserIdInput = document.getElementById('modalUserId');

    function openBanModal(userId) { modalUserIdInput.value = userId; banModal.style.display = 'flex'; }
    function closeModal() { banModal.style.display = 'none'; modalUserIdInput.value = ''; }
</script>

<?php include 'includes/footer.php'; ?>