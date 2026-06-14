<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'listings';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Fetch Data
if ($tab === 'sales') {
    $stmt = $conn->prepare("SELECT oi.quantity, oi.created_at as sale_date, l.title, u.username as buyer_name 
                            FROM order_items oi
                            JOIN listings l ON oi.listing_id = l.id
                            JOIN orders o ON oi.order_id = o.id
                            JOIN users u ON o.user_id = u.id
                            WHERE l.seller_id = ? 
                            ORDER BY oi.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result();
    
    $count_res = $conn->prepare("SELECT count(*) as total FROM order_items oi JOIN listings l ON oi.listing_id = l.id WHERE l.seller_id = ?");
    $count_res->bind_param("i", $user_id);
    $count_res->execute();
    $total_items = $count_res->get_result()->fetch_assoc()['total'];
} else {
    $stmt = $conn->prepare("SELECT * FROM listings WHERE seller_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result();
    
    $count_res = $conn->prepare("SELECT count(*) as total FROM listings WHERE seller_id = ?");
    $count_res->bind_param("i", $user_id);
    $count_res->execute();
    $total_items = $count_res->get_result()->fetch_assoc()['total'];
}

$total_pages = ceil($total_items / $limit);
?>

<div class="main-wrapper" style="padding: 2rem 3rem; background: #f8fafc; min-height: 80vh;">
    
    <div class="admin-header">
        <h2>Dashboard</h2>
    </div>

    <div class="tab-container">
        <a href="?tab=listings" class="tab-link <?php echo $tab === 'listings' ? 'active' : ''; ?>">My Listings</a>
        <a href="?tab=sales" class="tab-link <?php echo $tab === 'sales' ? 'active' : ''; ?>">Sales History</a>
    </div>

    <div class="admin-table-container">
        <table class="admin-data-table">
            <thead>
                <?php if ($tab === 'listings'): ?>
                    <tr><th>Product</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                <?php else: ?>
                    <tr><th>Product</th><th>Buyer</th><th>Quantity</th><th>Date</th></tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php while ($row = $data->fetch_assoc()): ?>
                    <tr>
                        <?php if ($tab === 'listings'): ?>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td>R <?php echo number_format($row['buy_now_price'], 2); ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td>
                                <form action="delete-listing.php" method="POST" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="listing_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="delete-btn-modern">Delete</button>
                                </form>
                            </td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                            <td style="color: #e11d48; font-weight: bold;">-<?php echo $row['quantity']; ?></td>
                            <td><?php echo date("d M, Y H:i", strtotime($row['sale_date'])); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>