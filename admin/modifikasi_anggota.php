<?php
include '../koneksi.php';
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Proses tambah anggota baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
    $nama = trim($_POST['nama']);
    $nip = trim($_POST['nip']);
    
    if (!empty($nama) && !empty($nip)) {
        // Cek apakah NIP sudah ada
        $check_stmt = $conn->prepare("SELECT id_anggota FROM anggota WHERE nip = ?");
        $check_stmt->bind_param("s", $nip);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "NIP sudah terdaftar! Silakan gunakan NIP lain.";
            $message_type = "warning";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            $stmt = $conn->prepare("INSERT INTO anggota (nama, nip) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama, $nip);
            if ($stmt->execute()) {
                $stmt->close();
                // Redirect untuk refresh data dan menghindari resubmit
                $_SESSION['success_message'] = "Anggota baru berhasil ditambahkan!";
                // Pertahankan parameter pencarian jika ada
                $redirect_url = "modifikasi_anggota.php";
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $redirect_url .= "?search=" . urlencode($_GET['search']);
                }
                header("Location: " . $redirect_url);
                exit;
            } else {
                $message = "Gagal menambahkan anggota: " . $stmt->error;
                $message_type = "danger";
                $stmt->close();
            }
        }
    } else {
        $message = "Nama dan NIP tidak boleh kosong!";
        $message_type = "danger";
    }
}

// Tampilkan pesan dari session jika ada (setelah redirect)
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = "success";
    unset($_SESSION['success_message']);
}

// Proses update anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id_anggota = intval($_POST['id_anggota']);
    $nama = trim($_POST['nama']);
    $nip = trim($_POST['nip']);
    
    if (!empty($nama) && !empty($nip)) {
        $stmt = $conn->prepare("UPDATE anggota SET nama=?, nip=? WHERE id_anggota=?");
        $stmt->bind_param("ssi", $nama, $nip, $id_anggota);
        if ($stmt->execute()) {
            $message = "Data anggota berhasil diubah!";
            $message_type = "success";
        } else {
            $message = "Gagal mengubah data: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = "Nama dan NIP tidak boleh kosong!";
        $message_type = "danger";
    }
}

// Proses hapus anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $id_anggota = intval($_POST['id_anggota']);
    
    // Cek apakah anggota memiliki riwayat izin
    $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE id_anggota = ?");
    $check_stmt->bind_param("i", $id_anggota);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $total_izin = $check_result->fetch_assoc()['total'];
    $check_stmt->close();
    
    if ($total_izin > 0) {
        $message = "Anggota tidak dapat dihapus karena memiliki riwayat izin. Silakan hapus riwayat terlebih dahulu.";
        $message_type = "warning";
    } else {
        $stmt = $conn->prepare("DELETE FROM anggota WHERE id_anggota = ?");
        $stmt->bind_param("i", $id_anggota);
        if ($stmt->execute()) {
            $message = "Anggota berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus anggota: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Fungsi untuk mendapatkan data anggota
function getAnggotaData($conn, $search_query = '', $page = 1, $per_page = 10) {
    $anggota_list = [];
    $sql_query = "SELECT id_anggota, nama, nip FROM anggota";
    $params = [];
    $types = "";

    if (!empty($search_query)) {
        $sql_query .= " WHERE nama LIKE ? OR nip LIKE ?";
        $search_param = "%{$search_query}%";
        $params = [$search_param, $search_param];
        $types = "ss";
    }

    $sql_query .= " ORDER BY nama ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $anggota_list[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($sql_query);
        while ($row = $result->fetch_assoc()) {
            $anggota_list[] = $row;
        }
    }

    // Pagination
    $offset = ($page - 1) * $per_page;
    $total_rows = count($anggota_list);
    $total_pages = ceil($total_rows / $per_page);
    $anggota_paginated = array_slice($anggota_list, $offset, $per_page);

    return [
        'list' => $anggota_paginated,
        'total_rows' => $total_rows,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'offset' => $offset
    ];
}

// Deteksi AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Filter pencarian
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;

// Ambil data
$data = getAnggotaData($conn, $search_query, $page, $per_page);
$anggota_list = [];
$total_rows = $data['total_rows'];

// Untuk keperluan rendering, ambil semua data jika bukan AJAX (untuk count di info)
if (!$is_ajax) {
    $anggota_paginated = $data['list'];
    $total_pages = $data['total_pages'];
    $offset = $data['offset'];
} else {
    // Jika AJAX, return JSON
    header('Content-Type: application/json');
    
    $output = [
        'success' => true,
        'html' => '',
        'modals_html' => '',
        'pagination_html' => '',
        'info_html' => '',
        'total' => $data['total_rows'],
        'search_query' => $search_query
    ];
    
    // Generate HTML untuk tabel dan modals
    ob_start();
    if (count($data['list']) > 0) {
        $no = $data['offset'] + 1;
        foreach ($data['list'] as $anggota) {
            echo "<tr>";
            echo "<td>{$no}</td>";
            echo "<td>" . htmlspecialchars($anggota['nama']) . "</td>";
            echo "<td>" . htmlspecialchars($anggota['nip']) . "</td>";
            echo "<td>";
            echo "<button class='btn btn-warning btn-sm me-2' data-bs-toggle='modal' data-bs-target='#editModal{$anggota['id_anggota']}'>Edit</button>";
            echo "<button class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#deleteModal{$anggota['id_anggota']}'>Hapus</button>";
            echo "</td>";
            echo "</tr>";
            $no++;
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>Tidak ada data anggota.</td></tr>";
    }
    $output['html'] = ob_get_clean();
    
    // Generate HTML untuk modals
    ob_start();
    if (count($data['list']) > 0) {
        foreach ($data['list'] as $anggota) {
            // Modal Edit
            echo "<div class='modal fade' id='editModal{$anggota['id_anggota']}' tabindex='-1'>";
            echo "<div class='modal-dialog'>";
            echo "<div class='modal-content'>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='update'>";
            echo "<input type='hidden' name='id_anggota' value='{$anggota['id_anggota']}'>";
            echo "<div class='modal-header'>";
            echo "<h5 class='modal-title'>Edit Data Anggota</h5>";
            echo "<button type='button' class='btn-close' data-bs-dismiss='modal'></button>";
            echo "</div>";
            echo "<div class='modal-body'>";
            echo "<div class='mb-3'>";
            echo "<label class='form-label'>Nama</label>";
            echo "<input type='text' name='nama' class='form-control' value='" . htmlspecialchars($anggota['nama']) . "' required>";
            echo "</div>";
            echo "<div class='mb-3'>";
            echo "<label class='form-label'>NIP</label>";
            echo "<input type='text' name='nip' class='form-control' value='" . htmlspecialchars($anggota['nip']) . "' required>";
            echo "</div>";
            echo "</div>";
            echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>";
            echo "<button type='submit' class='btn btn-primary'>Simpan Perubahan</button>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            
            // Modal Hapus
            echo "<div class='modal fade' id='deleteModal{$anggota['id_anggota']}' tabindex='-1'>";
            echo "<div class='modal-dialog'>";
            echo "<div class='modal-content'>";
            echo "<form method='POST' id='deleteForm{$anggota['id_anggota']}'>";
            echo "<input type='hidden' name='action' value='delete'>";
            echo "<input type='hidden' name='id_anggota' value='{$anggota['id_anggota']}'>";
            echo "<input type='hidden' name='confirm_delete' value='' id='confirmDelete{$anggota['id_anggota']}'>";
            echo "<div class='modal-header bg-danger text-white'>";
            echo "<h5 class='modal-title'>Konfirmasi Penghapusan</h5>";
            echo "<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>";
            echo "</div>";
            echo "<div class='modal-body'>";
            echo "<div class='alert alert-warning'>";
            echo "<strong>Peringatan!</strong> Apakah Anda yakin ingin menghapus anggota berikut?";
            echo "</div>";
            echo "<p><strong>Nama:</strong> " . htmlspecialchars($anggota['nama']) . "</p>";
            echo "<p><strong>NIP:</strong> " . htmlspecialchars($anggota['nip']) . "</p>";
            echo "<p class='text-danger'><small>Jika anggota memiliki riwayat izin, penghapusan akan dibatalkan.</small></p>";
            echo "</div>";
            echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>";
            echo "<button type='button' class='btn btn-danger' onclick='confirmDelete({$anggota['id_anggota']})'>Ya, Hapus</button>";
            echo "</div>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
    }
    $output['modals_html'] = ob_get_clean();
    
    // Generate HTML untuk pagination
    ob_start();
    if ($data['total_pages'] > 1) {
        echo '<nav><ul class="pagination justify-content-center">';
        
        $query_string = $_GET;
        $query_string['page'] = 1;
        $url = '?' . http_build_query($query_string);
        echo '<li class="page-item'.(1 == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">1</a></li>';
        
        if ($data['total_pages'] <= 3) {
            for ($p = 2; $p <= $data['total_pages']; $p++) {
                $query_string['page'] = $p;
                $url = '?' . http_build_query($query_string);
                echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
            }
        } else {
            if ($page <= 3) {
                for ($p = 2; $p <= min(4, $data['total_pages']); $p++) {
                    $query_string['page'] = $p;
                    $url = '?' . http_build_query($query_string);
                    echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                }
                if ($data['total_pages'] > 4) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    $query_string['page'] = $data['total_pages'];
                    $url = '?' . http_build_query($query_string);
                    echo '<li class="page-item"><a class="page-link" href="'.$url.'">'.$data['total_pages'].'</a></li>';
                }
            } elseif ($page > 3 && $page < $data['total_pages'] - 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                for ($p = $page - 1; $p <= $page + 1; $p++) {
                    $query_string['page'] = $p;
                    $url = '?' . http_build_query($query_string);
                    echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                }
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                $query_string['page'] = $data['total_pages'];
                $url = '?' . http_build_query($query_string);
                echo '<li class="page-item"><a class="page-link" href="'.$url.'">'.$data['total_pages'].'</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                for ($p = max(2, $data['total_pages'] - 2); $p <= $data['total_pages']; $p++) {
                    $query_string['page'] = $p;
                    $url = '?' . http_build_query($query_string);
                    echo '<li class="page-item'.($p == $page ? ' active' : '').'"><a class="page-link" href="'.$url.'">'.$p.'</a></li>';
                }
            }
        }
        echo '</ul></nav>';
    }
    $output['pagination_html'] = ob_get_clean();
    
    // Generate HTML untuk info
    ob_start();
    if (!empty($search_query)) {
        echo '<div class="mt-2"><small class="text-muted">Menampilkan '.$data['total_rows'].' hasil untuk "<strong>'.htmlspecialchars($search_query).'</strong>"</small></div>';
    }
    $output['info_html'] = ob_get_clean();
    
    echo json_encode($output);
    exit;
}

$anggota_paginated = $data['list'];
$total_pages = $data['total_pages'];
$offset = $data['offset'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Modifikasi Data Anggota</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="../assets/logo-uho.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
        <img src="../assets/logo-uho.png" alt="Logo UHO" height="40" class="me-2">
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

    <div class="row mb-4">
      <div class="col-md-8">
        <h2 class="mb-0">Modifikasi Data Anggota</h2>
      </div>
      <div class="col-md-4 text-end">
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#tambahModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
          </svg> Tambah Anggota Baru
        </button>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-success text-white text-center">
        <h4 class="mb-0 d-flex align-items-center justify-content-center">
          <img src="../assets/logo-uho.png" alt="Logo UHO" height="30" class="me-2">
          Daftar Anggota
        </h4>
      </div>
      <div class="card-body">
        <!-- Form Pencarian -->
        <form method="GET" id="searchForm" class="mb-4">
          <div class="row">
            <div class="col-md-8">
              <div class="input-group">
                <input type="text" name="search" id="searchInput" class="form-control" placeholder="Cari Nama atau NIP" value="<?= htmlspecialchars($search_query) ?>" autocomplete="off">
                <button class="btn btn-primary" type="submit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                  </svg> Cari
                </button>
              </div>
            </div>
            <div class="col-md-4 text-end">
              <?php if (!empty($search_query)): ?>
                <a href="modifikasi_anggota.php" class="btn btn-outline-secondary">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                  </svg> Reset
                </a>
              <?php endif; ?>
            </div>
          </div>
          <div id="searchInfo">
            <?php if (!empty($search_query)): ?>
              <div class="mt-2">
                <small class="text-muted">Menampilkan <?= $total_rows ?> hasil untuk "<strong><?= htmlspecialchars($search_query) ?></strong>"</small>
              </div>
            <?php endif; ?>
          </div>
          <!-- Hidden field untuk mempertahankan parameter page saat pencarian -->
          <input type="hidden" name="page" value="1" id="pageInput">
        </form>
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-success">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody id="tableBody">
              <?php
              if (count($anggota_paginated) > 0) {
                  $no = $offset + 1;
                  foreach ($anggota_paginated as $anggota) {
                      echo "<tr>";
                      echo "<td>{$no}</td>";
                      echo "<td>" . htmlspecialchars($anggota['nama']) . "</td>";
                      echo "<td>" . htmlspecialchars($anggota['nip']) . "</td>";
                      echo "<td>";
                      echo "<button class='btn btn-warning btn-sm me-2' data-bs-toggle='modal' data-bs-target='#editModal{$anggota['id_anggota']}'>Edit</button>";
                      echo "<button class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#deleteModal{$anggota['id_anggota']}'>Hapus</button>";
                      echo "</td>";
                      echo "</tr>";
                      $no++;
                      
                      // Modal Edit
                      echo "<div class='modal fade' id='editModal{$anggota['id_anggota']}' tabindex='-1'>";
                      echo "<div class='modal-dialog'>";
                      echo "<div class='modal-content'>";
                      echo "<form method='POST'>";
                      echo "<input type='hidden' name='action' value='update'>";
                      echo "<input type='hidden' name='id_anggota' value='{$anggota['id_anggota']}'>";
                      echo "<div class='modal-header'>";
                      echo "<h5 class='modal-title'>Edit Data Anggota</h5>";
                      echo "<button type='button' class='btn-close' data-bs-dismiss='modal'></button>";
                      echo "</div>";
                      echo "<div class='modal-body'>";
                      echo "<div class='mb-3'>";
                      echo "<label class='form-label'>Nama</label>";
                      echo "<input type='text' name='nama' class='form-control' value='" . htmlspecialchars($anggota['nama']) . "' required>";
                      echo "</div>";
                      echo "<div class='mb-3'>";
                      echo "<label class='form-label'>NIP</label>";
                      echo "<input type='text' name='nip' class='form-control' value='" . htmlspecialchars($anggota['nip']) . "' required>";
                      echo "</div>";
                      echo "</div>";
                      echo "<div class='modal-footer'>";
                      echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>";
                      echo "<button type='submit' class='btn btn-primary'>Simpan Perubahan</button>";
                      echo "</div>";
                      echo "</form>";
                      echo "</div>";
                      echo "</div>";
                      echo "</div>";
                      
                      // Modal Hapus
                      echo "<div class='modal fade' id='deleteModal{$anggota['id_anggota']}' tabindex='-1'>";
                      echo "<div class='modal-dialog'>";
                      echo "<div class='modal-content'>";
                      echo "<form method='POST' id='deleteForm{$anggota['id_anggota']}'>";
                      echo "<input type='hidden' name='action' value='delete'>";
                      echo "<input type='hidden' name='id_anggota' value='{$anggota['id_anggota']}'>";
                      echo "<input type='hidden' name='confirm_delete' value='' id='confirmDelete{$anggota['id_anggota']}'>";
                      echo "<div class='modal-header bg-danger text-white'>";
                      echo "<h5 class='modal-title'>Konfirmasi Penghapusan</h5>";
                      echo "<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>";
                      echo "</div>";
                      echo "<div class='modal-body'>";
                      echo "<div class='alert alert-warning'>";
                      echo "<strong>Peringatan!</strong> Apakah Anda yakin ingin menghapus anggota berikut?";
                      echo "</div>";
                      echo "<p><strong>Nama:</strong> " . htmlspecialchars($anggota['nama']) . "</p>";
                      echo "<p><strong>NIP:</strong> " . htmlspecialchars($anggota['nip']) . "</p>";
                      echo "<p class='text-danger'><small>Jika anggota memiliki riwayat izin, penghapusan akan dibatalkan.</small></p>";
                      echo "</div>";
                      echo "<div class='modal-footer'>";
                      echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>";
                      echo "<button type='button' class='btn btn-danger' onclick='confirmDelete({$anggota['id_anggota']})'>Ya, Hapus</button>";
                      echo "</div>";
                      echo "</form>";
                      echo "</div>";
                      echo "</div>";
                      echo "</div>";
                  }
              } else {
                  echo "<tr><td colspan='4' class='text-center'>Tidak ada data anggota.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div id="paginationContainer">
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
        
        <div class="text-center mt-3">
          <a href="dashboard.php" class="btn btn-secondary px-4">Kembali ke Dashboard</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Tambah Anggota Baru -->
  <div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="insert">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle me-2" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
              </svg>
              Tambah Anggota Baru
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="nama" class="form-control" placeholder="Masukkan nama anggota" required autofocus>
            </div>
            <div class="mb-3">
              <label class="form-label">NIP <span class="text-danger">*</span></label>
              <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP anggota" required>
              <small class="form-text text-muted">NIP harus unik dan belum terdaftar.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Tambah Anggota</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function confirmDelete(id) {
      if (confirm('Apakah Anda yakin ingin menghapus anggota ini? Tindakan ini tidak dapat dibatalkan!')) {
        document.getElementById('confirmDelete' + id).value = 'yes';
        document.getElementById('deleteForm' + id).submit();
      }
    }

    // Real-time search dengan AJAX
    (function() {
      const searchInput = document.getElementById('searchInput');
      const searchForm = document.getElementById('searchForm');
      const tableBody = document.getElementById('tableBody');
      const paginationContainer = document.getElementById('paginationContainer');
      const searchInfo = document.getElementById('searchInfo');
      let searchTimeout;
      let isLoading = false;

      function loadData(search = '', page = 1) {
        if (isLoading) return;
        
        isLoading = true;
        
        // Tampilkan loading indicator
        if (tableBody) {
          tableBody.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Memuat data...</td></tr>';
        }

        const params = new URLSearchParams();
        if (search) {
          params.set('search', search);
        }
        if (page > 1) {
          params.set('page', page);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'modifikasi_anggota.php?' + params.toString(), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
          isLoading = false;
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              
              if (response.success) {
                // Update tabel
                if (tableBody) {
                  tableBody.innerHTML = response.html;
                }
                
                // Update pagination
                if (paginationContainer) {
                  paginationContainer.innerHTML = response.pagination_html || '';
                }
                
                // Update info
                if (searchInfo) {
                  searchInfo.innerHTML = response.info_html || '';
                }
                
                // Update modals - hapus modals lama dan tambahkan yang baru
                const existingModals = document.querySelectorAll('.modal[id^="editModal"], .modal[id^="deleteModal"]');
                existingModals.forEach(modal => {
                  // Hanya hapus modals yang tidak sedang ditampilkan
                  const bsModal = bootstrap.Modal.getInstance(modal);
                  if (!bsModal || !bsModal._isShown) {
                    modal.remove();
                  }
                });
                
                // Tambahkan modals baru ke body
                if (response.modals_html) {
                  const tempDiv = document.createElement('div');
                  tempDiv.innerHTML = response.modals_html;
                  while (tempDiv.firstChild) {
                    document.body.appendChild(tempDiv.firstChild);
                  }
                }
              }
            } catch (e) {
              console.error('Error parsing response:', e);
              if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Terjadi kesalahan saat memuat data.</td></tr>';
              }
            }
          } else {
            isLoading = false;
            if (tableBody) {
              tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>';
            }
          }
        };
        
        xhr.onerror = function() {
          isLoading = false;
          if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Terjadi kesalahan jaringan.</td></tr>';
          }
        };
        
        xhr.send();
      }

      // Handle input dengan debounce
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          const pageInput = document.getElementById('pageInput');
          if (pageInput) {
            pageInput.value = 1;
          }
          
          // Debounce: tunggu 500ms setelah user berhenti mengetik
          searchTimeout = setTimeout(function() {
            loadData(searchInput.value.trim(), 1);
          }, 500);
        });

        // Submit form jika user menekan Enter
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            const pageInput = document.getElementById('pageInput');
            if (pageInput) {
              pageInput.value = 1;
            }
            loadData(searchInput.value.trim(), 1);
          }
        });
      }

      // Handle submit button
      if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
          e.preventDefault();
          clearTimeout(searchTimeout);
          const pageInput = document.getElementById('pageInput');
          if (pageInput) {
            pageInput.value = 1;
          }
          loadData(searchInput.value.trim(), 1);
        });
      }

      // Handle pagination click (delegate)
      document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination a')) {
          e.preventDefault();
          const url = new URL(e.target.closest('a').href);
          const search = url.searchParams.get('search') || '';
          const page = parseInt(url.searchParams.get('page')) || 1;
          
          if (searchInput) {
            searchInput.value = search;
          }
          const pageInput = document.getElementById('pageInput');
          if (pageInput) {
            pageInput.value = page;
          }
          
          loadData(search, page);
        }
      });
    })();
  </script>
</body>
</html>

