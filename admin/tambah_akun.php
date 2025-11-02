<?php
include '../koneksi.php';

// session_start();
// if (!isset($_SESSION['id_admin'])) {
//     header("Location: login.php");
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $nip = trim($_POST['nip']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($nama && $nip && $username && $password && $role) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (nama, nip, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $nip, $username, $password_hash, $role);

        if ($stmt->execute()) {
            $alert = "<div class='alert alert-success'>Akun berhasil ditambahkan!</div>";
        } else {
            $alert = "<div class='alert alert-danger'>Gagal menambah akun: {$stmt->error}</div>";
        }
        $stmt->close();
    } else {
        $alert = "<div class='alert alert-warning'>Semua kolom wajib diisi!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Akun Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-uho.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white text-center">
            <h4 class="mb-0 d-flex align-items-center justify-content-center">
              <img src="../assets/logo-uho.png" alt="Logo UHO" height="30" class="me-2">
              Tambah Akun Admin
            </h4>
          </div>
          <div class="card-body">
            <?php if (isset($alert)) echo $alert; ?>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" name="nama" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">NIP</label>
                <input type="text" name="nip" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                  <option value="" disabled selected>Pilih Role</option>
                  <option value="atasan">Atasan</option>
                  <option value="superadmin">Superadmin</option>
                  <option value="hrd">HRD</option>
                  <!-- Tambahkan opsi lain sesuai enum di database -->
                </select>
              </div>
              <div class="d-flex justify-content-between">
                <a href="akun.php" class="btn btn-secondary">Kembali</a>
                <button type="submit" class="btn btn-success">Tambah Akun</button>
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