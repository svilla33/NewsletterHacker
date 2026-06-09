<?php

require_once '../includes/auth.php';
require_once '../includes/conexion.php';
require_once '../includes/sql.php';

/*
|--------------------------------------------------------------------------
| ELIMINAR
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete'])) {
    eliminarEtiqueta(
        $pdo,
        (int)$_GET['delete']
    );

    header('Location: etiquetas.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    crearEtiqueta(
        $pdo,
        trim($_POST['nombre'])
    );

    header('Location: etiquetas.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LISTADO
|--------------------------------------------------------------------------
*/
$etiquetas = obtenerEtiquetas($pdo);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas · Terminal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,700&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="terminal-window">
        <div class="term-bar">
            <div class="term-buttons">
                <span class="term-btn red"></span>
                <span class="term-btn yellow"></span>
                <span class="term-btn green"></span>
            </div>
            <div class="term-title">
                <i class="fas fa-terminal"></i> admin@tag-manager
            </div>
            <div class="term-status">
                <i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin') ?>
            </div>
        </div>

        <div class="term-content">
            <div class="admin-nav">
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <span class="sep">|</span>
                <a href="newsletters.php"><i class="fas fa-newspaper"></i> Newsletters</a>
                <span class="sep">|</span>
                <a href="categorias.php"><i class="fas fa-tags"></i> Categorías</a>
                <span class="sep">|</span>
                <a href="etiquetas.php"><i class="fas fa-hashtag"></i> Etiquetas</a>
                <span class="sep">|</span>
                <a href="subscribers.php"><i class="fas fa-users"></i> Suscriptores</a>
                <span class="sep">|</span>
                <a href="../index.php"><i class="fas fa-globe"></i> Sitio Público</a>
                <span class="sep">|</span>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>

            <div class="glitch-wrapper">
                <h1 class="glitch">Gestión Etiquetas</h1>
            </div>
            <div class="tagline">
                <i class="fas fa-hashtag"></i> Indexación de palabras clave para refinamiento de metadata
            </div>

            <div class="hacker-card">
                <form method="POST">
                    <div class="prompt-line">
                        <span class="dollar">$</span>
                        <span class="cmd-text">./tag_injector --insert</span>
                    </div>

                    <p>Nombre de la Etiqueta</p>
                    <div class="input-group" style="gap: 10px; display: flex;"> 
                        <input
                            type="text"
                            name="nombre"
                            placeholder="Ej: malware, zero-day, phising..."
                            class="terminal-input"
                            required>

                        <button type="submit" class="terminal-btn">
                            <i class="fas fa-plus"></i> Añadir
                        </button>
                    </div>
                </form>
            </div>

            <hr>
            <h2><i class="fas fa-fingerprint"></i> Hashes / Etiquetas Indexadas</h2>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Etiqueta</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($etiquetas as $tag): ?>
                <tr>
                    <td><?= $tag['id'] ?></td>
                    <td>
                        <span class="tag-badge"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($tag['nombre']) ?></span>
                    </td>
                    <td>
                        <a href="?delete=<?= $tag['id'] ?>" class="delete-link" onclick="return confirm('¿Eliminar etiqueta?')">
                            <i class="fas fa-trash-alt"></i> Eliminar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="footer">
                <span><i class="fas fa-shield-alt"></i> Portal de Administración</span>
                <span>v1.0.4</span>
            </div>

        </div>
    </div>

</body>
</html>