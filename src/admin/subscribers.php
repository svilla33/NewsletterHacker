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

    eliminarSuscriptor(
        $pdo,
        (int)$_GET['delete']
    );

    header('Location: subscribers.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$categorias = obtenerCategorias($pdo);



/*
|--------------------------------------------------------------------------
| EDITAR
|--------------------------------------------------------------------------
*/

$editando = false;
$suscriptor = null;

if (isset($_GET['edit'])) {

    $suscriptor = obtenerSuscriptorCompleto(
        $pdo,
        (int)$_GET['edit']
    );

    $editando = $suscriptor !== null;
}

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    guardarSuscriptor(
        $pdo,
        $_POST
    );

    header('Location: subscribers.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LISTADO
|--------------------------------------------------------------------------
*/

$suscriptores = obtenerSuscriptores($pdo);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscriptores · Terminal</title>
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
                <i class="fas fa-terminal"></i> admin@subscriber-manager
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
                <h1 class="glitch">Gestión Suscriptores</h1>
            </div>
            <div class="tagline">
                <i class="fas fa-user-plus"></i> <?= $editando ? 'Modificando credenciales del suscriptor' : 'Registrando nuevo objetivo en la base de datos' ?>
            </div>

            <div class="hacker-card">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $suscriptor['id'] ?? '' ?>">

                    <div class="prompt-line">
                        <span class="dollar">$</span>
                        <span class="cmd-text">./subscriber_db --<?= $editando ? 'update' : 'append' ?></span>
                    </div>

                    <p>Nombre</p>
                    <input type="text" name="nombre" class="terminal-input"
                        value="<?= htmlspecialchars($suscriptor['nombre'] ?? '') ?>">

                    <p>Email</p>
                    <input type="email" name="email" required class="terminal-input"
                        value="<?= htmlspecialchars($suscriptor['email'] ?? '') ?>">

                    <p>Estado</p>
                    <select name="estado" class="terminal-select">
                        <option value="activo"
                            <?= (($suscriptor['estado'] ?? 'activo') === 'activo') ? 'selected' : '' ?>>
                            Activo
                        </option>
                        <option value="inactivo" <?= (($suscriptor['estado'] ?? '') === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        <option value="baja" <?= (($suscriptor['estado'] ?? '') === 'baja') ? 'selected' : '' ?>>Baja</option>
                    </select>

                    <p>Categorías de interés</p>
                    <div class="tags-container">
                        <?php foreach ($categorias as $cat): ?>
                            <label class="terminal-checkbox-label">
                                <input
                                    type="checkbox"
                                    name="categorias[]"
                                    class="terminal-checkbox"
                                    value="<?= $cat['id'] ?>"
                                    <?= in_array(
                                        $cat['id'],
                                        $suscriptor['categorias'] ?? []
                                    ) ? 'checked' : '' ?>>

                                <?= htmlspecialchars($cat['nombre']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <br>
                    <button type="submit" class="terminal-btn">
                        <i class="fas fa-save"></i> <?= $editando ? 'Actualizar' : 'Crear' ?>
                    </button>
                </form>
            </div>

            <hr>
            <h2><i class="fas fa-users-viewfinder"></i> Suscriptores Registrados</h2>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Categorías</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($suscriptores as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><?= htmlspecialchars($s['nombre'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['email']) ?></td>
                        <td><?= htmlspecialchars($s['estado']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($s['fecha_suscripcion'])) ?></td>
                        <td>
                            <?php $cats = obtenerNombresCategoriasSuscriptor($pdo, $s['id']); ?>
                            <?= empty($cats) ? '-' : htmlspecialchars(implode(', ', $cats)) ?>
                        </td>
                        <td>
                            <a href="?edit=<?= $s['id'] ?>"><i class="fas fa-user-edit"></i> Editar</a> |
                            <a href="?delete=<?= $s['id'] ?>" onclick="return confirm('¿Eliminar suscriptor?')"><i class="fas fa-user-minus"></i> Eliminar</a>
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