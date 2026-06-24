<?php 
session_start();
include 'includes/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];

$sql = "SELECT l.*, u.username FROM listings l 
        JOIN users u ON l.seller_id = u.id 
        WHERE l.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2 style='text-align:center; margin-top:5rem;'>Product not found.</h2>");
}

$product = $result->fetch_assoc();

// Consolidate all images into a single array
$raw_images = [$product['img_main'], $product['img_front'], $product['img_back'], $product['img_side'], $product['img_detail']];
if (!empty($product['gallery_images'])) {
    $extra = json_decode($product['gallery_images'], true);
    if (is_array($extra)) { $raw_images = array_merge($raw_images, $extra); }
}
$images = array_values(array_filter($raw_images)); 

$is_auction = ($product['listing_type'] === 'auction');

include 'includes/header.php'; 
?>

<div class="main-wrapper">

    <main class="content-feed">
        <div class="product-view-layout">
            
            <div class="product-gallery-zone">
                <div class="thumbnail-column">
                    <?php foreach($images as $index => $img): ?>
                        <div class="thumb-box <?php echo $index === 0 ? 'active-thumb' : ''; ?>" 
                             onmouseenter="updateMainImage(<?php echo $index; ?>)"
                             onclick="updateMainImage(<?php echo $index; ?>)">
                            <img src="images/<?php echo htmlspecialchars($img); ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="main-media-container">
                    <div class="main-image-box">
                        <button class="gallery-nav prev-nav" onclick="prevImage()">&#10094;</button>
                        <img id="main-product-image" src="images/<?php echo htmlspecialchars($images[0]); ?>" alt="Main Product View">
                        <button class="gallery-nav next-nav" onclick="nextImage()">&#10095;</button>
                    </div>
                    
                    <div class="media-actions-bar">
                        <button class="sleek-share-btn" onclick="copyLink()">Share Link</button>
                        <button class="sleek-report-btn">Report Listing</button>
                    </div>
                </div>
            </div>

            <div class="product-info-zone">
                
                <div class="utility-bar">
                    <span class="brand-tag"><?php echo htmlspecialchars($product['brand']); ?></span>
                    <span class="sold-count">🔥 <?php echo $product['units_sold'] ?? 0; ?> sold</span>
                </div>

                <h1 class="product-page-title" style="margin-bottom: 0.3rem;"><?php echo htmlspecialchars($product['title']); ?></h1>

                <div class="product-quick-rating">
                    <span class="stars">⭐⭐⭐⭐⭐</span>
                    <a href="#reviews-section" class="rating-count">4.9 (12 ratings)</a>
                </div> 

                <div class="seller-mini-card">
                    <div class="seller-avatar">👤</div>
                    <div class="seller-details">
                        <span class="seller-name"><?php echo htmlspecialchars($product['username']); ?></span>
                        <span class="seller-rating">⭐⭐⭐⭐⭐ (12 Reviews)</span>
                    </div>
                    <div class="seller-actions">
                        <button class="msg-seller-btn" title="Message Seller">✉️</button>
                    </div>
                </div>

                <hr class="divider">

                <div class="action-buttons" style="display: flex; gap: 1rem; width: 100%;">
                    <?php 
                    $current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                    
                    if ($product['seller_id'] === $current_user && $current_user !== 0): ?>
                        <button disabled class="primary-btn" style="background: #e2e8f0; color: #64748b; cursor: not-allowed; width: 100%;">This is your listing</button>
                        
                    <?php elseif ($product['status'] !== 'active'): ?>
                        <button disabled class="primary-btn" style="background: #fee2e2; color: #991b1b; cursor: not-allowed; width: 100%;">No longer available</button>
                        
                    <?php elseif ($is_auction): ?>
                        <button class="primary-btn bid-color" style="flex: 1;">Place Bid</button>
                        <button class="secondary-btn" style="flex: 1;">❤️ Watchlist</button>
                        
                    <?php else: ?>
                        <form action="add-to-cart.php" method="POST" style="flex: 1; display: flex;">
                            <input type="hidden" name="listing_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="primary-btn" style="width: 100%;">Buy Now</button>
                        </form>
                        
                        <form action="watchlist.php" method="POST" style="flex: 1; display: flex;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="listing_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="secondary-btn" style="width: 100%;">❤️ Wishlist</button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <section class="product-description-section">
            <h2>Product Description</h2>
            <div class="desc-content">
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
        </section>

        <section id="reviews-section" class="amazon-reviews-section">
            <h2>Customer Reviews</h2>
            
            <div class="reviews-dashboard-layout">
                <div class="reviews-summary-panel">
                    <div class="aggregate-rating-number">4.9</div>
                    <div class="aggregate-stars">⭐⭐⭐⭐⭐</div>
                    <p class="global-count-text">Based on 12 global ratings</p>
                    
                    <div class="histogram-container">
                        <div class="histogram-row"><span>5 star</span><div class="progress-bar-bg"><div class="progress-fill" style="width: 92%;"></div></div><span>92%</span></div>
                        <div class="histogram-row"><span>4 star</span><div class="progress-bar-bg"><div class="progress-fill" style="width: 8%;"></div></div><span>8%</span></div>
                        <div class="histogram-row"><span>3 star</span><div class="progress-bar-bg"><div class="progress-fill" style="width: 0%;"></div></div><span>0%</span></div>
                        <div class="histogram-row"><span>2 star</span><div class="progress-bar-bg"><div class="progress-fill" style="width: 0%;"></div></div><span>0%</span></div>
                        <div class="histogram-row"><span>1 star</span><div class="progress-bar-bg"><div class="progress-fill" style="width: 0%;"></div></div><span>0%</span></div>
                    </div>
                </div>

                <div class="reviews-feed-panel">
                    <h3>Top reviews from South Africa</h3>
                    
                    <div class="individual-review-card">
                        <div class="reviewer-meta-row">
                            <div class="user-avatar-sm">👤</div>
                            <span class="reviewer-profile-name">Kabelo M.</span>
                        </div>
                        <div class="review-stars-title">
                            <span>⭐⭐⭐⭐⭐</span>
                            <strong>Highly recommended, crisp packaging</strong>
                        </div>
                        <span class="review-date-stamp">Reviewed on June 1, 2026</span>
                        <span class="verified-purchase-badge">Verified Purchase</span>
                        <p class="review-body-text">The item arrived exactly as shown in the multi-angle shots. Seamless transaction and completely verified. Seller was highly responsive to messages!</p>
                    </div>

                    <div class="individual-review-card">
                        <div class="reviewer-meta-row">
                            <div class="user-avatar-sm">👤</div>
                            <span class="reviewer-profile-name">Thabo S.</span>
                        </div>
                        <div class="review-stars-title">
                            <span>⭐⭐⭐⭐⭐</span> 
                            <strong>Item pristine condition</strong>
                        </div>
                        <span class="review-date-stamp">Reviewed on May 24, 2026</span>
                        <span class="verified-purchase-badge">Verified Purchase</span>
                        <p class="review-body-text">Great condition, works perfectly on setup. Saving a couple of hundred Rand compared to retail stores makes this marketplace totally worth it.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    const galleryImages = <?php echo json_encode($images); ?>;
    let currentIndex = 0;

    function updateMainImage(index) {
        currentIndex = index;
        document.getElementById('main-product-image').src = 'images/' + galleryImages[currentIndex];
        
        let allThumbs = document.querySelectorAll('.thumb-box');
        allThumbs.forEach((thumb, idx) => {
            if(idx === currentIndex) {
                thumb.classList.add('active-thumb');
            } else {
                thumb.classList.remove('active-thumb');
            }
        });
    }

    function nextImage() {
        let newIndex = (currentIndex + 1) % galleryImages.length;
        updateMainImage(newIndex);
    }

    function prevImage() {
        let newIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
        updateMainImage(newIndex);
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href);
        alert("Link copied to clipboard!");
    }
</script>

<?php include 'includes/footer.php'; ?>