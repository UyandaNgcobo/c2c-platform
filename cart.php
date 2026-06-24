<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Check if cart is empty
$cart_empty = empty($_SESSION['cart']);
$total_price = 0;
$cart_items = [];

// If cart has items, fetch their details from the database
if (!$cart_empty) {
    // Get all the IDs from the session array keys
    $item_ids = implode(',', array_keys($_SESSION['cart']));
    
    // Fetch the active items matching those IDs
    $sql = "SELECT id, title, buy_now_price, img_main FROM listings WHERE id IN ($item_ids) AND status = 'active'";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['buy_now_price'];
    }
}

// Paystack requires the amount in cents!
$paystack_amount = $total_price * 100;
?>

<div class="main-wrapper">
    <main class="content-feed" style="max-width: 800px; margin: 2rem auto;">
        <h2>Your Shopping Basket</h2>

        <?php if ($cart_empty): ?>
            <div class="empty-search-state">
                <h2>Your basket is empty.</h2>
                <a href="index.php" class="primary-btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            
            <div class="cart-items-list" style="background:#fff; padding:2rem; border-radius:8px; border:1px solid #eee;">
                <?php foreach ($cart_items as $item): ?>
                    <div style="display:flex; justify-content:space-between; border-bottom:1px solid #ddd; padding:1rem 0;">
                        <div>
                            <h4 style="margin:0;"><?php echo htmlspecialchars($item['title']); ?></h4>
                        </div>
                        <div>
                            <strong>R <?php echo number_format($item['buy_now_price'], 2); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align:right; margin-top:1.5rem; font-size:1.2rem;">
                    Total to Pay: <strong>R <?php echo number_format($total_price, 2); ?></strong>
                </div>

                <form id="paymentForm" style="text-align:right; margin-top:1.5rem;">
                    <button type="submit" class="primary-btn" onclick="payWithPaystack(event)">Pay Securely with Paystack</button>
                </form>
            </div>

        <?php endif; ?>
    </main>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function payWithPaystack(e) {
    e.preventDefault();

    let handler = PaystackPop.setup({
        key: 'pk_test_73f6c47fc8ff88b56195c168ababa37cfa155e64', // public key
        email: '<?php echo $_SESSION['email'] ?? "customer@example.com"; ?>',
        amount: <?php echo $paystack_amount; ?>, // Amount is in kobo
        currency: 'ZAR',
        ref: 'ORD_' + Math.floor((Math.random() * 1000000000) + 1), // random order reference
        
        callback: function(response){
            // This runs if the payment is successful
            alert('Payment complete! Reference: ' + response.reference);
            
            // Redirect them to a success page that will save the order to the database
            window.location.href = 'process-order.php?reference=' + response.reference;
        },
        
        onClose: function(){
            alert('Window closed. Payment cancelled.');
        }
    });

    handler.openIframe();
}
</script>

<?php include 'includes/footer.php'; ?>