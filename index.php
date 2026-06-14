<?php 
// Including the header
include 'includes/header.php'; 
?>

<div class="main-wrapper">

    <?php include 'includes/sidebar.php'; ?>

    <main class="content-feed">

        <!--  HERO CAROUSEL  -->
        <section class="hero-carousel">
            <div class="carousel-track">
                <div class="carousel-item">
                    <h1>Big Summer Deals!</h1>
                    <p>Up to 50% off on all electronics.</p>
                </div>
            </div>
        </section>

        <!--  AD BANNER  -->
        <section class="ad-banner">
            <p>SPONSORED: Get free delivery on your first 3 orders!</p>
        </section>

        <!--  PRODUCT SECTION -->
       <section class="product-section">
    <h2>Trending Now</h2>

    <div class="product-grid">
        <?php
        // Fetch the 20 most recent active listings from the database
        require_once 'includes/db.php';
        
        $sql = "SELECT * FROM listings WHERE status = 'active' ORDER BY created_at DESC LIMIT 20";
        $result = $conn->query($sql);

        if ($result->num_rows > 0):
            while($item = $result->fetch_assoc()): 
                
                // Determine the correct price and button layout
                $is_auction = ($item['listing_type'] === 'auction');
                $display_price = $is_auction ? $item['starting_bid'] : $item['buy_now_price'];
        ?>
            <div class="product-card">

                <a href="product.php?id=<?php echo $item['id']; ?>" target="_blank" style="text-decoration:none; color:inherit;">
    <div class="product-image-container">
        <img src="images/<?php echo htmlspecialchars($item['img_main']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
        </div>
</a>
<button class="add-to-cart-plus" title="Add to Watchlist">+</button>

                <div class="product-details">
                    <h4 class="product-title"><?php echo htmlspecialchars($item['title']); ?></h4>
                    <p class="condition-brand">
                        <?php echo htmlspecialchars($item['item_condition']); ?> &mdash; <?php echo htmlspecialchars($item['brand']); ?>
                    </p>
                    
                    <span class="price">
                        <?php if ($is_auction) echo "<span style='font-size:0.75rem; color:#666;'>Current Bid: </span>"; ?>
                        R <?php echo number_format($display_price, 2); ?>
                    </span>
                    
                    <div class="card-actions">
                        <?php if ($is_auction): ?>
                            <button class="bid-btn-sm" style="flex: 1; border: 1.5px solid #232f3e; color: #232f3e;">Place Bid</button>
                        <?php else: ?>
                            <button class="buy-btn">Buy Now</button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php 
            endwhile; 
        else: 
        ?>
            <p style="color: #666; font-size: 1.1rem; padding: 2rem 0;">No items are currently listed on the marketplace.</p>
        <?php endif; ?>
    </div>
</section>

    </main>

</div>


<?php include 'includes/footer.php'; ?>