<?php
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username && $password) {
        // Query akun berdasarkan username (prepared statement)
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $akun = $result->fetch_assoc();

        if ($akun && password_verify($password, $akun['password'])) {
            // Login sukses, set session dan regenerate id
            session_start();
            session_regenerate_id(true);
            $_SESSION['id_admin'] = $akun['id_admin'];
            $_SESSION['nama'] = $akun['nama'];
            $_SESSION['role'] = $akun['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<div class='alert alert-danger text-center'>Username atau password salah!</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-warning text-center'>Username dan password wajib diisi!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Admin</title>
  <link rel="icon" type="image/png" href="../assets/logo-logo-brmp.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-sm p-4 position-relative">
        <a href="../index.php" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Close"></a>
        <h3 class="mb-4 text-center d-flex align-items-center justify-content-center">
          <img src="../assets/logo-brmp.png" alt="Logo BRMP" height="30" class="me-2">
          Login Admin
        </h3>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-success w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
