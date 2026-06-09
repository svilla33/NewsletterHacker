<?php

require_once '../includes/auth.php';
require_once '../includes/conexion.php';
require_once '../includes/sql.php';

$stats = estadisticasSistema($pdo);

$totalCategorias = $pdo->query("
    SELECT COUNT(*)
    FROM categorias
")->fetchColumn();

$totalEtiquetas = $pdo->query("
    SELECT COUNT(*)
    FROM etiquetas
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| Últimas newsletters
|--------------------------------------------------------------------------
*/

$ultimasNews = $pdo->query("
    SELECT
        id,
        titulo,
        estado,
        creado_en
    FROM newsletters
    ORDER BY creado_en DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Últimos suscriptores
|--------------------------------------------------------------------------
*/

$ultimosSubs = $pdo->query("
    SELECT
        id,
        nombre,
        email,
        estado
    FROM suscriptores
    ORDER BY fecha_suscripcion DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Terminal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="terminal-window">
        <!-- Barra superior al estilo terminal -->
        <div class="term-bar">
            <div class="term-buttons">
                <span class="term-btn red"></span>
                <span class="term-btn yellow"></span>
                <span class="term-btn green"></span>
            </div>
            <div class="term-title">
                <i class="fas fa-terminal"></i> admin@newsletter-panel
            </div>
            <div class="term-status">
                <i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
            </div>
        </div>

        <div class="term-content">
            <!-- Navegación administrativa integrada -->
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

            <!-- Título principal con efecto glitch -->
            <div class="glitch-wrapper">
                <h1 class="glitch">Panel Admin</h1>
            </div>
            <div class="tagline">
                <i class="fas fa-chart-line"></i> Bienvenido, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong> — monitoreo del sistema en tiempo real.
            </div>

            <!-- Tarjeta de estadísticas rápidas -->
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-newspaper"></i>
                    <h3>Newsletters</h3>
                    <p><?= $stats['newsletters'] ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <h3>Publicadas</h3>
                    <p><?= $stats['publicadas'] ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-user-friends"></i>
                    <h3>Suscriptores</h3>
                    <p><?= $stats['suscriptores'] ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-layer-group"></i>
                    <h3>Categorías</h3>
                    <p><?= $totalCategorias ?></p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-tag"></i>
                    <h3>Etiquetas</h3>
                    <p><?= $totalEtiquetas ?></p>
                </div>
            </div>

            <hr>

            <h2><i class="fas fa-clock"></i> Últimas Newsletters</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
                <?php foreach ($ultimasNews as $news): ?>
                <tr>
                    <td><?= $news['id'] ?></td>
                    <td><?= htmlspecialchars($news['titulo']) ?></td>
                    <td><?= htmlspecialchars($news['estado']) ?></td>
                    <td><?= $news['creado_en'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <a class="terminal-btn" href="newsletters.php">
                <i class="fas fa-cogs"></i> Gestionar Newsletters
            </a>

            <hr>

            <h2><i class="fas fa-user-plus"></i> Últimos Suscriptores</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                </tr>
                <?php foreach ($ultimosSubs as $sub): ?>
                <tr>
                    <td><?= $sub['id'] ?></td>
                    <td><?= htmlspecialchars($sub['nombre'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($sub['email']) ?></td>
                    <td><?= htmlspecialchars($sub['estado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <a class="terminal-btn" href="subscribers.php">
                <i class="fas fa-address-book"></i> Gestionar Suscriptores
            </a>

            <!-- Footer terminal -->
            <div class="footer">
                <span><i class="fas fa-shield-alt"></i> Panel seguro · sesión activa</span>
                <span>v1.0.4</span>
            </div>
        </div>
    </div>
</body>
</html>