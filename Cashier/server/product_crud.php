<?php
require 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
        break;

    case 'add':
        $title = $_POST['title'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $img = '';

        if (isset($_FILES['img']) && $_FILES['img']['error'] === 0) {
            $fileName = time() . '_' . basename($_FILES['img']['name']);
            $target = '../uploads/' . $fileName;
            if (move_uploaded_file($_FILES['img']['tmp_name'], $target)) {
                $img = 'uploads/' . $fileName;
            }
        }

        $stmt = $conn->prepare("INSERT INTO products (title, price, quantity, img) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $title, $price, $quantity, $img);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        break;


    case 'delete':
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(["status" => "deleted"]);
        break;

    case 'update':
        $id = $_POST['id'];
        $title = $_POST['title'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $img = $_POST['existing_img'];

        if (isset($_FILES['img']) && $_FILES['img']['size'] > 0) {
            $fileName = time() . '_' . basename($_FILES['img']['name']);
            $target = '../uploads/' . $fileName;
            move_uploaded_file($_FILES['img']['tmp_name'], $target);
            $img = 'uploads/' . $fileName;
        }

        $stmt = $conn->prepare("UPDATE products SET title = ?, price = ?, quantity = ?, img = ? WHERE id = ?");
        $stmt->bind_param("siisi", $title, $price, $quantity, $img, $id);
        $stmt->execute();
        echo json_encode(["status" => "updated"]);
        break;
}
