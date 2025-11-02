<?php
include '../koneksi.php';
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

$per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Hitung total data
$tanggal_hari_ini = date('Y-m-d');
$count_sql = "SELECT COUNT(*) as total FROM izin i 
              JOIN anggota a ON i.id_anggota = a.id_anggota 
              WHERE i.tanggal_izin = '$tanggal_hari_ini'";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT i.*, a.nama, a.nip 
        FROM izin i 
        JOIN anggota a ON i.id_anggota = a.id_anggota 
        WHERE i.tanggal_izin = '$tanggal_hari_ini'
        ORDER BY 
            CASE WHEN i.status = 'pending' THEN 0 ELSE 1 END,
            i.id_izin DESC
            LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
// Ambil izin hari ini
// $tanggal_hari_ini = date('Y-m-d');
// $sql = "SELECT i.*, a.nama, a.nip 
//         FROM izin i 
//         JOIN anggota a ON i.id_anggota = a.id_anggota 
//         WHERE i.tanggal_izin = '$tanggal_hari_ini'
//         ORDER BY 
//             CASE WHEN i.status = 'pending' THEN 0 ELSE 1 END,
//             i.id_izin DESC";
// $result = $conn->query($sql);

// Proses update status izin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_izin'])) {
    $id_izin = $_POST['id_izin'];
    $status = $_POST['status'];
    $catatan_admin = trim($_POST['catatan_admin']);
    $nama_admin = $_SESSION['nama'];

    // Tambahkan nama admin ke catatan
    if ($catatan_admin) {
        if (strpos($catatan_admin, $nama_admin) === false) {
            $catatan_admin .= " (oleh: $nama_admin)";
        }
    } else {
        $catatan_admin = "Diubah oleh: $nama_admin";
    }

    $stmt = $conn->prepare("UPDATE izin SET status=?, catatan_admin=?, tanggal_respon=NOW() WHERE id_izin=?");
    $stmt->bind_param("ssi", $status, $catatan_admin, $id_izin);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-uho.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <img src="../assets/logo-uho.png" alt="Logo UHO" height="40" class="me-2">
        Dashboard Admin
      </a>
      <div class="d-flex">
        <a href="logout.php" class="btn btn-outline-light ms-2">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row mb-4">
      <div class="col-md-8">
        <a href="riwayat.php" class="btn btn-info me-2 mb-2">Lihat Riwayat</a>
        <a href="modifikasi_anggota.php" class="btn btn-primary me-2 mb-2">Modifikasi Anggota</a>
        <a href="akun.php" class="btn btn-success mb-2">Akun</a>
      </div>
      <div class="col-md-4 text-end">
        <span class="fw-bold">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></span>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-success text-white text-center">
        <h4 class="mb-0 d-flex align-items-center justify-content-center">
          <img src="../assets/logo-uho.png" alt="Logo UHO" height="30" class="me-2">
          Status Izin Hari Ini (<?= date('d-m-Y') ?>)
        </h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>No</th>
                <th>Nama / NIP</th>
                <th>Jam Keluar</th>
                <th>Jam Kembali</th>
                <th>Keperluan</th>
                <th>Status Pengajuan</th>
                <th>Catatan Admin</th>
                <th>Edit</th>
              </tr>
            </thead>
            <tbody>
              <?php
                if ($result->num_rows > 0) {
                    $no = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$no}</td>";
                        echo "<td>{$row['nama']}<br><small>{$row['nip']}</small></td>";
                        echo "<td>{$row['jam_keluar']}</td>";
                        echo "<td>{$row['jam_kembali']}</td>";
                        echo "<td>{$row['keperluan']}</td>";

                        // Status pengajuan
                        if ($row['status'] == 'pending') {
                            echo "<td><span class='badge bg-warning text-dark'>Menunggu</span></td>";
                            // Catatan admin + form
                            echo "<td>
                                <form method='POST'>
                                    <input type='hidden' name='id_izin' value='{$row['id_izin']}'>
                                    <textarea name='catatan_admin' class='form-control mb-1' placeholder='Catatan admin...' rows='2'></textarea>
                                    <div class='d-flex gap-1 mt-2'>
                                        <button type='submit' name='status' value='disetujui' class='btn btn-success btn-sm'>Izinkan</button>
                                        <button type='submit' name='status' value='ditolak' class='btn btn-danger btn-sm'>Tolak</button>
                                    </div>
                                </form>
                            </td>";
                        } else {
                            $badge_pengajuan = $row['status'] == 'disetujui'
                                ? "<span class='badge bg-success'>Disetujui</span>"
                                : "<span class='badge bg-danger'>Ditolak</span>";
                            echo "<td>{$badge_pengajuan}</td>";
                            echo "<td>" . htmlspecialchars($row['catatan_admin'] !== null ? $row['catatan_admin'] : '-') . "</td>";

                        }

                        // Edit modal tetap di luar form
                        echo "<td>
                            <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editModal{$row['id_izin']}'>Edit</button>
                            <div class='modal fade' id='editModal{$row['id_izin']}' tabindex='-1'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <form method='POST'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title'>Edit Status & Catatan</h5>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                            </div>
                                            <div class='modal-body'>
                                                <input type='hidden' name='id_izin' value='{$row['id_izin']}'>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Status Pengajuan</label>
                                                    <select name='status' class='form-control' required>
                                                        <option value='pending' ".($row['status']=='pending'?'selected':'').">Menunggu</option>
                                                        <option value='disetujui' ".($row['status']=='disetujui'?'selected':'').">Disetujui</option>
                                                        <option value='ditolak' ".($row['status']=='ditolak'?'selected':'').">Ditolak</option>
                                                    </select>
                                                </div>
                                                <div class='mb-3'>
                                                    <label class='form-label'>Catatan Admin</label>
                                                    <textarea name='catatan_admin' class='form-control' rows='2'>".htmlspecialchars($row['catatan_admin'] ?? '')."</textarea>
                                                </div>
                                            </div>
                                            <div class='modal-footer'>
                                                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>
                                                <button type='submit' class='btn btn-primary'>Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>";
                        echo "</tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>Tidak ada data izin untuk hari ini.</td></tr>";
                }
            ?>
            </tbody>
          </table>
        </div>
          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
          <nav>
            <ul class="pagination justify-content-center">
              <?php
              $query_string = $_GET;
              
              // Halaman pertama selalu ditampilkan
              $query_string['page'] = 1;
              $url = '?' . http_build_query($query_string);
              echo '<li class="page-item'.(1 == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">1</a></li>';
              
              if ($total_pages <= 3) {
                  // Jika total halaman <= 3, tampilkan semua
                  for ($p = 2; $p <= $total_pages; $p++) {
                      $query_string['page'] = $p;
                      $url = '?' . http_build_query($query_string);
                      echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                  }
              } else {
                  // Jika halaman saat ini <= 3
                  if ($page <= 3) {
                      // Tampilkan 2, 3, 4 jika di halaman 3
                      for ($p = 2; $p <= min(4, $total_pages); $p++) {
                          $query_string['page'] = $p;
                          $url = '?' . http_build_query($query_string);
                          echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                      }
                      if ($total_pages > 4) {
                          echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                          $query_string['page'] = $total_pages;
                          $url = '?' . http_build_query($query_string);
                          echo '<li class="page-item"><a class="page-link" href="'.$url.'">'.$total_pages.'</a></li>';
                      }
                  }
                  // Jika halaman saat ini > 3 dan < total_pages - 2
                  elseif ($page > 3 && $page < $total_pages - 2) {
                      echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                      // Tampilkan halaman sebelumnya, saat ini, dan berikutnya
                      for ($p = $page - 1; $p <= $page + 1; $p++) {
                          $query_string['page'] = $p;
                          $url = '?' . http_build_query($query_string);
                          echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                      }
                      echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                      $query_string['page'] = $total_pages;
                      $url = '?' . http_build_query($query_string);
                      echo '<li class="page-item"><a class="page-link" href="'.$url.'">'.$total_pages.'</a></li>';
                  }
                  // Jika halaman saat ini >= total_pages - 2
                  else {
                      echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                      // Tampilkan 3 halaman terakhir
                      for ($p = max(2, $total_pages - 2); $p <= $total_pages; $p++) {
                          $query_string['page'] = $p;
                          $url = '?' . http_build_query($query_string);
                          echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                      }
                  }
              }
              ?>
            </ul>
          </nav>
          <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
