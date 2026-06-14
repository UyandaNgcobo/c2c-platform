<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $listing_id = (int)$_POST['listing_id'];
        $check_stmt = $conn->prepare("SELECT seller_id FROM listings WHERE id = ?");
        $check_stmt->bind_param("i", $listing_id);
        $check_stmt->execute();
        $listing_data = $check_stmt->get_result()->fetch_assoc();

        if ($listing_data && $listing_data['seller_id'] !== $user_id) {
            $insert_stmt = $conn->prepare("INSERT IGNORE INTO cart_items (user_id, listing_id, quantity) VALUES (?, ?, 1)");
            $insert_stmt->bind_param("ii", $user_id, $listing_id);
            $insert_stmt->execute();
        }
        header("Location: cart.php");
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_qty') {
        $cart_item_id = (int)$_POST['cart_item_id'];
        $new_qty = (int)$_POST['quantity'];
        $max_qty = (int)$_POST['max_quantity'];

        if ($new_qty > 0 && $new_qty <= $max_qty) {
            $upd_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
            $upd_stmt->bind_param("iii", $new_qty, $cart_item_id, $user_id);
            $upd_stmt->execute();
        } elseif ($new_qty <= 0) {
            $del_stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $del_stmt->bind_param("ii", $cart_item_id, $user_id);
            $del_stmt->execute();
        }
        header("Location: cart.php");
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $cart_item_id = (int)$_POST['cart_item_id'];
        $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $cart_item_id, $user_id);
        $delete_stmt->execute();
        header("Location: cart.php");
        exit();
    }
}

$sql = "SELECT c.id AS cart_id, c.quantity AS cart_qty, l.id AS listing_id, l.title, l.buy_now_price, l.starting_bid, l.listing_type, l.img_main, l.status, l.quantity AS max_stock, u.username AS seller_name 
        FROM cart_items c 
        JOIN listings l ON c.listing_id = l.id 
        JOIN users u ON l.seller_id = u.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_items = [];
$active_subtotal = 0;
$active_items_count = 0;

while ($row = $cart_result->fetch_assoc()) {
    $row['is_active'] = ($row['status'] === 'active');
    $row['display_price'] = ($row['listing_type'] === 'auction') ? $row['starting_bid'] : $row['buy_now_price'];
    if ($row['cart_qty'] > $row['max_stock']) { $row['cart_qty'] = $row['max_stock']; }

    if ($row['is_active']) {
        $active_subtotal += ($row['display_price'] * $row['cart_qty']);
        $active_items_count += $row['cart_qty'];
    }
    $cart_items[] = $row;
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="styles/cart.css?v=<?php echo time(); ?>">

<div class="main-wrapper" style="background: #f8fafc; min-height: 80vh;">
    <div class="cart-container">
        
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-layout">
                
                <div class="cart-items-column">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item-card <?php echo $item['is_active'] ? '' : 'inactive'; ?>">
                            
                            <div class="cart-image-box">
                                <?php if (!empty($item['img_main'])): ?>
                                    <img src="images/<?php echo htmlspecialchars($item['img_main']); ?>" alt="Product">
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.9rem;">No Image</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-info">
                                <div>
                                    <a href="product.php?id=<?php echo $item['listing_id']; ?>" class="cart-item-title">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                    <p style="margin: 0; font-size: 0.9rem;">Sold by: <span class="cart-seller-name"><?php echo htmlspecialchars($item['seller_name']); ?></span></p>
                                    
                                    <?php if ($item['is_active']): ?>
                                        <div class="cart-stock-status">In Stock (<?php echo $item['max_stock']; ?> available)</div>
                                    <?php else: ?>
                                        <div class="cart-status-badge">No longer available</div>
                                    <?php endif; ?>
                                </div>
                                
                                <form action="cart.php" method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" class="remove-btn">Remove from cart</button>
                                </form>
                            </div>

                            <div class="cart-item-actions">
                                <div class="cart-item-price">
                                    R <?php echo number_format($item['display_price'], 2); ?>
                                </div>

                                <?php if ($item['is_active']): ?>
                                    <?php if ($item['listing_type'] === 'fixed'): ?>
                                        <div class="qty-wrapper">
                                            <form method="POST" action="cart.php" class="qty-form">
                                                <input type="hidden" name="action" value="update_qty">
                                                <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_id']; ?>">
                                                <input type="hidden" name="max_quantity" value="<?php echo $item['max_stock']; ?>">
                                                <input type="hidden" name="quantity" value="<?php echo $item['cart_qty'] - 1; ?>">
                                                <button type="submit" class="qty-btn" title="Decrease">-</button>
                                            </form>
                                            
                                            <div class="qty-display"><?php echo $item['cart_qty']; ?></div>
                                            
                                            <form method="POST" action="cart.php" class="qty-form">
                                                <input type="hidden" name="action" value="update_qty">
                                                <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_id']; ?>">
                                                <input type="hidden" name="max_quantity" value="<?php echo $item['max_stock']; ?>">
                                                <input type="hidden" name="quantity" value="<?php echo $item['cart_qty'] + 1; ?>">
                                                <button type="submit" class="qty-btn" title="Increase" <?php echo ($item['cart_qty'] >= $item['max_stock']) ? 'disabled' : ''; ?>>+</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #64748b; font-size: 0.9rem;">Qty: 1 (Auction)</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary-column">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-receipt">
                        <?php foreach ($cart_items as $item): ?>
                            <?php if ($item['is_active']): ?>
                                <div class="receipt-item">
                                    <span class="receipt-title" title="<?php echo htmlspecialchars($item['title']); ?>">
                                        <?php echo htmlspecialchars($item['cart_qty'] . 'x ' . $item['title']); ?>
                                    </span>
                                    <span class="receipt-price">
                                        R <?php echo number_format($item['display_price'] * $item['cart_qty'], 2); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($active_items_count === 0): ?>
                            <p style="color: #94a3b8; font-size: 0.9rem; text-align: center; margin-top: 1rem;">No active items to checkout.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-footer">
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $active_items_count; ?> items)</span>
                            <span>R <?php echo number_format($active_subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Platform Fee (5%)</span>
                            <span>R <?php echo number_format($active_subtotal * 0.05, 2); ?></span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>R <?php echo number_format($active_subtotal * 1.05, 2); ?></span>
                        </div>
                        
                        <?php if ($active_items_count > 0): ?>
                            <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                        <?php else: ?>
                            <button disabled class="checkout-btn">Proceed to Checkout</button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php else: ?>
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 4rem 2rem; text-align: center;">
                <span style="font-size: 4rem; display: block; margin-bottom: 1rem;">🛒</span>
                <h2 style="color: #1e293b; margin-bottom: 1rem;">Your cart is empty</h2>
                <p style="color: #64748b; margin-bottom: 2rem;">Looks like you haven't added any items yet.</p>
                <a href="search.php" class="checkout-btn" style="display: inline-block; width: auto; padding: 0.8rem 2rem;">Start Shopping</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>