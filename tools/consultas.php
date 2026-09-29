<?php
require_once __DIR__ . '/../config/conexion.php';

$consulta = $conexion->query('SELECT * FROM `activos`');
$activos = $consulta->fetchAll(PDO::FETCH_ASSOC);

print_r($activos);
