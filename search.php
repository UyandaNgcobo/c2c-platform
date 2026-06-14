<?php 
session_start();
include 'includes/db.php';
include 'includes/header.php'; 

// query builder
$where_clauses = ["status = 'active'"];
$params = [];
$types = "";

// Build Filter Logic
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
if (!empty($_GET['type']) && in_array($_GET['type'], ['fixed', 'auction'])) {
    $where_clauses[] = "listing_type = ?";
    $types .= "s";
    $params[] = $_GET['type'];
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

//  PAGINATION Logic
$limit = 12; // Adjusted to 12 for grid symmetry (rows of 3 or 4)
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get Total Count for Pagination Tabs
$count_sql = "SELECT COUNT(*) as total FROM listings WHERE $where_sql";
$stmt_count = $conn->prepare($count_sql);
if ($types) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_results = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $limit);

// Execute Actual Data Query with LIMIT and OFFSET
$data_sql = "SELECT * FROM listings WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt_data = $conn->prepare($data_sql);
if ($types) $stmt_data->bind_param($types, ...$params);
$stmt_data->execute();
$results = $stmt_data->get_result();

// Helper function to keep filters active when clicking next page
function buildPageUrl($pageNum) {
    $get = $_GET;
    $get['page'] = $pageNum;
    return 'search.php?' . http_build_query($get);
}
?>

<div class="main-wrapper">

    <main class="content-feed">
        
        <div class="search-header-zone" style="padding: 1rem 1.5rem;">
            <div class="category-bubbles-container" style="padding-bottom: 0;">
                <a href="search.php" class="cat-bubble <?php echo empty($_GET['category']) ? 'active-bubble' : ''; ?>">All Categories</a>
                <?php
                $cat_query = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                while ($cat = $cat_query->fetch_assoc()) {
                    $is_active = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'active-bubble' : '';
                    $current_q = !empty($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';
                    echo "<a href='search.php?category=" . $cat['id'] . $current_q . "' class='cat-bubble $is_active'>" . htmlspecialchars($cat['name']) . "</a>";
                }
                ?>
            </div>
        </div>

        <div class="search-layout-split">
            <aside class="filter-sidebar">
                <form action="search.php" method="GET" id="filter-form">
                    <?php if(!empty($_GET['q'])): ?>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($_GET['q']); ?>">
                    <?php endif; ?>

                    <div class="filter-group">
                        <h4>Categories</h4>
                        <div class="sidebar-category-list">
                            <label class="filter-radio">
                                <input type="radio" name="category" value="" <?php echo empty($_GET['category']) ? 'checked' : ''; ?>> All Categories
                            </label>
                            <?php 
                            $side_cat_query = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                            while ($scat = $side_cat_query->fetch_assoc()) {
                                $checked = (isset($_GET['category']) && $_GET['category'] == $scat['id']) ? 'checked' : '';
                                echo '<label class="filter-radio">';
                                echo '<input type="radio" name="category" value="' . $scat['id'] . '" ' . $checked . '> ' . htmlspecialchars($scat['name']);
                                echo '</label>';
                            }
                            ?>
                        </div>
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
                        <h4>Buying Format</h4>
                        <label class="filter-radio">
                            <input type="radio" name="type" value="" <?php echo empty($_GET['type']) ? 'checked' : ''; ?>> All Listings
                        </label>
                        <label class="filter-radio">
                            <input type="radio" name="type" value="fixed" <?php echo (isset($_GET['type']) && $_GET['type'] == 'fixed') ? 'checked' : ''; ?>> Buy It Now
                        </label>
                        <label class="filter-radio">
                            <input type="radio" name="type" value="auction" <?php echo (isset($_GET['type']) && $_GET['type'] == 'auction') ? 'checked' : ''; ?>> Auction Only
                        </label>
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
                    <a href="search.php" class="clear-filters-link">Clear All</a>
                </form>
            </aside>

            <section class="search-results-area">
                <div class="results-meta">
                    <h3><?php echo $total_results; ?> results found</h3>
                </div>

                <?php if ($results->num_rows > 0): ?>
                    
                    <div class="product-grid">
                        <?php while($item = $results->fetch_assoc()): 
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

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPageUrl($page - 1); ?>" class="page-tab">« Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo buildPageUrl($i); ?>" class="page-tab <?php echo ($i === $page) ? 'active-page' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo buildPageUrl($page + 1); ?>" class="page-tab">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-search-state">
                        <span style="font-size: 3rem;"></span>
                        <h2>No exact matches found</h2>
                        <p>Try clearing your filters or using different keywords.</p>
                        <a href="search.php" class="secondary-btn" style="display:inline-block; margin-top:1rem; width:auto; padding: 0.8rem 2rem;">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>