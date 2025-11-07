<?php
include 'koneksi.php';

$conn->query("UPDATE izin SET status='ditolak' WHERE status='pending' AND tanggal_izin < CURDATE()");

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
$tanggal_hari_ini = date('Y-m-d');
$count_sql = "SELECT COUNT(*) as total FROM izin i 
              JOIN anggota a ON i.id_anggota = a.id_anggota 
              WHERE i.tanggal_izin = '$tanggal_hari_ini'";
if ($filter_id_anggota) {
    $count_sql .= " AND i.id_anggota = '$filter_id_anggota'";
}
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Ambil data izin sesuai halaman
$sql = "SELECT i.*, a.nama, a.nip 
        FROM izin i 
        JOIN anggota a ON i.id_anggota = a.id_anggota 
        WHERE i.tanggal_izin = '$tanggal_hari_ini'";
if ($filter_id_anggota) {
    $sql .= " AND i.id_anggota = '$filter_id_anggota'";
}
$sql .= " ORDER BY i.id_izin DESC LIMIT $per_page OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Izin Hari Ini</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="assets/logo-brmp.png">
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
    <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
      <img src="assets/logo-brmp.png" alt="Logo BRMP" height="40" class="me-2">
      Sistem Izin Non-Kedinasan
    </a>
  </div>
</nav>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white text-center">
          <h4 class="mb-0 d-flex align-items-center justify-content-center">
            <img src="assets/logo-brmp.png" alt="Logo BRMP" height="30" class="me-2">
            Status Izin Hari Ini (<?= date('d-m-Y') ?>)
          </h4>
        </div>
        <div class="card-body">
          <form method="GET" class="mb-4 position-relative" autocomplete="off">
            <div class="mb-3" style="max-width:400px;margin:auto;">
              <label for="searchAnggota" class="form-label">Cari Nama / NIP Anda</label>
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
              <a href="status_izin.php" class="btn btn-outline-secondary px-4">Reset</a>
            </div>
          </form>
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
                      <td>{$row['jam_keluar']}</td>
                      <td>{$row['jam_kembali']}</td>
                      <td>{$row['keperluan']}</td>
                      <td>{$badge_pengajuan}</td>
                      <td>{$catatan}</td>
                    </tr>";
                    $no++;
                  }
                } else {
                  echo "<tr><td colspan='7' class='text-center'>Belum ada izin hari ini.</td></tr>";
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
            <a href="index.php" class="btn btn-secondary px-4">Kembali</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<footer class="text-center py-3 mt-5 text-muted">
  &copy; <?= date('Y') ?> Sistem Izin Non-Kedinasan
</footer>
<script>
const searchInput = document.getElementById('searchAnggota');
const dropdown = document.getElementById('dropdownAnggota');
const hiddenInput = document.getElementById('id_anggota');

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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
