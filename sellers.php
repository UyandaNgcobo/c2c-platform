<?php 
session_start();
include 'includes/db.php';
include 'includes/header.php'; 

$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$results = null;

if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    
    $sql = "SELECT u.id, u.username, COUNT(l.id) as active_items 
            FROM users u 
            LEFT JOIN listings l ON u.id = l.seller_id AND l.status = 'active'
            WHERE u.username LIKE ? AND u.is_banned = 0
            GROUP BY u.id, u.username
            ORDER BY active_items DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<div class="main-wrapper">
    <main class="content-feed" style="max-width: 1000px; margin: 0 auto; padding-top: 2rem;">
        
        <div class="results-meta" style="margin-bottom: 2rem; text-align: center;">
            <h1 style="font-size: 2rem; color: #111;">Seller Search</h1>
            <p style="color: #666; font-size: 1.1rem;">
                <?php if (!empty($search_query)): ?>
                    Showing profiles matching <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                <?php else: ?>
                    Enter a seller's name to find their storefront.
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($search_query) && $results && $results->num_rows > 0): ?>
            <div class="sellers-grid">
                <?php while($seller = $results->fetch_assoc()): ?>
                    <div class="seller-profile-card">
                        <div class="seller-avatar-large">
                            <?php echo strtoupper(substr($seller['username'], 0, 1)); ?>
                        </div>
                        <h3 class="seller-username"><?php echo htmlspecialchars($seller['username']); ?></h3>
                        <p class="seller-stats"><?php echo $seller['active_items']; ?> Active Listings</p>
                        <div class="seller-rating-stars">⭐⭐⭐⭐⭐5.0</div>
                        
                        <a href="storefront.php?id=<?php echo $seller['id']; ?>" class="primary-btn view-store-btn">View Storefront</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php elseif (!empty($search_query)): ?>
            <div class="empty-search-state">
                <span style="font-size: 3rem;">🕵️‍♂️</span>
                <h2>No active sellers found</h2>
                <p>We couldn't find any active accounts matching "<?php echo htmlspecialchars($search_query); ?>".</p>
                <a href="index.php" class="secondary-btn" style="display:inline-block; margin-top:1rem; width:auto; padding: 0.8rem 2rem;">Return Home</a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php include 'includes/footer.php'; ?>