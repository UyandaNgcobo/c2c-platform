<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'includes/db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to sell an item.");
}

$seller_id = $_SESSION['user_id'];

// 2Capture Form Data
$title = $_POST['title'];
$brand = $_POST['brand'];
$identifier = !empty($_POST['identifier']) ? $_POST['identifier'] : NULL;
$condition = $_POST['condition'];
$quantity = (int)$_POST['quantity'];
$description = $_POST['description'];
$listing_type = $_POST['listing_type'];
$buy_now_price = ($listing_type === 'fixed') ? $_POST['buy_now_price'] : NULL;
$starting_bid = ($listing_type === 'auction') ? $_POST['starting_bid'] : NULL;

// Handle Auction Logic
$auction_ends_at = NULL;
if ($listing_type === 'auction') {
    $days = (int)$_POST['auction_duration'];
    $auction_ends_at = date('Y-m-d H:i:s', strtotime("+$days days")); 
}

// Image Upload Helper
function uploadImage($inputName) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === 0) {
        $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
        $newName = uniqid('img_', true) . '.' . $ext; 
        $destination = 'images/' . $newName;
        move_uploaded_file($_FILES[$inputName]['tmp_name'], $destination);
        return $newName;
    }
    return NULL;
}

$img_main = uploadImage('img_main'); 
$img_front = uploadImage('img_front');
$img_back = uploadImage('img_back');
$img_side = uploadImage('img_side');
$img_detail = uploadImage('img_detail');

// Process Gallery
$gallery_images = [];
if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
    foreach ($_FILES['gallery']['name'] as $key => $name) {
        if ($_FILES['gallery']['error'][$key] === 0) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = uniqid('gal_', true) . '.' . $ext;
            move_uploaded_file($_FILES['gallery']['tmp_name'][$key], 'images/' . $newName);
            $gallery_images[] = $newName;
        }
    }
}
$gallery_json = !empty($gallery_images) ? json_encode($gallery_images) : NULL;

// Save to Database
$conn->begin_transaction();

try {
    $sql = "INSERT INTO listings (seller_id, title, brand, identifier, item_condition, quantity, listing_type, buy_now_price, starting_bid, auction_ends_at, img_main, img_front, img_back, img_side, img_detail, gallery_images, description, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters: "issssisddssssssss" = 17 characters
    $stmt->bind_param(
        "issssisddssssssss", 
        $seller_id, $title, $brand, $identifier, $condition, $quantity,
        $listing_type, $buy_now_price, $starting_bid, $auction_ends_at, 
        $img_main, $img_front, $img_back, $img_side, $img_detail, 
        $gallery_json, $description
    );
    
    $stmt->execute();
    $listing_id = $conn->insert_id; // Get the ID of the new listing

    if (!empty($_POST['categories']) && is_array($_POST['categories'])) {
        $stmt_cat = $conn->prepare("INSERT INTO listing_categories (listing_id, category_id) VALUES (?, ?)");
        foreach ($_POST['categories'] as $cat_id) {
            $cat_id = (int)$cat_id;
            $stmt_cat->bind_param("ii", $listing_id, $cat_id);
            $stmt_cat->execute();
        }
    } else {
        // Fallback to Category 1 (General) if none selected
        $stmt_cat = $conn->prepare("INSERT INTO listing_categories (listing_id, category_id) VALUES (?, 1)");
        $stmt_cat->bind_param("i", $listing_id);
        $stmt_cat->execute();
    }

    // Commit everything
    $conn->commit();
    header("Location: index.php?status=success");
    exit();

} catch (Exception $e) {
    // If anything goes wrong, undo everything
    $conn->rollback();
    die("Database Error: " . $e->getMessage());
}
?>