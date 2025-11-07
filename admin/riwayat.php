<?php
include '../koneksi.php';
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

// Auto delete data lebih dari 3 bulan
$tanggal_3_bulan_lalu = date('Y-m-d', strtotime('-3 months'));
$delete_old_stmt = $conn->prepare("DELETE FROM izin WHERE tanggal_izin < ?");
$delete_old_stmt->bind_param("s", $tanggal_3_bulan_lalu);
$delete_old_stmt->execute();
$deleted_count = $delete_old_stmt->affected_rows;
$delete_old_stmt->close();

$message = '';
$message_type = '';

// Proses hapus berdasarkan rentang tanggal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_range') {
    $tanggal_mulai = isset($_POST['tanggal_mulai']) ? trim($_POST['tanggal_mulai']) : '';
    $tanggal_selesai = isset($_POST['tanggal_selesai']) ? trim($_POST['tanggal_selesai']) : '';
    
    if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
        if (isset($_POST['confirm_delete_range']) && $_POST['confirm_delete_range'] === 'yes') {
            // Validasi tanggal
            if ($tanggal_mulai > $tanggal_selesai) {
                $message = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai!";
                $message_type = "danger";
            } else {
                // Cek dulu berapa banyak data yang akan dihapus
                $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE tanggal_izin BETWEEN ? AND ?");
                if ($check_stmt) {
                    $check_stmt->bind_param("ss", $tanggal_mulai, $tanggal_selesai);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $total_to_delete = $check_result->fetch_assoc()['total'];
                    $check_stmt->close();
                    
                    if ($total_to_delete > 0) {
                        // Nonaktifkan foreign key check sementara untuk menghapus data
                        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
                        
                        $delete_stmt = $conn->prepare("DELETE FROM izin WHERE tanggal_izin BETWEEN ? AND ?");
                        if ($delete_stmt) {
                            $delete_stmt->bind_param("ss", $tanggal_mulai, $tanggal_selesai);
                            if ($delete_stmt->execute()) {
                                $deleted_count = $delete_stmt->affected_rows;
                                $delete_stmt->close();
                                
                                // Aktifkan kembali foreign key check
                                $conn->query("SET FOREIGN_KEY_CHECKS = 1");
                                
                                // Set session message untuk ditampilkan setelah redirect
                                $_SESSION['delete_message'] = "Berhasil menghapus $deleted_count data riwayat dari tanggal " . date('d-m-Y', strtotime($tanggal_mulai)) . " sampai " . date('d-m-Y', strtotime($tanggal_selesai)) . ".";
                                $_SESSION['delete_message_type'] = "success";
                                
                                // Redirect untuk menghindari resubmit form
                                header("Location: riwayat.php");
                                exit;
                            } else {
                                // Aktifkan kembali foreign key check jika error
                                $conn->query("SET FOREIGN_KEY_CHECKS = 1");
                                $message = "Gagal menghapus data: " . $delete_stmt->error;
                                $message_type = "danger";
                                $delete_stmt->close();
                            }
                        } else {
                            // Aktifkan kembali foreign key check jika error
                            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
                            $message = "Gagal menyiapkan query DELETE: " . $conn->error;
                            $message_type = "danger";
                        }
                    } else {
                        $message = "Tidak ada data riwayat yang ditemukan pada rentang tanggal tersebut.";
                        $message_type = "info";
                    }
                } else {
                    $message = "Gagal menyiapkan query COUNT: " . $conn->error;
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Konfirmasi penghapusan diperlukan!";
            $message_type = "warning";
        }
    } else {
        $message = "Tanggal mulai dan tanggal selesai harus diisi!";
        $message_type = "warning";
    }
}

// Tampilkan message dari session jika ada (setelah redirect)
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    $message_type = $_SESSION['delete_message_type'];
    unset($_SESSION['delete_message']);
    unset($_SESSION['delete_message_type']);
}

// Ambil tanggal 3 bulan terakhir
$tanggal_awal = date('Y-m-d', strtotime('-3 months'));
$tanggal_akhir = date('Y-m-d');

// Ambil data anggota untuk pencarian
$anggota = [];
$res = $conn->query("SELECT id_anggota, nama, nip FROM anggota ORDER BY nama ASC");
while ($row = $res->fetch_assoc()) {
    $anggota[] = $row;
}

// Filter pencarian
$filter_id_anggota = isset($_GET['id_anggota']) ? $_GET['id_anggota'] : '';

// Pagination setup
$per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Hitung total data
$count_sql = "SELECT COUNT(*) as total FROM izin i 
              JOIN anggota a ON i.id_anggota = a.id_anggota 
              WHERE i.tanggal_izin BETWEEN ? AND ?";
$count_params = [$tanggal_awal, $tanggal_akhir];
$count_types = "ss";
if ($filter_id_anggota) {
    $count_sql .= " AND i.id_anggota = ?";
    $count_params[] = $filter_id_anggota;
    $count_types .= "i";
}
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);
$count_stmt->close();

// Ambil data izin sesuai halaman
$sql = "SELECT i.*, a.nama, a.nip 
        FROM izin i 
        JOIN anggota a ON i.id_anggota = a.id_anggota 
        WHERE i.tanggal_izin BETWEEN ? AND ?";
        
$params = [$tanggal_awal, $tanggal_akhir];
$types = "ss";
if ($filter_id_anggota) {
    $sql .= " AND i.id_anggota = ?";
    $params[] = $filter_id_anggota;
    $types .= "i";
}
$sql .= " ORDER BY i.id_izin DESC, i.jam_keluar DESC LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Riwayat Izin 3 Bulan Terakhir</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-brmp.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #dropdownAnggota {
      border: 2px solid #198754;
      box-shadow: 0 4px 12px rgba(0,0,0,0.10);
      background: #fff;
      position: absolute;
      width: 100%;
      z-index: 10;
      max-height: 200px;
      overflow-y: auto;
      display: none;
    }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
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
  <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white text-center">
          <h4 class="mb-0 d-flex align-items-center justify-content-center">
            <img src="../assets/logo-brmp.png" alt="Logo BRMP" height="30" class="me-2">
            Riwayat Izin Anggota 3 Bulan Terakhir
          </h4>
          <small>(<?= date('d-m-Y', strtotime($tanggal_awal)) ?> s/d <?= date('d-m-Y') ?>)</small>
        </div>
        <div class="card-body">
          <!-- Form Hapus Data Berdasarkan Rentang Tanggal -->
          <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
              <h5 class="mb-0">Hapus Data Riwayat Berdasarkan Tanggal</h5>
            </div>
            <div class="card-body">
              <form method="POST" id="deleteRangeForm" action="">
                <input type="hidden" name="action" value="delete_range">
                <input type="hidden" name="confirm_delete_range" value="" id="confirmDeleteRange">
                <div class="row">
                  <div class="col-md-5 mb-3">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                  </div>
                  <div class="col-md-5 mb-3">
                    <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                  </div>
                  <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="button" class="btn btn-danger w-100" id="btnHapusData">Hapus Data</button>
                  </div>
                </div>
                <div class="alert alert-info">
                  <small><strong>Catatan:</strong> Data yang dihapus tidak dapat dikembalikan. Pastikan Anda yakin sebelum menghapus.</small>
                </div>
              </form>
            </div>
          </div>
          <form method="GET" class="mb-4 position-relative" autocomplete="off">
            <div class="mb-3" style="max-width:400px;margin:auto;">
              <label for="searchAnggota" class="form-label">Cari Nama / NIP Anggota</label>
              <input type="text" id="searchAnggota" class="form-control mb-2" placeholder="Ketik nama/NIP..." value="<?php
                if ($filter_id_anggota) {
                  foreach ($anggota as $ag) {
                    if ($ag['id_anggota'] == $filter_id_anggota) {
                      echo htmlspecialchars($ag['nama'] . ' (' . $ag['nip'] . ')');
                      break;
                    }
                  }
                }
              ?>">
              <div id="dropdownAnggota" class="list-group">
                <?php foreach ($anggota as $ag): ?>
                  <button type="button" class="list-group-item list-group-item-action" data-id="<?= $ag['id_anggota'] ?>">
                    <?= htmlspecialchars($ag['nama']) ?> (<?= htmlspecialchars($ag['nip']) ?>)
                  </button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="id_anggota" id="id_anggota" value="<?= htmlspecialchars($filter_id_anggota) ?>">
            </div>
            <div class="text-center">
              <button type="submit" class="btn btn-primary px-4">Cari</button>
              <a href="riwayat.php" class="btn btn-outline-secondary px-4">Reset</a>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead class="table-success">
                <tr>
                  <th>No</th>
                  <th>Nama / NIP</th>
                  <th>Tanggal Izin</th>
                  <th>Jam Keluar</th>
                  <th>Jam Kembali</th>
                  <th>Keperluan</th>
                  <th>Status Pengajuan</th>
                  <th>Catatan Admin</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result->num_rows > 0) {
                  $no = $offset + 1;
                  while ($row = $result->fetch_assoc()) {
                    if ($row['status'] == 'pending') {
                      $badge_pengajuan = "<span class='badge bg-warning text-dark'>Menunggu</span>";
                    } elseif ($row['status'] == 'disetujui') {
                      $badge_pengajuan = "<span class='badge bg-success'>Disetujui</span>";
                    } else {
                      $badge_pengajuan = "<span class='badge bg-danger'>Ditolak</span>";
                    }
                    $catatan = !empty($row['catatan_admin']) ? $row['catatan_admin'] : '-';
                    echo "<tr>
                      <td>{$no}</td>
                      <td>{$row['nama']}<br><small>{$row['nip']}</small></td>
                      <td>{$row['tanggal_izin']}</td>
                      <td>{$row['jam_keluar']}</td>
                      <td>{$row['jam_kembali']}</td>
                      <td>{$row['keperluan']}</td>
                      <td>{$badge_pengajuan}</td>
                      <td>{$catatan}</td>
                    </tr>";
                    $no++;
                  }
                } else {
                  echo "<tr><td colspan='8' class='text-center'>Tidak ada riwayat izin dalam 3 bulan terakhir.</td></tr>";
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
          <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary px-4">Kembali ke Dashboard</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
// Pastikan fungsi didefinisikan di global scope
(function() {
  const searchInput = document.getElementById('searchAnggota');
  const dropdown = document.getElementById('dropdownAnggota');
  const hiddenInput = document.getElementById('id_anggota');

  if (searchInput && dropdown && hiddenInput) {
    searchInput.addEventListener('focus', function() {
      dropdown.style.display = 'block';
      filterDropdown();
    });

    searchInput.addEventListener('input', filterDropdown);

    document.addEventListener('click', function(e) {
      if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });

    function filterDropdown() {
      const filter = searchInput.value.toLowerCase();
      const items = dropdown.querySelectorAll('.list-group-item');
      let hasVisible = false;
      items.forEach(item => {
        if (item.textContent.toLowerCase().includes(filter)) {
          item.style.display = '';
          hasVisible = true;
        } else {
          item.style.display = 'none';
        }
      });
      dropdown.style.display = hasVisible ? 'block' : 'none';
    }

    dropdown.querySelectorAll('.list-group-item').forEach(item => {
      item.addEventListener('click', function() {
        searchInput.value = this.textContent;
        hiddenInput.value = this.getAttribute('data-id');
        dropdown.style.display = 'none';
      });
    });
  }
})();

// Fungsi confirmDeleteRange - didefinisikan di global scope
function confirmDeleteRange() {
  const tanggalMulai = document.getElementById('tanggal_mulai');
  const tanggalSelesai = document.getElementById('tanggal_selesai');
  const confirmInput = document.getElementById('confirmDeleteRange');
  const form = document.getElementById('deleteRangeForm');
  
  if (!tanggalMulai || !tanggalSelesai || !confirmInput || !form) {
    alert('Terjadi kesalahan! Silakan refresh halaman dan coba lagi.');
    console.error('Missing elements:', { tanggalMulai, tanggalSelesai, confirmInput, form });
    return false;
  }
  
  const nilaiMulai = tanggalMulai.value.trim();
  const nilaiSelesai = tanggalSelesai.value.trim();
  
  if (!nilaiMulai || !nilaiSelesai) {
    alert('Silakan pilih tanggal mulai dan tanggal selesai!');
    if (!nilaiMulai) tanggalMulai.focus();
    else tanggalSelesai.focus();
    return false;
  }
  
  if (nilaiMulai > nilaiSelesai) {
    alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai!');
    tanggalMulai.focus();
    return false;
  }
  
  // Format tanggal untuk ditampilkan
  const mulaiDate = new Date(nilaiMulai + 'T00:00:00');
  const selesaiDate = new Date(nilaiSelesai + 'T00:00:00');
  const mulaiFormatted = mulaiDate.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
  const selesaiFormatted = selesaiDate.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
  
  const confirmMessage = `Apakah Anda yakin ingin menghapus semua data riwayat dari tanggal ${mulaiFormatted} sampai ${selesaiFormatted}?\n\nTindakan ini tidak dapat dibatalkan!`;
  
  if (confirm(confirmMessage)) {
    // Set nilai konfirmasi
    confirmInput.value = 'yes';
    
    // Pastikan form memiliki action
    if (!form.action) {
      form.action = 'riwayat.php';
    }
    
    // Disable button untuk mencegah double submit
    const submitBtn = form.querySelector('button[type="button"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Menghapus...';
    }
    
    // Submit form
    form.submit();
    return true;
  }
  return false;
}

// Event listener untuk tombol hapus data (alternatif jika onclick tidak bekerja)
document.addEventListener('DOMContentLoaded', function() {
  const btnHapusData = document.getElementById('btnHapusData');
  if (btnHapusData) {
    btnHapusData.addEventListener('click', function() {
      confirmDeleteRange();
    });
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
