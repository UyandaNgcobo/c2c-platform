<?php
session_start();

// Ensure the user is logged in before they can add to cart
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=login_required");
    exit();
}

// Create the cart session array if it doesn't exist yet
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Grab the product ID from the Buy Now button
if (isset($_POST['listing_id'])) {
    $listing_id = (int)$_POST['listing_id'];

    // We add the item ID to the session cart array.
    $_SESSION['cart'][$listing_id] = 1; 
    
    // Send them straight to the basket
    header("Location: cart.php");
    exit();
} else {
    // Nobody will access this page directly, kick them to main if they try
    header("Location: index.php");
    exit();
}
?>