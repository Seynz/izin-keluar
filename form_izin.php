<?php 
include 'koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_anggota = $_POST['id_anggota'];
    $tanggal_izin = $_POST['tanggal_izin'];
    $jam_keluar = $_POST['jam_keluar'];
    $jam_kembali = $_POST['jam_kembali'];
    $keperluan = $_POST['keperluan'];

    $sql = "INSERT INTO izin (id_anggota, tanggal_izin, jam_keluar, jam_kembali, keperluan)
            VALUES ('$id_anggota', '$tanggal_izin', '$jam_keluar', '$jam_kembali', '$keperluan')";

    if ($conn->query($sql) === TRUE) {
        $alert = "<div class='alert alert-success text-center'>✅ Izin berhasil diajukan!</div>";
    } else {
        $alert = "<div class='alert alert-danger text-center'>❌ Gagal mengajukan izin: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form Izin Anggota</title>
  <link rel="icon" type="image/png" href="assets/logo-uho.png">
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


<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
      <img src="assets/logo-uho.png" alt="Logo UHO" height="40" class="me-2">
      Sistem Izin Karyawan
    </a>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white text-center">
          <h4 class="mb-0 d-flex align-items-center justify-content-center">
            <img src="assets/logo-uho.png" alt="Logo UHO" height="30" class="me-2">
            Form Pengajuan Izin Keluar
          </h4>
        </div>
        <div class="card-body">
          <?php if (isset($alert)) echo $alert; ?>
          <form method="POST">
            <div class="mb-3 position-relative">
              <label for="searchAnggota" class="form-label">Nama / NIP Anggota <span class="text-danger">*</span></label>
              <input type="text" id="searchAnggota" class="form-control mb-2" autocomplete="off" placeholder="Ketik nama/NIP untuk mencari, lalu pilih dari daftar...">
              <!-- <small class="text-muted d-block mb-2"><strong>Penting:</strong> Ketik nama atau NIP untuk mencari, kemudian klik salah satu hasil yang muncul untuk memilih</small> -->
              <div id="dropdownAnggota" class="list-group">
                <?php
                $result = $conn->query("SELECT id_anggota, nama, nip FROM anggota ORDER BY nama ASC");
                while ($row = $result->fetch_assoc()) {
                    echo "<button type='button' class='list-group-item list-group-item-action' data-id='{$row['id_anggota']}' data-nama='" . htmlspecialchars($row['nama']) . "' data-nip='" . htmlspecialchars($row['nip']) . "'>{$row['nama']} ({$row['nip']})</button>";
                }
                ?>
              </div>
              <input type="hidden" name="id_anggota" id="id_anggota" required>
              <div id="errorMessage" class="text-danger small mt-1" style="display: none;">Identitas Anggota tidak ditemukan</div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="tanggal_izin" class="form-label">Tanggal Izin</label>
                <input type="date" name="tanggal_izin" id="tanggal_izin" class="form-control" required>
              </div>
              <div class="col-md-3 mb-3">
                <label for="jam_keluar" class="form-label">Jam Keluar</label>
                <input type="time" name="jam_keluar" id="jam_keluar" class="form-control" required>
              </div>
              <div class="col-md-3 mb-3">
                <label for="jam_kembali" class="form-label">Jam Kembali</label>
                <input type="time" name="jam_kembali" id="jam_kembali" class="form-control" required>
              </div>
            </div>

            <div class="mb-3">
              <label for="keperluan" class="form-label">Keperluan</label>
              <textarea name="keperluan" id="keperluan" class="form-control" rows="3" required></textarea>
            </div>

            <div class="d-flex justify-content-between">
              <a href="index.php" class="btn btn-secondary px-4">Kembali</a>
              <button type="submit" class="btn btn-success px-4">Ajukan Izin</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const searchInput = document.getElementById('searchAnggota');
const dropdown = document.getElementById('dropdownAnggota');
const hiddenInput = document.getElementById('id_anggota');
const errorMessage = document.getElementById('errorMessage');
let selectedAnggotaId = null;
let isAnggotaSelected = false;

// Buka dropdown saat input focus
searchInput.addEventListener('focus', function() {
  if (searchInput.value) {
    filterDropdown();
  } else {
    dropdown.style.display = 'block';
    filterDropdown();
  }
});

// Filter dropdown berdasarkan input
searchInput.addEventListener('input', function() {
  isAnggotaSelected = false;
  hiddenInput.value = '';
  filterDropdown();
});

// Tutup dropdown jika klik di luar
document.addEventListener('click', function(e) {
  if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.style.display = 'none';
  }
});

function filterDropdown() {
  const filter = searchInput.value.toLowerCase().trim();
  const items = dropdown.querySelectorAll('.list-group-item');
  let hasVisible = false;
  
  if (filter === '') {
    // Jika kosong, tampilkan semua (atau sembunyikan)
    items.forEach(item => {
      item.style.display = 'none';
    });
    dropdown.style.display = 'none';
    return;
  }
  
  items.forEach(item => {
    const nama = item.getAttribute('data-nama').toLowerCase();
    const nip = item.getAttribute('data-nip').toLowerCase();
    const displayText = item.textContent.toLowerCase();
    
    if (displayText.includes(filter) || nama.includes(filter) || nip.includes(filter)) {
      item.style.display = '';
      hasVisible = true;
    } else {
      item.style.display = 'none';
    }
  });
  
  dropdown.style.display = hasVisible ? 'block' : 'none';
}

// Handle klik item di dropdown
dropdown.querySelectorAll('.list-group-item').forEach(item => {
  item.addEventListener('click', function() {
    const id = this.getAttribute('data-id');
    const nama = this.getAttribute('data-nama');
    const nip = this.getAttribute('data-nip');
    
    searchInput.value = nama + ' (' + nip + ')';
    hiddenInput.value = id;
    selectedAnggotaId = id;
    isAnggotaSelected = true;
    dropdown.style.display = 'none';
    errorMessage.style.display = 'none';
    searchInput.style.borderColor = '#198754';
    
    // Set readonly setelah dipilih untuk mencegah editing
    setTimeout(() => {
      searchInput.setAttribute('readonly', 'readonly');
      searchInput.style.backgroundColor = '#f8f9fa';
      searchInput.style.cursor = 'default';
    }, 100);
  });
});

// Reset readonly saat input diklik lagi (jika ingin mengubah pilihan)
searchInput.addEventListener('click', function() {
  if (this.hasAttribute('readonly') && isAnggotaSelected) {
    // Hapus readonly untuk memungkinkan pencarian ulang
    this.removeAttribute('readonly');
    this.style.backgroundColor = '#fff';
    this.style.cursor = 'text';
    this.value = '';
    hiddenInput.value = '';
    selectedAnggotaId = null;
    isAnggotaSelected = false;
    dropdown.style.display = 'block';
    filterDropdown();
  }
});

// Validasi sebelum submit
document.querySelector('form').addEventListener('submit', function(e) {
  if (!hiddenInput.value || !isAnggotaSelected) {
    e.preventDefault();
    errorMessage.style.display = 'block';
    searchInput.style.borderColor = '#dc3545';
    searchInput.focus();
    
    // Hapus readonly jika ada untuk memungkinkan user memilih
    if (searchInput.hasAttribute('readonly')) {
      searchInput.removeAttribute('readonly');
      searchInput.style.backgroundColor = '#fff';
      searchInput.style.cursor = 'text';
    }
    
    if (searchInput.value) {
      dropdown.style.display = 'block';
      filterDropdown();
    }
    return false;
  }
  errorMessage.style.display = 'none';
  searchInput.style.borderColor = '';
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>