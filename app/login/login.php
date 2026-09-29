<?php
session_start();

$credenciales = require __DIR__ . '/../../config/usuarios.php';
$usuarioEntrada = $_POST['usuario'] ?? '';
$contrasenaEntrada = $_POST['codigo'] ?? '';
$usuario = is_string($usuarioEntrada) ? strtolower(trim($usuarioEntrada)) : '';
$contrasena = is_string($contrasenaEntrada) ? $contrasenaEntrada : '';
$cuenta = $credenciales['cuentas'][$usuario] ?? null;

if (!is_array($cuenta) || !password_verify($contrasena, $credenciales['password_hash'])) {
    header('Location: login.html?error=1');
    exit;
}

session_regenerate_id(true);
$_SESSION['nombre_usuario'] = $cuenta['nombre'];
$_SESSION['rol'] = $cuenta['rol'];

if ($cuenta['rol'] === 'administrador') {
    header('Location: ../menu_admin/menu_admin.php');
    exit;
}

header('Location: ../menu_usuario/menu_usuario.php');
exit;