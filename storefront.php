<?php 
session_start();
include 'includes/db.php';

// Check if a seller ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: sellers.php");
    exit();
}

$seller_id = (int)$_GET['id'];

// Fetch Seller Profile Details
$stmt_user = $conn->prepare("SELECT id, username, email, location, contact_number, bio, created_at FROM users WHERE id = ?");
$stmt_user->bind_param("i", $seller_id);
$stmt_user->execute();
$seller_result = $stmt_user->get_result();

if ($seller_result->num_rows === 0) {
    die("<h2 style='text-align:center; margin-top:5rem;'>Seller not found.</h2>");
}
$seller = $seller_result->fetch_assoc();

// DYNAMIC STOREFRONT SEARCH ENGINE 
$where_clauses = ["seller_id = ?", "status = 'active'"];
$params = [$seller_id];
$types = "i";

// Add Filters 
if (!empty($_GET['q'])) {
    $where_clauses[] = "(title LIKE ? OR brand LIKE ?)";
    $search_term = "%" . $_GET['q'] . "%";
    $types .= "ss";
    $params[] = $search_term;
    $params[] = $search_term;
}
if (!empty($_GET['category'])) {
    $where_clauses[] = "category_id = ?";
    $types .= "i";
    $params[] = (int)$_GET['category'];
}
if (!empty($_GET['min_price'])) {
    $where_clauses[] = "COALESCE(buy_now_price, starting_bid) >= ?";
    $types .= "d";
    $params[] = (float)$_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where_clauses[] = "COALESCE(buy_now_price, starting_bid) <= ?";
    $types .= "d";
    $params[] = (float)$_GET['max_price'];
}
if (!empty($_GET['condition']) && is_array($_GET['condition'])) {
    $placeholders = implode(',', array_fill(0, count($_GET['condition']), '?'));
    $where_clauses[] = "item_condition IN ($placeholders)";
    foreach ($_GET['condition'] as $cond) {
        $types .= "s";
        $params[] = $cond;
    }
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch the filtered inventory
$data_sql = "SELECT * FROM listings WHERE $where_sql ORDER BY created_at DESC";
$stmt_items = $conn->prepare($data_sql);
if ($types) $stmt_items->bind_param($types, ...$params);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="styles/search.css?v=<?php echo time(); ?>">

<div class="filter-overlay" id="filter-overlay" onclick="toggleFilters()"></div>

<div class="main-wrapper">
    <main class="content-feed">
        
        <div class="storefront-horizontal-header">
            <div class="storefront-avatar">
                <?php echo strtoupper(substr($seller['username'], 0, 1)); ?>
            </div>
            
            <div class="storefront-info-block">
                <h1><?php echo htmlspecialchars($seller['username']); ?></h1>
                
                <div class="storefront-meta-row">
                    <span class="meta-item">4.9 Stars (12 Reviews)</span>
                    <span class="meta-item">📍 <?php echo htmlspecialchars($seller['location'] ?? 'Not specified'); ?></span>
                    <span class="meta-item"><?php echo $items_result->num_rows; ?> Active Listings</span>
                </div>
                
                <p class="storefront-bio">
                    <?php echo !empty($seller['bio']) ? htmlspecialchars($seller['bio']) : "Welcome to my storefront! Browse my latest items below."; ?>
                </p>
            </div>
            
            <div class="storefront-action-block">
                <button class="primary-btn" style="width:100%; margin-bottom:0.5rem;">+ Follow Seller</button>
                <button class="secondary-btn" style="width:100%;">Contact</button>
            </div>
        </div>

        <div class="search-layout-split" style="margin-top: 2rem;">
            
            <aside class="filter-sidebar">
                <form action="storefront.php" method="GET" id="filter-form">
                    <input type="hidden" name="id" value="<?php echo $seller_id; ?>">
                    
                    <div class="filter-group">
                        <h4>Search this Store</h4>
                        <input type="text" name="q" placeholder="Search keywords..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 1rem;">
                    </div>

                    <div class="filter-group">
                        <h4>Price Range (R)</h4>
                        <div class="price-inputs">
                            <input type="number" name="min_price" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4>Condition</h4>
                        <?php 
                        $conditions = ['New', 'Refurbished', 'Secondhand', 'Well Worn'];
                        $selected_conditions = isset($_GET['condition']) ? (array)$_GET['condition'] : [];
                        foreach ($conditions as $cond): 
                            $checked = in_array($cond, $selected_conditions) ? 'checked' : '';
                        ?>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="condition[]" value="<?php echo $cond; ?>" <?php echo $checked; ?>> <?php echo $cond; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="apply-filters-btn">Apply Filters</button>
                    <a href="storefront.php?id=<?php echo $seller_id; ?>" class="clear-filters-link">Clear All</a>
                </form>
            </aside>

            <section class="search-results-area">
                <div class="inventory-header">
                    <h2>Store Inventory</h2>
                    <button class="mobile-filter-toggle" style="display:none;" onclick="toggleFilters()">Filter Items</button>
                </div>

                <?php if ($items_result->num_rows > 0): ?>
                    <div class="product-grid"> 
                        <?php while($item = $items_result->fetch_assoc()): 
                            $is_auction = ($item['listing_type'] === 'auction');
                            $display_price = $is_auction ? $item['starting_bid'] : $item['buy_now_price'];
                        ?>
                            <div class="product-card">
                                <a href="product.php?id=<?php echo $item['id']; ?>" target="_blank" style="text-decoration:none; color:inherit;">
                                    <div class="product-image-container">
                                        <img src="images/<?php echo htmlspecialchars($item['img_main']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                </a>
                                <div class="product-details">
                                    <h4 class="product-title"><?php echo htmlspecialchars($item['title']); ?></h4>
                                    <p class="condition-brand">
                                        <?php echo htmlspecialchars($item['item_condition']); ?> &mdash; <?php echo htmlspecialchars($item['brand']); ?>
                                    </p>
                                    <span class="price">
                                        <?php if ($is_auction) echo "<span style='font-size:0.75rem; color:#666;'>Current Bid: </span>"; ?>
                                        R <?php echo number_format($display_price, 2); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-search-state">
                        <span style="font-size: 3rem;">🕵️</span>
                        <h2>No matches found</h2>
                        <p>We couldn't find any items matching your filters in this seller's store.</p>
                        <a href="storefront.php?id=<?php echo $seller_id; ?>" class="secondary-btn" style="display:inline-block; margin-top:1rem; width:auto; padding: 0.8rem 2rem;">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </section>
            
        </div>
    </main>

   <a href="report.php?type=user&id=<?php echo $seller_id; ?>" class="floating-report-btn" style="text-decoration:none;">Report Seller</a>
</div>

<script>
    function toggleFilters() {
        const sidebar = document.querySelector('.filter-sidebar');
        const overlay = document.getElementById('filter-overlay');
        
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
</script>

<?php include 'includes/footer.php'; ?>