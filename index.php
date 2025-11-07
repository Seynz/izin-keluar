<?php
include 'koneksi.php'; 
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Izin Non-Kedinasan</title>
  <link rel="icon" type="image/png" href="assets/logo-brmp.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
      <img src="assets/logo-brmp.png" alt="Logo BRMP" height="40" class="me-2">
      Sistem Izin Non-Kedinasan
    </a>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white text-center">
          <h2 class="mb-0 d-flex align-items-center justify-content-center">
            <img src="assets/logo-brmp.png" alt="Logo BRMP" height="35" class="me-2">
            Selamat Datang
          </h2>
        </div>
        <div class="card-body text-center">
          <p class="lead mb-4">Silakan pilih menu di bawah untuk melanjutkan:</p>
          <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
            <a href="admin/login.php" class="btn btn-primary btn-lg px-4">Login Admin</a>
            <a href="form_izin.php" class="btn btn-success btn-lg px-4">Isi Form Izin</a>
            <a href="status_izin.php" class="btn btn-secondary btn-lg px-4">Lihat Status Izin</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="text-center py-3 mt-5 text-muted">
  &copy; <?= date('Y') ?> Sistem Izin Non-Kedinasan
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
