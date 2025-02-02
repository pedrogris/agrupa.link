<?php
require 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if ($username && $email && $password) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $password]);
            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;

            echo json_encode(['success' => true, 'redirect' => '/panel']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(['success' => false, 'message' => 'El usuario o email ya están registrados.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error en el servidor. Inténtalo más tarde.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    }
    exit;
}
?>