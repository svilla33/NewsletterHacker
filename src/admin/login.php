<?php

session_start();

require_once '../includes/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT
            u.*,
            r.nombre AS rol
        FROM usuarios u
        JOIN roles r
            ON r.id = u.rol_id
        WHERE u.email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        $usuario &&
        password_verify($password, $usuario['password_hash'])
    ) {

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol'] = $usuario['rol'];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Credenciales incorrectas';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Login</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <div class="terminal-window">

        <div class="term-content">

            <h1>Admin Login</h1>

            <?php if ($error): ?>

                <div class="log-console">
                    <div class="log-line">
                        <?= htmlspecialchars($error) ?>
                    </div>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="input-group">

                    <input
                        type="email"
                        name="email"
                        class="terminal-input"
                        placeholder="Correo"
                        required>

                </div>

                <br>

                <div class="input-group">

                    <input
                        type="password"
                        name="password"
                        class="terminal-input"
                        placeholder="Contraseña"
                        required>

                </div>

                <br>

                <button class="terminal-btn" type="submit">
                    Iniciar sesión
                </button>

            </form>

        </div>

    </div>

</body>

</html>