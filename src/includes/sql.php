<?php

/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/

function totalNewsletters(PDO $pdo): int
{
    return (int)$pdo
        ->query("
            SELECT COUNT(*)
            FROM newsletters
        ")
        ->fetchColumn();
}

function totalSuscriptores(PDO $pdo): int
{
    return (int)$pdo
        ->query("
            SELECT COUNT(*)
            FROM suscriptores
        ")
        ->fetchColumn();
}

function totalNewslettersPublicadas(PDO $pdo): int
{
    return (int)$pdo
        ->query("
            SELECT COUNT(*)
            FROM newsletters
            WHERE estado='publicado'
        ")
        ->fetchColumn();
}

/*

Procediemiento 

*/

function registrarSuscriptor(
    PDO $pdo,
    string $nombre,
    string $email
): int {

    $stmt = $pdo->prepare("
        INSERT INTO suscriptores
        (
            nombre,
            email,
            estado
        )
        VALUES
        (
            ?, ?, 'activo'
        )
    ");

    $stmt->execute([
        $nombre,
        $email
    ]);

    return (int)$pdo->lastInsertId();
}

function guardarCategoriasSuscriptor(
    PDO $pdo,
    int $suscriptorId,
    array $categorias
): void {

    foreach($categorias as $categoriaId) {

        $stmt = $pdo->prepare("
            INSERT INTO suscriptor_categorias
            (
                suscriptor_id,
                categoria_id
            )
            VALUES
            (
                ?, ?
            )
        ");

        $stmt->execute([
            $suscriptorId,
            $categoriaId
        ]);
    }
}

function obtenerCategoriasSuscriptor(PDO $pdo, int $suscriptorId): array 
{
    $stmt = $pdo->prepare("
        SELECT categoria_id
        FROM suscriptor_categorias
        WHERE suscriptor_id = ?
    ");
    $stmt->execute([$suscriptorId]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function actualizarCategoriasSuscriptor(PDO $pdo, int $suscriptorId, array $categorias): void 
{
    try {
        // 1. Iniciamos una transacción por seguridad y rendimiento
        $pdo->beginTransaction();

        // 2. Eliminamos las categorías anteriores
        $stmt = $pdo->prepare("
            DELETE FROM suscriptor_categorias
            WHERE suscriptor_id = ?
        ");
        $stmt->execute([$suscriptorId]);

        // 3. Preparamos el INSERT una sola vez (¡Fuera del bucle!)
        if (!empty($categorias)) {
            $stmt = $pdo->prepare("
                INSERT INTO suscriptor_categorias (suscriptor_id, categoria_id)
                VALUES (?, ?)
            ");

            // 4. Ejecutamos el mismo statement en el bucle
            foreach ($categorias as $categoriaId) {
                $stmt->execute([
                    $suscriptorId,
                    $categoriaId
                ]);
            }
        }

        // 5. Confirmamos los cambios si todo salió bien
        $pdo->commit();

    } catch (Exception $e) {
        // Si algo falla, revertimos para no dejar la base de datos inconsistente
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function obtenerSuscriptores(PDO $pdo): array
{
    // Usamos query() ya que es una consulta estática sin variables externas
    $stmt = $pdo->query("
        SELECT id, nombre, email, estado, fecha_suscripcion
        FROM suscriptores
        ORDER BY fecha_suscripcion DESC
    ");
    
    // Si la consulta falla por alguna razón, fetchAll podría fallar; 
    // nos aseguramos de retornar un array vacío en ese caso.
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function obtenerNombresCategoriasSuscriptor(PDO $pdo, int $suscriptorId): array 
{
    $stmt = $pdo->prepare("
        SELECT c.nombre
        FROM categorias c
        INNER JOIN suscriptor_categorias sc ON sc.categoria_id = c.id
        WHERE sc.suscriptor_id = :suscriptor_id
    ");

    // Es mejor práctica usar marcadores con nombre (:suscriptor_id) 
    // en lugar de signos de interrogación para mayor claridad.
    $stmt->execute([
        'suscriptor_id' => $suscriptorId
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function obtenerSuscriptorPorId(
    PDO $pdo,
    int $id
): ?array {

    $stmt = $pdo->prepare("
        SELECT *
        FROM suscriptores
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $suscriptor = $stmt->fetch(PDO::FETCH_ASSOC);

    return $suscriptor ?: null;
}

function obtenerSuscriptorCompleto(
    PDO $pdo,
    int $id
): ?array {

    $suscriptor = obtenerSuscriptorPorId(
        $pdo,
        $id
    );

    if (!$suscriptor) {
        return null;
    }

    $suscriptor['categorias'] =
        obtenerCategoriasSuscriptor(
            $pdo,
            $id
        );

    return $suscriptor;
}

function guardarSuscriptor(
    PDO $pdo,
    array $datos
): int {

    $categorias = $datos['categorias'] ?? [];

    if (!empty($datos['id'])) {

        actualizarSuscriptor(
            $pdo,
            (int)$datos['id'],
            trim($datos['nombre']),
            trim($datos['email']),
            $datos['estado']
        );

        actualizarCategoriasSuscriptor(
            $pdo,
            (int)$datos['id'],
            $categorias
        );

        return (int)$datos['id'];
    }

    $suscriptorId = registrarSuscriptor(
        $pdo,
        trim($datos['nombre']),
        trim($datos['email'])
    );

    actualizarCategoriasSuscriptor(
        $pdo,
        $suscriptorId,
        $categorias
    );

    return $suscriptorId;
}


/*

join de newsletters 

*/

function obtenerNewsletters(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            n.id,
            n.titulo,
            n.estado,
            n.creado_en,
            c.nombre AS categoria,
            u.nombre AS autor

        FROM newsletters n

        INNER JOIN categorias c
            ON c.id = n.categoria_id

        INNER JOIN usuarios u
            ON u.id = n.autor_id

        ORDER BY n.creado_en DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* trigger Registrar auditoria */

function registrarAuditoria(
    PDO $pdo,
    ?int $usuarioId,
    string $accion,
    string $tabla,
    ?int $registroId
): void {

    $stmt = $pdo->prepare("
        INSERT INTO auditoria
        (
            usuario_id,
            accion,
            tabla_afectada,
            registro_id
        )
        VALUES
        (
            ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $usuarioId,
        $accion,
        $tabla,
        $registroId
    ]);
}

/* variables de estadisticas del sistema */

function estadisticasSistema(PDO $pdo): array
{
    $totalNews = totalNewsletters($pdo);

    $totalSubs = totalSuscriptores($pdo);

    $totalPublicadas = totalNewslettersPublicadas($pdo);

    return [
        'newsletters' => $totalNews,
        'suscriptores' => $totalSubs,
        'publicadas' => $totalPublicadas
    ];
}

function obtenerNewsletterCompleta(
    PDO $pdo,
    int $id
): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            n.*,
            c.nombre AS categoria,
            u.nombre AS autor

        FROM newsletters n

        INNER JOIN categorias c
            ON c.id = n.categoria_id

        INNER JOIN usuarios u
            ON u.id = n.autor_id

        WHERE n.id = ?

        LIMIT 1
    ");

    $stmt->execute([$id]);

    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newsletter) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT e.nombre

        FROM etiquetas e

        INNER JOIN newsletter_etiquetas ne
            ON ne.etiqueta_id = e.id

        WHERE ne.newsletter_id = ?
    ");

    $stmt->execute([$id]);

    $newsletter['etiquetas'] =
        $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $newsletter;
}

/*CRUD NEWSLETTERS--------------------------------------------------------------------------*/

function crearNewsletter(
    PDO $pdo,
    int $autorId,
    int $categoriaId,
    string $titulo,
    string $contenido,
    string $estado
): bool {
    $stmt = $pdo->prepare("
        INSERT INTO newsletters (autor_id, categoria_id, titulo, contenido, estado)
        VALUES (?, ?, ?, ?, ?)
    ");

    $resultado = $stmt->execute([
        $autorId,
        $categoriaId,
        $titulo,
        $contenido,
        $estado
    ]);

    if ($resultado && function_exists('registrarAuditoria')) {
        registrarAuditoria(
            $pdo,
            $autorId,
            'INSERT',
            'newsletters',
            (int)$pdo->lastInsertId()
        );
    }

    if ($resultado) {

    registrarAuditoria(
        $pdo,
        $autorId,
        'INSERT',
        'newsletters',
        (int)$pdo->lastInsertId()
    );

    return (int)$pdo->lastInsertId();
}

return 0;
}

function actualizarNewsletter(
    PDO $pdo,
    int $id,
    int $categoriaId,
    string $titulo,
    string $contenido,
    string $estado,
    ?int $usuarioId = null
): bool {
    $stmt = $pdo->prepare("
        UPDATE newsletters
        SET categoria_id = ?, titulo = ?, contenido = ?, estado = ?
        WHERE id = ?
    ");

    $resultado = $stmt->execute([
        $categoriaId,
        $titulo,
        $contenido,
        $estado,
        $id
    ]);

    // Evita registrar auditoría si no se pasó un usuario válido
    if ($resultado && $usuarioId !== null && function_exists('registrarAuditoria')) {
        registrarAuditoria(
            $pdo,
            $usuarioId,
            'UPDATE',
            'newsletters',
            $id
        );
    }

    return $resultado;
}

function eliminarNewsletter(
    PDO $pdo,
    int $id,
    ?int $usuarioId = null
): bool {
    try {
        $pdo->beginTransaction();

        // 1. Eliminar relaciones en la tabla intermedia
        $stmt = $pdo->prepare("DELETE FROM newsletter_etiquetas WHERE newsletter_id = ?");
        $stmt->execute([$id]);

        // 2. Registrar la auditoría ANTES de borrar el registro (Por seguridad de integridad/ID)
        if ($usuarioId !== null && function_exists('registrarAuditoria')) {
            registrarAuditoria(
                $pdo,
                $usuarioId,
                'DELETE',
                'newsletters',
                $id
            );
        }

        // 3. Eliminar el registro principal
        $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
        $resultado = $stmt->execute([$id]);

        // Si por alguna razón el DELETE no afectó filas, podríamos lanzar excepción para hacer rollback
        if ($stmt->rowCount() === 0) {
            throw new Exception("No se encontró la newsletter para eliminar.");
        }

        $pdo->commit();
        return $resultado;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

/*CRUD SUSCRIPTORES--------------------------------------------------------------------------*/

function actualizarSuscriptor(
    PDO $pdo,
    int $id,
    string $nombre,
    string $email,
    string $estado
): bool {
    $stmt = $pdo->prepare("
        UPDATE suscriptores
        SET nombre = ?, email = ?, estado = ?
        WHERE id = ?
    ");

    return $stmt->execute([
        $nombre,
        $email,
        $estado,
        $id
    ]);
}

function eliminarSuscriptor(
    PDO $pdo,
    int $id
): bool {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM suscriptor_categorias WHERE suscriptor_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM suscriptores WHERE id = ?");
        $resultado = $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No se encontró el suscriptor para eliminar.");
        }

        $pdo->commit();
        return $resultado;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

function guardarEtiquetasNewsletter(
    PDO $pdo,
    int $newsletterId,
    string $etiquetasTexto
): void {

    $stmt = $pdo->prepare("
        DELETE FROM newsletter_etiquetas
        WHERE newsletter_id = ?
    ");

    $stmt->execute([$newsletterId]);

    $etiquetas = array_filter(
        array_map(
            'trim',
            explode(',', $etiquetasTexto)
        )
    );

    foreach ($etiquetas as $nombreEtiqueta) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM etiquetas
            WHERE nombre = ?
        ");

        $stmt->execute([$nombreEtiqueta]);

        $etiquetaId = $stmt->fetchColumn();

        if (!$etiquetaId) {

            $stmt = $pdo->prepare("
                INSERT INTO etiquetas
                (
                    nombre
                )
                VALUES
                (
                    ?
                )
            ");

            $stmt->execute([
                $nombreEtiqueta
            ]);

            $etiquetaId = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("
            INSERT INTO newsletter_etiquetas
            (
                newsletter_id,
                etiqueta_id
            )
            VALUES
            (
                ?, ?
            )
        ");

        $stmt->execute([
            $newsletterId,
            $etiquetaId
        ]);
    }
}

function obtenerCategorias(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT *
        FROM categorias
        ORDER BY nombre
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearCategoria(
    PDO $pdo,
    string $nombre
): bool {

    $stmt = $pdo->prepare("
        INSERT INTO categorias
        (
            nombre
        )
        VALUES
        (
            ?
        )
    ");

    return $stmt->execute([
        trim($nombre)
    ]);
}

function eliminarCategoria(
    PDO $pdo,
    int $id
): bool {

    $stmt = $pdo->prepare("
        DELETE FROM categorias
        WHERE id = ?
    ");

    return $stmt->execute([
        $id
    ]);
}

function obtenerEtiquetas(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT *
        FROM etiquetas
        ORDER BY nombre
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearEtiqueta(
    PDO $pdo,
    string $nombre
): bool {

    $stmt = $pdo->prepare("
        INSERT INTO etiquetas
        (
            nombre
        )
        VALUES
        (
            ?
        )
    ");

    return $stmt->execute([
        trim($nombre)
    ]);
}

function eliminarEtiqueta(
    PDO $pdo,
    int $id
): bool {

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            DELETE FROM newsletter_etiquetas
            WHERE etiqueta_id = ?
        ");

        $stmt->execute([$id]);

        $stmt = $pdo->prepare("
            DELETE FROM etiquetas
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $pdo->commit();

        return true;

    } catch(Exception $e) {

        $pdo->rollBack();

        return false;
    }
}


