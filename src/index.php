<?php
require_once 'includes/conexion.php';

/*
|--------------------------------------------------------------------------
| Estadísticas
|--------------------------------------------------------------------------
*/
$totalSubs = $pdo->query("
    SELECT COUNT(*)
    FROM suscriptores
    WHERE estado='activo'
")->fetchColumn();

$totalNews = $pdo->query("
    SELECT COUNT(*)
    FROM newsletters
    WHERE estado='publicado'
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| Última newsletter
|--------------------------------------------------------------------------
*/
$ultimaStmt = $pdo->query("
    SELECT
        n.id,
        n.titulo,
        n.contenido,
        n.creado_en,
        c.nombre AS categoria
    FROM newsletters n
    JOIN categorias c
        ON c.id = n.categoria_id
    WHERE n.estado = 'publicado'
    ORDER BY n.creado_en DESC
    LIMIT 1
");
$ultima = $ultimaStmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Todas las newsletters e información estructural
|--------------------------------------------------------------------------
*/
$newslettersStmt = $pdo->query("
    SELECT
        n.id,
        n.titulo,
        n.contenido,
        n.creado_en,
        c.nombre AS categoria
    FROM newsletters n
    JOIN categorias c
        ON c.id = n.categoria_id
    WHERE n.estado = 'publicado'
    ORDER BY n.creado_en DESC
");
$newsletters = $newslettersStmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = $pdo->query("
    SELECT
        id,
        nombre
    FROM categorias
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberSecurity Newsletter</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <div class="terminal-window">
        <div class="term-content">

            <div class="glitch-wrapper">
                <h1 class="glitch">>_ CYBERSECURITY NEWSLETTER</h1>
            </div>

            <p class="hero-description">
                Mantente al día con vulnerabilidades, investigaciones, herramientas y tendencias
                del mundo de la seguridad informática.
            </p>

            <div class="hacker-card">
                <form action="subscribe.php" method="POST">
                    <div class="prompt-line">
                        <span class="dollar">⤷</span>
                        <span class="cmd-text">
                            Ingresa tu correo para recibir la próxima edición
                        </span>
                    </div>

                    <div class="input-group">
                        <input
                            type="email"
                            name="email"
                            class="terminal-input"
                            placeholder="correo@ejemplo.com"
                            required>

                        <button type="submit" class="terminal-btn">
                            <i class="fas fa-paper-plane"></i>
                            SUSCRIBIRSE
                        </button>
                    </div>

                    <strong class="form-section-title">
                        Categorías de interés
                    </strong>

                    <div class="tags-container">
                        <?php foreach ($categorias as $categoria): ?>
                            <label class="terminal-checkbox-label">
                                <input
                                    type="checkbox"
                                    name="categorias[]"
                                    value="<?= $categoria['id'] ?>">
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </form>

                <?php if (isset($_GET['success'])): ?>
                    <div class="log-console log-success">
                        <div class="log-line">
                            <i class="fas fa-check-circle"></i>
                            Suscripción realizada correctamente.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="log-console log-error">
                        <div class="log-line">
                            <i class="fas fa-triangle-exclamation"></i>
                            El correo ya existe o no es válido.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="log-console">
                    <div class="log-line">
                        <i class="fas fa-angle-right"></i>
                        [system] sistema listo para nuevos suscriptores
                    </div>
                    <div class="log-line">
                        <i class="fas fa-angle-right"></i>
                        [latest] análisis de vulnerabilidades y noticias de la semana
                    </div>
                </div>

                <div class="stats-bar">
                    <span>
                        <i class="fas fa-users"></i>
                        <?= $totalSubs ?> suscriptores
                    </span>
                    <span>
                        <i class="fas fa-newspaper"></i>
                        <?= $totalNews ?> ediciones
                    </span>
                    <span>
                        <i class="fas fa-calendar"></i>
                        publicación semanal
                    </span>
                </div>
            </div>

            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-bug"></i>
                    <h3>Vulnerabilidades</h3>
                    <p>Análisis de CVEs relevantes y su impacto.</p>
                </div>

                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Defensa</h3>
                    <p>Buenas prácticas, mitigaciones y hardening.</p>
                </div>

                <div class="feature-item">
                    <i class="fas fa-terminal"></i>
                    <h3>Herramientas</h3>
                    <p>Software y utilidades para profesionales de seguridad.</p>
                </div>

                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <h3>Tendencias</h3>
                    <p>Noticias y análisis del ecosistema tecnológico.</p>
                </div>
            </div>

            <?php if ($ultima): ?>
                <div class="sample-issue">
                    <div class="issue-header">
                        <i class="fas fa-newspaper"></i>
                        <strong>Última edición</strong>
                    </div>

                    <h3><?= htmlspecialchars($ultima['titulo']) ?></h3>

                    <p><?= htmlspecialchars(substr($ultima['contenido'], 0, 300)) ?>...</p>

                    <div class="issue-meta">
                        Categoría: <?= htmlspecialchars($ultima['categoria']) ?>
                        <br>
                        Publicada: <?= date('d/m/Y', strtotime($ultima['creado_en'])) ?>
                    </div>
                    <br>
                    <a href="newsletter.php?id=<?= $ultima['id'] ?>">
                        Leer newsletter completa →
                    </a>
                </div>
            <?php endif; ?>

            <div class="all-newsletters">
                <h2>
                    <i class="fas fa-book-open"></i>
                    Archivo de newsletters
                </h2>

                <?php foreach ($newsletters as $news): ?>
                    <div class="archive-item">
                        <h3><?= htmlspecialchars($news['titulo']) ?></h3>
                        <p><?= htmlspecialchars(substr($news['contenido'], 0, 180)) ?>...</p>
                        <small>
                            <?= htmlspecialchars($news['categoria']) ?> • <?= date('d/m/Y', strtotime($news['creado_en'])) ?>
                        </small>
                        <br>
                        <a href="newsletter.php?id=<?= $news['id'] ?>">Leer completa →</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="testi">
                <div class="testi-card">
                    <i class="fas fa-quote-left"></i>
                    Información clara y actualizada cada semana.
                </div>
                <div class="testi-card">
                    <i class="fas fa-quote-left"></i>
                    Una excelente fuente para mantenerse informado sobre ciberseguridad.
                </div>
            </div>

            <div class="footer">
                <span><i class="fas fa-lock"></i> Privacidad garantizada</span>
                <span><i class="fas fa-envelope"></i> Cancelación en cualquier momento</span>
                <span><i class="fas fa-shield-alt"></i> Contenido educativo y profesional</span>
            </div>

        </div>
    </div>

</body>
</html>