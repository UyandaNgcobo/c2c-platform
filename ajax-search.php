<?php
include 'includes/db.php';
// Prevent PHP warnings from breaking the JSON format
error_reporting(0); 

if (isset($_GET['keyword'])) {
    $keyword = $_GET['keyword'];
    $search_term = "%" . $keyword . "%";
    
    $response = [
        'items' => [],
        'sellers' => []
    ];

    // Fetch Top 4 Product Matches, making sure that they are active
    $stmt_items = $conn->prepare("SELECT DISTINCT title FROM listings WHERE status = 'active' AND (title LIKE ? OR brand LIKE ?) LIMIT 4");
    $stmt_items->bind_param("ss", $search_term, $search_term);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();
    while ($row = $res_items->fetch_assoc()) {
        $response['items'][] = $row['title'];
    }

    // Fetch Top 2 Seller Matches
    $stmt_users = $conn->prepare("SELECT username FROM users WHERE username LIKE ? AND is_banned = 0 LIMIT 2");
    $stmt_users->bind_param("s", $search_term);
    $stmt_users->execute();
    $res_users = $stmt_users->get_result();
    while ($row = $res_users->fetch_assoc()) {
        $response['sellers'][] = $row['username'];
    }

    // Explicitly tell the browser we are sending JSON
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>