<?php

/*
|--------------------------------------------------------------------------
| CONTEXTO 1: USO DE VARIABLES Y MÉTRICAS ESCALARES
|--------------------------------------------------------------------------
| Conceptos:
|   - Funciones SQL/PHP de agregación para conteo de datos (COUNT).
|   - Variables Locales: Almacenan en memoria dinámica las métricas calculadas.
|   - Estructura compuesta: Unificación de variables en un array asociativo.
|--------------------------------------------------------------------------
*/

/**
 * Cuenta el total de newsletters registradas.
 */
function totalNewsletters(PDO $pdo): int
{
    return (int)$pdo
        ->query("SELECT COUNT(*) FROM newsletters")
        ->fetchColumn();
}

/**
 * Cuenta el total de suscriptores registrados.
 */
function totalSuscriptores(PDO $pdo): int
{
    return (int)$pdo
        ->query("SELECT COUNT(*) FROM suscriptores")
        ->fetchColumn();
}

/**
 * Cuenta las newsletters que están publicadas.
 */
function totalNewslettersPublicadas(PDO $pdo): int
{
    return (int)$pdo
        ->query("SELECT COUNT(*) FROM newsletters WHERE estado='publicado'")
        ->fetchColumn();
}

/**
 * Orquestador de estadísticas globales del sistema.
 * Aplica VARIABLES LOCALES para retener los resultados antes del retorno masivo.
 */
function estadisticasSistema(PDO $pdo): array
{
    // VARIABLES LOCALES: Guardan temporalmente los datos devueltos por la BD
    $totalNews       = totalNewsletters($pdo);
    $totalSubs       = totalSuscriptores($pdo);
    $totalPublicadas = totalNewslettersPublicadas($pdo);

    return [
        'newsletters'  => $totalNews,
        'suscriptores' => $totalSubs,
        'publicadas'   => $totalPublicadas
    ];
}


/*
|--------------------------------------------------------------------------
| CONTEXTO 2: QUERIES AVANZADAS MEDIANTE RELACIONES (JOINS)
|--------------------------------------------------------------------------
| Conceptos:
|   - INNER JOIN: Vinculación física obligatoria entre múltiples tablas relacionales.
|   - Alias de columnas (AS): Normalización léxica de campos ambiguos.
|--------------------------------------------------------------------------
*/

/**
 * CONSULTA GENERAL CON INNER JOIN MÚLTIPLE
 * Resuelve las llaves foráneas uniendo la newsletter con su categoría y su autor.
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
        INNER JOIN categorias c ON c.id = n.categoria_id
        INNER JOIN usuarios u   ON u.id = n.autor_id
        ORDER BY n.creado_en DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * CONSULTA ESPECÍFICA CON INNER JOIN FILTRADO
 * Resuelve una relación de muchos a muchos para traer los nombres de categorías de un suscriptor.
 */
function obtenerNombresCategoriasSuscriptor(PDO $pdo, int $suscriptorId): array 
{
    $stmt = $pdo->prepare("
        SELECT c.nombre
        FROM categorias c
        INNER JOIN suscriptor_categorias sc ON sc.categoria_id = c.id
        WHERE sc.suscriptor_id = :suscriptor_id
    ");

    $stmt->execute([
        'suscriptor_id' => $suscriptorId
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * CONSULTA COMPUESTA POR ID CON INNER JOIN
 * Extrae la información base cruzada de la newsletter y le concatena sus etiquetas relacionadas.
 */
function obtenerNewsletterCompleta(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            n.*,
            c.nombre AS categoria,
            u.nombre AS autor
        FROM newsletters n
        INNER JOIN categorias c ON c.id = n.categoria_id
        INNER JOIN usuarios u   ON u.id = n.autor_id
        WHERE n.id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newsletter) {
        return null;
    }

    // Consulta secundaria mediante JOIN a tabla intermedia
    $stmt = $pdo->prepare("
        SELECT e.nombre
        FROM etiquetas e
        INNER JOIN newsletter_etiquetas ne ON ne.etiqueta_id = e.id
        WHERE ne.newsletter_id = ?
    ");

    $stmt->execute([$id]);
    $newsletter['etiquetas'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $newsletter;
}


/*
|--------------------------------------------------------------------------
| CONTEXTO 3: FUNCIONES DE CONSULTA (READ DEL CRUD / MÓDULOS POR ID)
|--------------------------------------------------------------------------
| Conceptos:
|   - Encapsulamiento de lógica de lectura de registros específicos y generales.
|   - Retornos estructurados con control de nulidad (`?array`).
|--------------------------------------------------------------------------
*/

function obtenerSuscriptores(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, nombre, email, estado, fecha_suscripcion
        FROM suscriptores
        ORDER BY fecha_suscripcion DESC
    ");
    
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function obtenerSuscriptorPorId(PDO $pdo, int $id): ?array 
{
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

function obtenerSuscriptorCompleto(PDO $pdo, int $id): ?array 
{
    $suscriptor = obtenerSuscriptorPorId($pdo, $id);

    if (!$suscriptor) {
        return null;
    }

    $suscriptor['categorias'] = obtenerCategoriasSuscriptor($pdo, $id);

    return $suscriptor;
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

function obtenerCategorias(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT *
        FROM categorias
        ORDER BY nombre
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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


/*
|--------------------------------------------------------------------------
| CONTEXTO 4: PROCEDIMIENTOS COMPUESTOS DE PERSISTENCIA Y ESCRITURA
|--------------------------------------------------------------------------
| Conceptos:
|   - Transacciones explícitas (`beginTransaction`, `commit`, `rollBack`).
|   - Mutaciones CRUD seguras garantizando integridad referencial.
|   - Estructuras lógicas de control iterativo (`foreach`).
|--------------------------------------------------------------------------
*/

function registrarSuscriptor(PDO $pdo, string $nombre, string $email): int 
{
    $stmt = $pdo->prepare("
        INSERT INTO suscriptores (nombre, email, estado)
        VALUES (?, ?, 'activo')
    ");

    $stmt->execute([$nombre, $email]);
    return (int)$pdo->lastInsertId();
}

function actualizarSuscriptor(PDO $pdo, int $id, string $nombre, string $email, string $estado): bool 
{
    $stmt = $pdo->prepare("
        UPDATE suscriptores
        SET nombre = ?, email = ?, estado = ?
        WHERE id = ?
    ");

    return $stmt->execute([$nombre, $email, $estado, $id]);
}

/**
 * PROCEDIMIENTO COMPUESTO TRANSACCIONAL
 */
function actualizarCategoriasSuscriptor(PDO $pdo, int $suscriptorId, array $categorias): void 
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            DELETE FROM suscriptor_categorias
            WHERE suscriptor_id = ?
        ");
        $stmt->execute([$suscriptorId]);

        if (!empty($categorias)) {
            $stmt = $pdo->prepare("
                INSERT INTO suscriptor_categorias (suscriptor_id, categoria_id)
                VALUES (?, ?)
            ");

            foreach ($categorias as $categoriaId) {
                $stmt->execute([$suscriptorId, $categoriaId]);
            }
        }

        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * PROCEDIMIENTO LÓGICO INTELIGENTE (Save/Upsert)
 */
function guardarSuscriptor(PDO $pdo, array $datos): int 
{
    $categorias = $datos['categorias'] ?? [];

    if (!empty($datos['id'])) {
        actualizarSuscriptor($pdo, (int)$datos['id'], trim($datos['nombre']), trim($datos['email']), $datos['estado']);
        actualizarCategoriasSuscriptor($pdo, (int)$datos['id'], $categorias);
        return (int)$datos['id'];
    }

    $suscriptorId = registrarSuscriptor($pdo, trim($datos['nombre']), trim($datos['email']));
    actualizarCategoriasSuscriptor($pdo, $suscriptorId, $categorias);

    return $suscriptorId;
}

/**
 * PROCEDIMIENTO DE PARSEO Y AUTOMATIZACIÓN DE CATÁLOGOS
 */
function guardarEtiquetasNewsletter(PDO $pdo, int $newsletterId, string $etiquetasTexto): void 
{
    $stmt = $pdo->prepare("DELETE FROM newsletter_etiquetas WHERE newsletter_id = ?");
    $stmt->execute([$newsletterId]);

    $etiquetas = array_filter(array_map('trim', explode(',', $etiquetasTexto)));

    foreach ($etiquetas as $nombreEtiqueta) {
        $stmt = $pdo->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
        $stmt->execute([$nombreEtiqueta]);
        $etiquetaId = $stmt->fetchColumn();

        if (!$etiquetaId) {
            $stmt = $pdo->prepare("INSERT INTO etiquetas (nombre) VALUES (?)");
            $stmt->execute([$nombreEtiqueta]);
            $etiquetaId = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("
            INSERT INTO newsletter_etiquetas (newsletter_id, etiqueta_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$newsletterId, $etiquetaId]);
    }
}

function guardarCategoriasSuscriptor(PDO $pdo, int $suscriptorId, array $categorias): void 
{
    foreach($categorias as $categoriaId) {
        $stmt = $pdo->prepare("
            INSERT INTO suscriptor_categorias (suscriptor_id, categoria_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$suscriptorId, $categoriaId]);
    }
}

function crearCategoria(PDO $pdo, string $nombre): bool 
{
    $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
    return $stmt->execute([trim($nombre)]);
}

function crearEtiqueta(PDO $pdo, string $nombre): bool 
{
    $stmt = $pdo->prepare("INSERT INTO etiquetas (nombre) VALUES (?)");
    return $stmt->execute([trim($nombre)]);
}


/*
|--------------------------------------------------------------------------
| CONTEXTO 5: ELIMINACIONES SEGURAS (D DEL CRUD)
|--------------------------------------------------------------------------
*/

function eliminarSuscriptor(PDO $pdo, int $id): bool 
{
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

function eliminarCategoria(PDO $pdo, int $id): bool 
{
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    return $stmt->execute([$id]);
}

function eliminarEtiqueta(PDO $pdo, int $id): bool 
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM newsletter_etiquetas WHERE etiqueta_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM etiquetas WHERE id = ?");
        $resultado = $stmt->execute([$id]);

        $pdo->commit();
        return $resultado;

    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}


/*
|--------------------------------------------------------------------------
| CONTEXTO 6: EMULACIÓN DE TRIGGERS POR SOFTWARE (Log de Auditoría)
|--------------------------------------------------------------------------
| Conceptos:
|   - Trigger de Aplicación: Reacciones automáticas inmediatas ante mutaciones.
|   - Registro inmutable de trazas históricas de seguridad.
|--------------------------------------------------------------------------
*/

/**
 * ACCIÓN CENTRAL DEL DISPARADOR (Inyección en Tabla de Auditoría)
 */
function registrarAuditoria(PDO $pdo, ?int $usuarioId, string $accion, string $tabla, ?int $registroId): void 
{
    $stmt = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$usuarioId, $accion, $tabla, $registroId]);
}

/**
 * OPERACIÓN CON DISPARADOR POST-INSERT
 */
function crearNewsletter(PDO $pdo, int $autorId, int $categoriaId, string $titulo, string $contenido, string $estado): bool 
{
    $stmt = $pdo->prepare("
        INSERT INTO newsletters (autor_id, categoria_id, titulo, contenido, estado)
        VALUES (?, ?, ?, ?, ?)
    ");

    $resultado = $stmt->execute([$autorId, $categoriaId, $titulo, $contenido, $estado]);

    // COMPORTAMIENTO TRIGGER: Registra automáticamente la traza tras confirmar el éxito
    if ($resultado && function_exists('registrarAuditoria')) {
        $nuevoId = (int)$pdo->lastInsertId();
        registrarAuditoria($pdo, $autorId, 'INSERT', 'newsletters', $nuevoId);
        return $nuevoId;
    }

    return 0;
}

/**
 * OPERACIÓN CON DISPARADOR POST-UPDATE
 */
function actualizarNewsletter(PDO $pdo, int $id, int $categoriaId, string $titulo, string $contenido, string $estado, ?int $usuarioId = null): bool 
{
    $stmt = $pdo->prepare("
        UPDATE newsletters
        SET categoria_id = ?, titulo = ?, contenido = ?, estado = ?
        WHERE id = ?
    ");

    $resultado = $stmt->execute([$categoriaId, $titulo, $contenido, $estado, $id]);

    // COMPORTAMIENTO TRIGGER: Registra el cambio si existe un usuario logueado en la sesión
    if ($resultado && $usuarioId !== null && function_exists('registrarAuditoria')) {
        registrarAuditoria($pdo, $usuarioId, 'UPDATE', 'newsletters', $id);
    }

    return $resultado;
}

/**
 * OPERACIÓN CON DISPARADOR POST-DELETE
 */
function eliminarNewsletter(PDO $pdo, int $id, ?int $usuarioId = null): bool 
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM newsletter_etiquetas WHERE newsletter_id = ?");
        $stmt->execute([$id]);

        // COMPORTAMIENTO TRIGGER PRE-DELETE: Guarda el rastro histórico antes de eliminar el registro primario
        if ($usuarioId !== null && function_exists('registrarAuditoria')) {
            registrarAuditoria($pdo, $usuarioId, 'DELETE', 'newsletters', $id);
        }

        $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
        $resultado = $stmt->execute([$id]);

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

/*
|--------------------------------------------------------------------------
| CONTEXTO 7: AUTENTICACIÓN Y SEGURIDAD
|--------------------------------------------------------------------------
*/

function obtenerUsuarioPorEmail(
    PDO $pdo,
    string $email
): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            u.*,
            r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r
            ON r.id = u.rol_id
        WHERE u.email = ?
        LIMIT 1
    ");

    $stmt->execute([
        trim($email)
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    return $usuario ?: null;
}

function autenticarUsuario(
    PDO $pdo,
    string $email,
    string $password
): ?array
{
    $usuario = obtenerUsuarioPorEmail(
        $pdo,
        $email
    );

    if (
        $usuario &&
        password_verify(
            $password,
            $usuario['password_hash']
        )
    ) {
        return $usuario;
    }

    return null;
}

function emailValido(string $email): bool
{
    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}

function textoValido(string $texto): bool
{
    return preg_match(
        '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_@.]+$/u',
        $texto
    ) === 1;
}