<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php'; 


// Update the user's 'last_active' timestamp if logged in
if (isset($_SESSION['user_id'])) {
    $stmt_ping = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $stmt_ping->bind_param("i", $_SESSION['user_id']);
    $stmt_ping->execute();
    $stmt_ping->close();
}

// Grab the current page name ONCE at the top
$page = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C2C Marketplace</title>

    <link rel="stylesheet" href="styles/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/layout.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/components.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/shared.css?v=<?php echo time(); ?>">

    <?php 
    $css_map = [
        'login.php'      => 'auth.css',
        'register.php'   => 'auth.css',
        'sell.php'       => 'sell.css',
        'product.php'    => 'product.css',
        'search.php'     => 'search.css',
        'storefront.php' => 'storefront.css',
        'sellers.php'    => 'storefront.css',
        'admin.php'      => 'admin.css',
        'index.php'      => 'search.css', 
        'my-listings.php'      => 'my-listings.css' 
    ];

    if (array_key_exists($page, $css_map)) {
        echo '<link rel="stylesheet" href="styles/' . $css_map[$page] . '?v=' . time() . '">';
    }
    ?>
</head>
<body>

<?php 
//  CONDITIONALLY HIDE TO THE NAVBAR ON AUTH PAGES
$auth_pages = ['login.php', 'register.php']; 
if (!in_array($page, $auth_pages)): 
?>

    <header>
        <div class="logo">LOGO</div>
        <div class="search-bar">
            <form action="search.php" method="GET" class="master-search-form" id="global-search-form" style="position: relative;">
                <input type="text" name="q" id="search-input" autocomplete="off" placeholder="Search for anything..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit" class="search-btn">Search</button>
                <div id="suggestions-box" class="autocomplete-dropdown hidden-fields"></div>
            </form>
        </div>
        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
    <li><a href="admin.php" class="header-admin-btn">Admin Panel</a></li>
<?php endif; ?>
                    <li><a href="account.php">Account</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                <?php endif; ?>
                <li><a href="index.php">Home</a></li>
            </ul>
        </nav>
    </header>

    <div class="quick-categories-wrapper">
        <div class="toggle-btn-div">
            <button id="toggle-btn" class="burger-btn">
                <span class="burger-icon">☰</span>
            </button>
        </div>
        <nav>
    <ul>
        <li><a href="search.php?category=Best+Sellers">Best Sellers</a></li>
        <li><a href="search.php?category=Garden+and+Greenery">Garden and Greenery</a></li>
        <li><a href="search.php?category=Books">Books</a></li>
        <li><a href="search.php?category=Electronics">Electronics</a></li>
        <li><a href="search.php?category=Todays+Deals">Today's Deals</a></li>
        <li><a href="search.php?category=Fashion">Fashion</a></li>
        <li><a href="search.php?category=Sports+and+Equipment">Sports and Equipment</a></li>
        <li><a href="sell.php" class="sell-nav-link">Sell</a></li>
    </ul>
</nav>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('search-input');
        const suggestionsBox = document.getElementById('suggestions-box');
        const searchForm = document.getElementById('global-search-form');
        let timeoutId;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const query = this.value.trim();

                if (query.length < 2) {
                    suggestionsBox.classList.add('hidden-fields');
                    return;
                }

                timeoutId = setTimeout(() => {
                    fetch(`ajax-search.php?keyword=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            let hasResults = false;

                            if (data.items.length > 0) {
                                hasResults = true;
                                data.items.forEach(item => {
                                    let div = document.createElement('div');
                                    div.className = 'suggestion-item';
                                    div.innerHTML = `🔍 ${item}`;
                                    div.onclick = () => { searchInput.value = item; searchForm.submit(); };
                                    suggestionsBox.appendChild(div);
                                });
                            }

                            if (hasResults) {
                                let divider = document.createElement('div');
                                divider.className = 'suggestion-divider';
                                suggestionsBox.appendChild(divider);
                            }

                            let sellerDiv = document.createElement('div');
                            sellerDiv.className = 'suggestion-seller-link';
                            sellerDiv.innerHTML = `Search <strong>"${query}"</strong> in Sellers 👤`;
                            sellerDiv.onclick = () => { window.location.href = 'sellers.php?q=' + encodeURIComponent(query); };
                            suggestionsBox.appendChild(sellerDiv);

                            suggestionsBox.classList.remove('hidden-fields');
                        });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.classList.add('hidden-fields');
                }
            });
        }
    });
    </script>

<?php endif; ?>