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
    eliminarCategoria(
        $pdo,
        (int)$_GET['delete']
    );

    header('Location: categorias.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    crearCategoria(
        $pdo,
        trim($_POST['nombre']),
        trim($_POST['descripcion'])
    );

    header('Location: categorias.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LISTADO
|--------------------------------------------------------------------------
*/
$categorias = obtenerCategorias($pdo);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías · Terminal</title>
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
                <i class="fas fa-terminal"></i> admin@category-manager
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
                <h1 class="glitch">Gestión Categorías</h1>
            </div>
            <div class="tagline">
                <i class="fas fa-tags"></i> Segmentación y organización de directivas de contenido
            </div>

            <div class="hacker-card">
                <form method="POST">
                    <div class="prompt-line">
                        <span class="dollar">$</span>
                        <span class="cmd-text">./category_creator --append</span>
                    </div>

                    <p>Nombre de la Categoría</p>
                    <input
                        type="text"
                        name="nombre"
                        placeholder="Escribe el nombre..."
                        class="terminal-input"
                        required>

                    <p>Descripción</p>
                    <textarea
                        name="descripcion"
                        placeholder="Propósito de este segmento..."
                        class="terminal-textarea"
                        rows="4"></textarea>

                    <br><br>
                    <button type="submit" class="terminal-btn">
                        <i class="fas fa-plus-circle"></i> Crear Categoría
                    </button>
                </form>
            </div>

            <hr>
            <h2><i class="fas fa-folder-open"></i> Segmentos Indexados</h2>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach($categorias as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td><?= htmlspecialchars($cat['nombre']) ?></td>
                    <td>
                        <a href="?delete=<?= $cat['id'] ?>" class="delete-link" onclick="return confirm('¿Eliminar categoría?')">
                            <i class="fas fa-trash"></i> Eliminar
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