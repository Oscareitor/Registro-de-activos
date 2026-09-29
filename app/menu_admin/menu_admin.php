<?php
session_start();
if (($_SESSION['rol'] ?? '') !== 'administrador') {
    header('Location: ../login/login.html');
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';

$errorInventario = null;
$activos = [];
$errorRegistro = null;
$errorEliminacion = null;
$datosRegistro = [
    'codigo' => '',
    'nombre' => '',
    'categoria' => '',
    'marca' => '',
    'modelo' => '',
    'serie' => '',
    'ubicacion' => '',
    'estado' => '',
    'responsable' => '',
    'observaciones' => '',
];

function longitudTexto(string $texto): int
{
    $cantidad = preg_match_all('/./us', $texto, $coincidencias);
    return $cantidad === false ? PHP_INT_MAX : $cantidad;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_activo') {
    $idEntrada = $_POST['id'] ?? '';
    $idActivo = is_string($idEntrada)
        ? filter_var($idEntrada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;

    if ($idActivo === false) {
        $errorEliminacion = 'No se pudo identificar el activo seleccionado.';
    } else {
        try {
            $eliminar = $conexion->prepare('DELETE FROM `activos` WHERE `#` = :id');
            $eliminar->bindValue(':id', $idActivo, PDO::PARAM_INT);
            $eliminar->execute();

            if ($eliminar->rowCount() === 1) {
                header('Location: menu_admin.php?eliminado=ok#inventario');
                exit;
            }

            $errorEliminacion = 'El activo seleccionado ya no existe.';
        } catch (PDOException $error) {
            error_log('Error al eliminar un activo: ' . $error->getMessage());
            $errorEliminacion = 'No se pudo eliminar el activo. Intenta de nuevo.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'registrar_activo') {
    foreach ($datosRegistro as $campo => $valor) {
        $entrada = $_POST[$campo] ?? '';
        $datosRegistro[$campo] = is_string($entrada) ? trim($entrada) : '';
    }

    foreach (['codigo', 'nombre', 'categoria', 'ubicacion', 'estado'] as $campoObligatorio) {
        if ($datosRegistro[$campoObligatorio] === '') {
            $errorRegistro = 'Completa todos los campos obligatorios.';
            break;
        }
    }

    $limites = [
        'codigo' => 50,
        'nombre' => 150,
        'categoria' => 100,
        'marca' => 100,
        'modelo' => 100,
        'serie' => 100,
        'ubicacion' => 150,
        'estado' => 30,
        'responsable' => 150,
    ];

    foreach ($limites as $campo => $limite) {
        if (longitudTexto($datosRegistro[$campo]) > $limite) {
            $errorRegistro = 'Uno o más campos superan la longitud permitida.';
            break;
        }
    }

    if ($errorRegistro === null) {
        try {
            $insertar = $conexion->prepare(
                'INSERT INTO `activos` (`Código del activo`, `Nombre del activo`, `Categoría`, `Marca`, `Modelo`, `Número de serie`, `Ubicación`, `Estado`, `Responsable`, `Observaciones`) VALUES (:codigo, :nombre, :categoria, :marca, :modelo, :serie, :ubicacion, :estado, :responsable, :observaciones)'
            );
            $parametros = [
                ':codigo' => $datosRegistro['codigo'],
                ':nombre' => $datosRegistro['nombre'],
                ':categoria' => $datosRegistro['categoria'],
                ':marca' => $datosRegistro['marca'] !== '' ? $datosRegistro['marca'] : null,
                ':modelo' => $datosRegistro['modelo'] !== '' ? $datosRegistro['modelo'] : null,
                ':serie' => $datosRegistro['serie'] !== '' ? $datosRegistro['serie'] : null,
                ':ubicacion' => $datosRegistro['ubicacion'],
                ':estado' => $datosRegistro['estado'],
                ':responsable' => $datosRegistro['responsable'] !== '' ? $datosRegistro['responsable'] : null,
                ':observaciones' => $datosRegistro['observaciones'] !== '' ? $datosRegistro['observaciones'] : null,
            ];

            foreach ($parametros as $parametro => $valor) {
                $insertar->bindValue(
                    $parametro,
                    $valor,
                    $valor === null ? PDO::PARAM_NULL : PDO::PARAM_STR
                );
            }
            $insertar->execute();

            header('Location: menu_admin.php?registro=ok#inventario');
            exit;
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                $errorRegistro = 'Ya existe un activo con ese código.';
            } else {
                error_log('Error al registrar un activo: ' . $error->getMessage());
                $errorRegistro = 'No se pudo guardar el activo. Intenta de nuevo.';
            }
        }
    }
}

$registroGuardado = ($_GET['registro'] ?? '') === 'ok';
$registroEliminado = ($_GET['eliminado'] ?? '') === 'ok';

try {
    $consulta = $conexion->query(
        'SELECT `#` AS `id`, `Nombre del activo`, `Código del activo`, `Número de serie`, `Categoría`, `Marca`, `Modelo`, `Ubicación`, `Estado`, `Responsable`, `Observaciones` FROM `activos` ORDER BY `#` DESC'
    );
    $activos = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    $errorInventario = 'No se pudo cargar el inventario. Verifica la conexión y la tabla de activos.';
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
    <title>Menú Administrador</title>
    <link rel="stylesheet" href="menu_admin.css">
</head>

<body>
    <div class="linea-superior"></div>

    <header class="header">
        <div class="titulo-header">
            <h1>Universidad San Juan de la Cruz</h1>
            <p>Control de activos</p>
        </div>

        <div class="usuario-header">
            <span class="nombre-usuario"><?= escapar($_SESSION['nombre_usuario'] ?? 'Administrador') ?></span>
            <div class="separador"></div>
            <button class="cerrar-sesion" type="button" onclick="window.location.href='../login/logout.php'">
                <img src="../assets/img/cerrar_sesion.png" alt="" class="icono-salir">
                Cerrar sesión
            </button>
        </div>
    </header>

    <main class="contenido">
        <section class="parte-superior">
            <div class="informacion">
                <h2>Inventario General</h2>
                <p>Consulta y administra los activos de la institución.</p>
            </div>

            <div class="botones">
                <button class="boton-registrar" id="abrirModalActivo" type="button" aria-haspopup="dialog" aria-controls="modalActivo">+ Registrar Activo</button>
                <button class="boton-escanear" type="button">
                    <img src="../assets/img/scanner.png" alt="" class="icono-escaner">
                    Escanear Código
                </button>
            </div>
        </section>

        <section class="inventario" id="inventario" aria-labelledby="titulo-inventario">
            <div class="inventario-encabezado">
                <h3 id="titulo-inventario">Activos registrados</h3>
                <?php if ($errorInventario === null): ?>
                    <span class="total-activos"><?= count($activos) ?> activos</span>
                <?php endif; ?>
            </div>

            <?php if ($registroGuardado): ?>
                <p class="mensaje-registro-exitoso" role="status">Activo registrado correctamente.</p>
            <?php endif; ?>
            <?php if ($registroEliminado): ?>
                <p class="mensaje-registro-exitoso" role="status">Activo eliminado correctamente.</p>
            <?php endif; ?>
            <?php if ($errorEliminacion !== null): ?>
                <p class="mensaje-registro-error" role="alert"><?= escapar($errorEliminacion) ?></p>
            <?php endif; ?>

            <?php if ($errorInventario !== null): ?>
                <p class="mensaje-inventario error-inventario" role="alert"><?= escapar($errorInventario) ?></p>
            <?php elseif ($activos === []): ?>
                <p class="mensaje-inventario">No hay activos registrados todavía.</p>
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
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activos as $activo): ?>
                                <tr>
                                    <?php foreach ($activo as $columna => $valor): ?>
                                        <?php if ($columna !== 'id'): ?>
                                            <td><?= $valor === null || $valor === '' ? '&mdash;' : escapar($valor) ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <td class="celda-acciones">
                                        <button
                                            class="boton-eliminar"
                                            type="button"
                                            data-id="<?= escapar($activo['id']) ?>"
                                            data-nombre="<?= escapar($activo['Nombre del activo']) ?>"
                                            aria-haspopup="dialog"
                                            aria-controls="modalConfirmarEliminacion"
                                        >Eliminar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <dialog class="modal-activo" id="modalActivo" aria-labelledby="tituloModalActivo" data-reabrir="<?= $errorRegistro !== null ? 'true' : 'false' ?>">
        <form class="formulario-activo" method="post" action="menu_admin.php">
            <div class="encabezado-modal-activo">
                <div>
                    <h2 id="tituloModalActivo">Registrar activo</h2>
                    <p>Los campos marcados con * son obligatorios.</p>
                </div>
                <button class="cerrar-modal-activo" id="cerrarModalActivo" type="button" aria-label="Cerrar ventana">&times;</button>
            </div>

            <?php if ($errorRegistro !== null): ?>
                <p class="mensaje-registro-error" role="alert"><?= escapar($errorRegistro) ?></p>
            <?php endif; ?>

            <input type="hidden" name="accion" value="registrar_activo">
            <div class="campos-activo">
                <label class="campo-activo">
                    <span>Código del activo *</span>
                    <input name="codigo" type="text" maxlength="50" value="<?= escapar($datosRegistro['codigo']) ?>" required>
                </label>
                <label class="campo-activo">
                    <span>Nombre del activo *</span>
                    <input name="nombre" type="text" maxlength="150" value="<?= escapar($datosRegistro['nombre']) ?>" required>
                </label>
                <label class="campo-activo">
                    <span>Categoría *</span>
                    <input name="categoria" type="text" maxlength="100" value="<?= escapar($datosRegistro['categoria']) ?>" required>
                </label>
                <label class="campo-activo">
                    <span>Ubicación *</span>
                    <input name="ubicacion" type="text" maxlength="150" value="<?= escapar($datosRegistro['ubicacion']) ?>" required>
                </label>
                <label class="campo-activo">
                    <span>Estado *</span>
                    <input name="estado" type="text" maxlength="30" value="<?= escapar($datosRegistro['estado']) ?>" required>
                </label>
                <label class="campo-activo">
                    <span>Número de serie</span>
                    <input name="serie" type="text" maxlength="100" value="<?= escapar($datosRegistro['serie']) ?>">
                </label>
                <label class="campo-activo">
                    <span>Marca</span>
                    <input name="marca" type="text" maxlength="100" value="<?= escapar($datosRegistro['marca']) ?>">
                </label>
                <label class="campo-activo">
                    <span>Modelo</span>
                    <input name="modelo" type="text" maxlength="100" value="<?= escapar($datosRegistro['modelo']) ?>">
                </label>
                <label class="campo-activo">
                    <span>Responsable</span>
                    <input name="responsable" type="text" maxlength="150" value="<?= escapar($datosRegistro['responsable']) ?>">
                </label>
                <label class="campo-activo campo-activo-completo">
                    <span>Observaciones</span>
                    <textarea name="observaciones" rows="3"><?= escapar($datosRegistro['observaciones']) ?></textarea>
                </label>
            </div>

            <div class="acciones-modal-activo">
                <button class="boton-cancelar-activo" id="cancelarModalActivo" type="button">Cancelar</button>
                <button class="boton-guardar-activo" type="submit">Guardar activo</button>
            </div>
        </form>
    </dialog>

    <dialog class="modal-activo modal-confirmar-eliminacion" id="modalConfirmarEliminacion" aria-labelledby="tituloConfirmarEliminacion" aria-describedby="mensajeConfirmarEliminacion">
        <form class="formulario-activo formulario-confirmacion" method="post" action="menu_admin.php#inventario">
            <div class="encabezado-modal-activo">
                <div>
                    <h2 id="tituloConfirmarEliminacion">Eliminar activo</h2>
                    <p id="mensajeConfirmarEliminacion">Esta acción no se puede deshacer.</p>
                </div>
                <button class="cerrar-modal-activo" id="cerrarConfirmacionEliminacion" type="button" aria-label="Cerrar ventana">&times;</button>
            </div>

            <p class="nombre-activo-eliminar" id="nombreActivoEliminar"></p>
            <input type="hidden" name="accion" value="eliminar_activo">
            <input type="hidden" name="id" id="idActivoEliminar">

            <div class="acciones-modal-activo">
                <button class="boton-cancelar-activo" id="cancelarConfirmacionEliminacion" type="button">Cancelar</button>
                <button class="boton-confirmar-eliminacion" type="submit">Eliminar activo</button>
            </div>
        </form>
    </dialog>

    <script>
        const modalActivo = document.getElementById('modalActivo');
        document.getElementById('abrirModalActivo').addEventListener('click', () => modalActivo.showModal());
        document.getElementById('cerrarModalActivo').addEventListener('click', () => modalActivo.close());
        document.getElementById('cancelarModalActivo').addEventListener('click', () => modalActivo.close());

        if (modalActivo.dataset.reabrir === 'true') {
            modalActivo.showModal();
        }

        const modalConfirmarEliminacion = document.getElementById('modalConfirmarEliminacion');
        const idActivoEliminar = document.getElementById('idActivoEliminar');
        const nombreActivoEliminar = document.getElementById('nombreActivoEliminar');

        document.querySelectorAll('.boton-eliminar').forEach((boton) => {
            boton.addEventListener('click', () => {
                idActivoEliminar.value = boton.dataset.id;
                nombreActivoEliminar.textContent = boton.dataset.nombre;
                modalConfirmarEliminacion.showModal();
            });
        });

        document.getElementById('cerrarConfirmacionEliminacion').addEventListener('click', () => modalConfirmarEliminacion.close());
        document.getElementById('cancelarConfirmacionEliminacion').addEventListener('click', () => modalConfirmarEliminacion.close());
    </script>
</body>
</html>