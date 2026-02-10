<?php

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'bsm_web';

$koneksi = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($koneksi->connect_errno) {
    exit('Koneksi database gagal: ' . $koneksi->connect_error);
}

if (! $koneksi->set_charset('utf8mb4')) {
    exit('Gagal mengatur charset koneksi.');
}

