<?php
session_start();
if (($_SESSION['rol'] ?? '') !== 'usuario') {
    header('Location: ../login/login.html');
    exit;
}

$nombreUsuario = (string) $_SESSION['nombre_usuario'];
require_once __DIR__ . '/../../config/conexion.php';

$errorInventario = null;
$activos = [];

try {
    $consulta = $conexion->prepare(
        'SELECT `Nombre del activo`, `Código del activo`, `Número de serie`, `Categoría`, `Marca`, `Modelo`, `Ubicación`, `Estado`, `Responsable`, `Observaciones` FROM `activos` WHERE LOWER(TRIM(COALESCE(`Responsable`, ""))) IN ("", "no aplica") OR LOWER(TRIM(`Responsable`)) = LOWER(:responsable) ORDER BY `#` DESC'
    );
    $consulta->execute([':responsable' => $nombreUsuario]);
    $activos = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    error_log('Error al cargar el inventario para usuarios: ' . $error->getMessage());
    $errorInventario = 'No se pudo cargar el inventario. Intenta de nuevo más tarde.';
}

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de activos</title>
    <link rel="stylesheet" href="menu_usuario.css">
</head>
<body>
    <div class="linea-superior"></div>

    <header class="header">
        <div class="titulo-header">
            <h1>Universidad San Juan de la Cruz</h1>
            <p>Control de activos</p>
        </div>

        <div class="usuario-header">
            <span><?= escapar($nombreUsuario) ?></span>
            <div class="separador"></div>
            <button class="cerrar-sesion" type="button" onclick="window.location.href='../login/logout.php'">
                <img src="../assets/img/cerrar_sesion.png" class="icono-salir" alt="">
                Cerrar sesión
            </button>
        </div>
    </header>

    <main class="contenido">
        <section class="parte-superior">
            <div class="informacion">
                <h2>Mis activos</h2>
                <p>Consulta los activos asignados a tu usuario.</p>
            </div>
            <button class="boton-escanear" type="button">
                <img src="../assets/img/scanner.png" class="icono-escaner" alt="">
                Escanear Código
            </button>
        </section>

        <section class="inventario" id="inventario" aria-labelledby="titulo-inventario">
            <div class="inventario-encabezado">
                <h3 id="titulo-inventario">Activos asignados</h3>
                <?php if ($errorInventario === null): ?>
                    <span class="total-activos"><?= count($activos) ?> activos</span>
                <?php endif; ?>
            </div>

            <?php if ($errorInventario !== null): ?>
                <p class="mensaje-inventario error-inventario" role="alert"><?= escapar($errorInventario) ?></p>
            <?php elseif ($activos === []): ?>
                <p class="mensaje-inventario">No hay activos asignados a tu usuario.</p>
            <?php else: ?>
                <div class="tabla-contenedor">
                    <table class="tabla-activos">
                        <thead>
                            <tr>
                                <th scope="col">Activo</th>
                                <th scope="col">Código</th>
                                <th scope="col">Número de serie</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Marca</th>
                                <th scope="col">Modelo</th>
                                <th scope="col">Ubicación</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Responsable</th>
                                <th scope="col">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activos as $activo): ?>
                                <tr>
                                    <?php foreach ($activo as $valor): ?>
                                        <td><?= $valor === null || $valor === '' ? '&mdash;' : escapar($valor) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>