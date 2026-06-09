<?php

require_once 'includes/conexion.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die("Newsletter no encontrada");
}

$stmt = $pdo->prepare("
    SELECT
        n.*,
        c.nombre AS categoria
    FROM newsletters n
    JOIN categorias c
        ON c.id = n.categoria_id
    WHERE n.id = ?
    AND n.estado = 'publicado'
");

$stmt->execute([$id]);

$newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$newsletter) {
    die("Newsletter no encontrada");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>
        <?= htmlspecialchars($newsletter['titulo']) ?>
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<h1>
    <?= htmlspecialchars($newsletter['titulo']) ?>
</h1>

<p>
    Categoría:
    <?= htmlspecialchars($newsletter['categoria']) ?>
</p>

<hr>

<div>

    <?= nl2br(htmlspecialchars($newsletter['contenido'])) ?>

</div>

<br>

<a href="index.php">
    ← Volver
</a>

</body>
</html>