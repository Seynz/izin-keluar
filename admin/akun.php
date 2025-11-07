<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Modifikasi Akun Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-brmp.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
        <img src="../assets/logo-brmp.png" alt="Logo BRMP" height="40" class="me-2">
        Dashboard Admin
      </a>
      <div class="d-flex">
        <a href="dashboard.php" class="btn btn-outline-light ms-2">Kembali</a>
      </div>
    </div>
  </nav>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white text-center">
            <h4 class="mb-0 d-flex align-items-center justify-content-center">
              <img src="../assets/logo-brmp.png" alt="Logo BRMP" height="30" class="me-2">
              Modifikasi Akun
            </h4>
          </div>
          <div class="card-body text-center">
            <p class="mb-4">Silakan pilih aksi berikut untuk memodifikasi akun:</p>
            <div class="d-flex flex-column gap-3 mb-4">
              <a href="tambah_akun.php" class="btn btn-primary btn-lg">Tambah Akun</a>
              <a href="ubah_password.php" class="btn btn-warning btn-lg">Ubah Password Akun</a>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>