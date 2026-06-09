<?php

require_once 'includes/conexion.php';
require_once 'includes/sql.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

$categorias = $_POST['categorias'] ?? [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=email');
    exit;
}

try {

    $suscriptorId = registrarSuscriptor(
        $pdo,
        '',
        $email
    );

    if (!empty($categorias)) {

        guardarCategoriasSuscriptor(
            $pdo,
            $suscriptorId,
            $categorias
        );
    }

    header('Location: index.php?success=1');

} catch (PDOException $e) {

    header('Location: index.php?error=1');
}

exit;
