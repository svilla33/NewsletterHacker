<?php
require_once '../includes/auth.php';
require_once '../includes/conexion.php';
require_once '../includes/sql.php';

// -------------------------------------------------------------------------- | ELIMINAR
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    eliminarNewsletter(
        $pdo,
        (int)$_GET['id'],
        $_SESSION['usuario_id']
    );
    header('Location: newsletters.php');
    exit;
}

// -------------------------------------------------------------------------- | CATEGORIAS
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------------------- | EDITAR
$editando = false;
$newsletter = null;

if (isset($_GET['edit'])) {
    $newsletter = obtenerNewsletterCompleta(
        $pdo,
        (int)$_GET['edit']
    );
    if ($newsletter) {
        $editando = true;
    }
}

// -------------------------------------------------------------------------- | GUARDAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id'])) {
        actualizarNewsletter(
            $pdo,
            (int)$_POST['id'],
            (int)$_POST['categoria_id'],
            trim($_POST['titulo']),
            trim($_POST['contenido']),
            $_POST['estado'],
            $_SESSION['usuario_id']
        );

        guardarEtiquetasNewsletter(
            $pdo,
            (int)$_POST['id'],
            $_POST['etiquetas']
        );
    } else {
        $newsletterId = crearNewsletter(
            $pdo,
            $_SESSION['usuario_id'],
            (int)$_POST['categoria_id'],
            trim($_POST['titulo']),
            trim($_POST['contenido']),
            $_POST['estado']
        );

        guardarEtiquetasNewsletter(
            $pdo,
            $newsletterId,
            $_POST['etiquetas']
        );
    }
    header('Location: newsletters.php');
    exit;
}

$newsletters = obtenerNewsletters($pdo);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletters · Terminal</title>
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
                <i class="fas fa-terminal"></i> admin@newsletter-editor
            </div>
            <div class="term-status">
                <i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
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
                <h1 class="glitch">Gestión Newsletters</h1>
            </div>
            <div class="tagline">
                <i class="fas fa-pen-fancy"></i> <?= $editando ? 'Editando newsletter existente' : 'Redactando nueva newsletter' ?>
            </div>

            <div class="hacker-card">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $newsletter['id'] ?? '' ?>">

                    <div class="prompt-line">
                        <span class="dollar">$</span>
                        <span class="cmd-text">./newsletter_manager --<?= $editando ? 'edit' : 'create' ?></span>
                    </div>

                    <p>Título</p>
                    <input type="text" name="titulo" required
                           class="terminal-input"
                           value="<?= htmlspecialchars($newsletter['titulo'] ?? '') ?>">

                    <p>Categoría</p>
                    <select name="categoria_id" class="terminal-select">
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (isset($newsletter) && $newsletter['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p>Etiquetas</p>
                    <?php
                    $todasEtiquetas = obtenerEtiquetas($pdo);
                    $etiquetasSeleccionadas = $newsletter['etiquetas'] ?? [];
                    ?>
                    <div class="tags-container">
                        <?php foreach ($todasEtiquetas as $tag): ?>
                            <label class="terminal-checkbox-label">
                                <input type="checkbox"
                                       name="etiquetas[]"
                                       value="<?= $tag['nombre'] ?>"
                                       class="terminal-checkbox"
                                       <?= in_array($tag['nombre'], $etiquetasSeleccionadas) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($tag['nombre']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="etiquetas" id="etiquetasInput"
                           value="<?= htmlspecialchars(isset($newsletter['etiquetas']) ? implode(',', $newsletter['etiquetas']) : '') ?>">

                    <p>Estado</p>
                    <select name="estado" class="terminal-select">
                        <option value="borrador" <?= (($newsletter['estado'] ?? '') == 'borrador') ? 'selected' : '' ?>>Borrador</option>
                        <option value="publicado" <?= (($newsletter['estado'] ?? '') == 'publicado') ? 'selected' : '' ?>>Publicado</option>
                        <option value="archivado" <?= (($newsletter['estado'] ?? '') == 'archivado') ? 'selected' : '' ?>>Archivado</option>
                    </select>

                    <p>Contenido</p>
                    <textarea name="contenido" rows="12" required
                              class="terminal-textarea"><?= htmlspecialchars($newsletter['contenido'] ?? '') ?></textarea>

                    <br><br>
                    <button type="submit" class="terminal-btn">
                        <i class="fas fa-save"></i> <?= $editando ? 'Actualizar' : 'Crear' ?>
                    </button>
                </form>
            </div>

            <hr>
            <h2><i class="fas fa-list-ul"></i> Listado de Newsletters</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Autor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($newsletters as $n): ?>
                <tr>
                    <td><?= $n['id'] ?></td>
                    <td><?= htmlspecialchars($n['titulo']) ?></td>
                    <td><?= htmlspecialchars($n['categoria']) ?></td>
                    <td><?= htmlspecialchars($n['autor']) ?></td>
                    <td><?= htmlspecialchars($n['estado']) ?></td>
                    <td>
                        <a href="?edit=<?= $n['id'] ?>"><i class="fas fa-edit"></i> Editar</a> |
                        <a href="?action=delete&id=<?= $n['id'] ?>" onclick="return confirm('¿Eliminar newsletter?')"><i class="fas fa-trash"></i> Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div class="footer">
                <span><i class="fas fa-shield-alt"></i> Sesión activa · <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span>v1.0.4</span>
            </div>
        </div>
    </div>
</body>
</html>