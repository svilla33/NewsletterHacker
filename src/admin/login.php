<?php

session_start();

require_once '../includes/conexion.php';
require_once '../includes/sql.php';

$error = '';

$_SESSION['intentos'] ??= 0;

if (
    isset($_SESSION['bloqueado_hasta']) &&
    time() < $_SESSION['bloqueado_hasta']
) {

    $segundos =
        $_SESSION['bloqueado_hasta']
        - time();

    $error =
        "Cuenta bloqueada. Espera {$segundos} segundos.";
}

if (
    empty($error) &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $email = trim(
        $_POST['email'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';

    if (
        empty($email) ||
        empty($password)
    ) {

        $error =
            'Todos los campos son obligatorios.';
    }

    elseif (!emailValido($email)) {

        $error =
            'Correo electrónico inválido.';
    }

    else {

        $usuario = autenticarUsuario(
            $pdo,
            $email,
            $password
        );

        if ($usuario) {

            $_SESSION['intentos'] = 0;

            unset(
                $_SESSION['bloqueado_hasta']
            );

            $_SESSION['usuario_id'] =
                $usuario['id'];

            $_SESSION['usuario_nombre'] =
                $usuario['nombre'];

            $_SESSION['usuario_rol'] =
                $usuario['rol'];

            header(
                'Location: dashboard.php'
            );

            exit;
        }

        $_SESSION['intentos']++;

        if (
            $_SESSION['intentos'] >= 3
        ) {

            $_SESSION['bloqueado_hasta'] =
                time() + 300;

            $error =
                'Demasiados intentos fallidos. Cuenta bloqueada durante 5 minutos.';
        }

        else {

            $restantes =
                3 - $_SESSION['intentos'];

            $error =
                "Credenciales incorrectas. Intentos restantes: {$restantes}";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Login</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

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

                <button
                    class="terminal-btn"
                    type="submit">

                    Iniciar sesión

                </button>

            </form>

        </div>

    </div>

</body>

</html>