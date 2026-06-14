<?php
session_start();
require_once 'includes/db.php';

// 1. Auth check
if (!isset($_SESSION['user_id'])) { exit("Unauthorized"); }

// 2. CSRF Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    exit("Invalid security token.");
}

$listing_id = (int)$_POST['listing_id'];
$user_id = $_SESSION['user_id'];

// 3. Fetch for image cleanup
$stmt = $conn->prepare("SELECT img_main FROM listings WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $listing_id, $user_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if ($listing) {
    // Unlink Image
    if (!empty($listing['img_main'])) {
        $image_path = 'images/' . $listing['img_main'];
        if (file_exists($image_path)) unlink($image_path);
    }

    // Transactional Delete
    $conn->begin_transaction();
    try {
        // Clean up cart/wishlist refs
        $conn->prepare("DELETE FROM cart_items WHERE listing_id = ?")->execute([$listing_id]);
        
        // Remove listing
        $del_stmt = $conn->prepare("DELETE FROM listings WHERE id = ? AND seller_id = ?");
        $del_stmt->bind_param("ii", $listing_id, $user_id);
        $del_stmt->execute();
        
        $conn->commit();
        header("Location: my-listings.php?success=1");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: my-listings.php?error=1");
    }
}
exit();
?>