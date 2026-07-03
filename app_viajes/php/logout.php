<?php
session_start();

/* 🔴 destruir sesión */
$_SESSION = [];
session_destroy();

/* 🔌 cerrar conexión (opcional en PHP) */
$pdo = null;

/* 🔀 redirigir al login */
header("Location: ../index.php");
exit;