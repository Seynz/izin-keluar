<?php
include '../koneksi.php';
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}


$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_admin = $_SESSION['id_admin'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konfirmasi = $_POST['password_konfirmasi'];

    // Ambil password lama dari database
    $stmt = $conn->prepare("SELECT password FROM admin WHERE id_admin = ?");
    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    $stmt->bind_result($password_hash_db);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($password_lama, $password_hash_db)) {
        $alert = "<div class='alert alert-danger'>Password lama salah!</div>";
    } elseif ($password_baru !== $password_konfirmasi) {
        $alert = "<div class='alert alert-warning'>Konfirmasi password baru tidak cocok!</div>";
    } elseif (strlen($password_baru) < 6) {
        $alert = "<div class='alert alert-warning'>Password baru minimal 6 karakter!</div>";
    } else {
        $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
        $stmt->bind_param("si", $password_hash_baru, $id_admin);
        if ($stmt->execute()) {
            $alert = "<div class='alert alert-success'>Password berhasil diubah!</div>";
        } else {
            $alert = "<div class='alert alert-danger'>Gagal mengubah password!</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ubah Password Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-brmp.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
        <img src="../assets/logo-brmp.png" alt="LogBRMP" height="40" class="me-2">
        Dashboard Admin
      </a>
    </div>
  </nav>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white text-center">
            <h4 class="mb-0 d-flex align-items-center justify-content-center">
              <img src="../assets/logo-brmp.png" alt="Logo BRMP" height="30" class="me-2">
              Ubah Password
            </h4>
          </div>
          <div class="card-body">
            <?php echo $alert; ?>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="password_lama" class="form-control" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password_baru" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_konfirmasi" class="form-control" required>
              </div>
              <div class="d-flex justify-content-between">
                <a href="akun.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-success">Ubah Password</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>