<?php
// transaksi_servis.php
require_once 'session_checker.php';
checkRole(['admin', 'staff']);

// Connect to database
include 'koneksi.php';

// Inisialisasi variabel
$alert = '';
$no_invoice = '';
$pelanggan_id = '';
$kendaraan_id = '';
$mekanik_id = '';
$tanggal_masuk = date('Y-m-d');
$tanggal_selesai = '';
$status_servis = 'dikerjakan';
$total_biaya = 0;
$diskon = 0;
$total_bayar = 0;
$status_pembayaran = 'lunas';
$keterangan = '';

// Mendapatkan data mekanik
$queryMekanik = mysqli_query($conn, "SELECT id, nama_mekanik FROM mekanik WHERE status = 'aktif'");

// Mendapatkan data pelangggan
$query_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan") or die(mysqli_error($conn));


// Generate nomor invoice otomatis
function generateNoInvoice() {
    $prefix = "INV/SRV/";
    $date = date('Ymd');
    $uniqueId = mt_rand(1000, 9999);
    return $prefix . $date . "/" . $uniqueId;
}

// Proses form tambah transaksi servis
if (isset($_POST['simpan_transaksi'])) {
    $no_invoice = mysqli_real_escape_string($conn, $_POST['no_invoice']);
    $pelanggan_id = mysqli_real_escape_string($conn, $_POST['pelanggan_id']);
    $kendaraan_id = mysqli_real_escape_string($conn, $_POST['kendaraan_id']);
    $mekanik_id = mysqli_real_escape_string($conn, $_POST['mekanik_id']);
    $tanggal_masuk = mysqli_real_escape_string($conn, $_POST['tanggal_masuk']);
    $status_servis = mysqli_real_escape_string($conn, $_POST['status_servis']);
    $total_biaya = mysqli_real_escape_string($conn, $_POST['total_biaya']);
    $diskon = mysqli_real_escape_string($conn, $_POST['diskon']);
    $total_bayar = mysqli_real_escape_string($conn, $_POST['total_bayar']);
    $status_pembayaran = mysqli_real_escape_string($conn, $_POST['status_pembayaran']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Insert data transaksi servis
    $queryInsert = "INSERT INTO transaksi_servis (no_invoice, pelanggan_id, kendaraan_id, mekanik_id, tanggal_masuk, status_servis, total_biaya, diskon, total_bayar, status_pembayaran, keterangan) 
                   VALUES ('$no_invoice', '$pelanggan_id', '$kendaraan_id', '$mekanik_id', '$tanggal_masuk', '$status_servis', '$total_biaya', '$diskon', '$total_bayar', '$status_pembayaran', '$keterangan')";
    
    $resultInsert = mysqli_query($conn, $queryInsert);
    
    if ($resultInsert) {
        $transaksi_id = mysqli_insert_id($conn);
        
        // Insert layanan servis
        if (isset($_POST['nama_layanan']) && is_array($_POST['nama_layanan'])) {
            foreach ($_POST['nama_layanan'] as $key => $nama_layanan) {
                if (!empty($nama_layanan)) {
                    $harga_layanan = $_POST['harga_layanan'][$key];
                    
                    // Insert ke detail_layanan sebagai layanan kustom
                    mysqli_query($conn, "INSERT INTO detail_layanan (transaksi_id, nama_layanan, harga) 
                                          VALUES ('$transaksi_id', '$nama_layanan', '$harga_layanan')");
                }
            }
        }
        
        // Insert sparepart yang dipilih
        if (isset($_POST['sparepart']) && is_array($_POST['sparepart'])) {
            foreach ($_POST['sparepart'] as $key => $sparepart_id) {
                $jumlah = $_POST['jumlah'][$key];
                $harga_satuan = $_POST['harga_satuan'][$key];
                $subtotal = $jumlah * $harga_satuan;
                
                // Insert ke detail_sparepart
                mysqli_query($conn, "INSERT INTO detail_sparepart (transaksi_id, sparepart_id, jumlah, harga_satuan, subtotal) 
                                      VALUES ('$transaksi_id', '$sparepart_id', '$jumlah', '$harga_satuan', '$subtotal')");
                
                // Update stok sparepart
                mysqli_query($conn, "UPDATE sparepart SET stok = stok - $jumlah WHERE id = '$sparepart_id'");
                
                // Tambahkan log stok keluar
                $user_id = $_SESSION['user_id'];
                $stok_sebelum = 0;
                $stok_sesudah = 0;
                
                // Dapatkan stok saat ini
                $queryStok = mysqli_query($conn, "SELECT stok FROM sparepart WHERE id = '$sparepart_id'");
                $dataStok = mysqli_fetch_assoc($queryStok);
                $stok_sesudah = $dataStok['stok'];
                $stok_sebelum = $stok_sesudah + $jumlah;
                
                mysqli_query($conn, "INSERT INTO log_sparepart (sparepart_id, user_id, tanggal, aktivitas, jumlah, stok_sebelum, stok_sesudah, keterangan) 
                                     VALUES ('$sparepart_id', '$user_id', NOW(), 'kurangi_stok', '$jumlah', '$stok_sebelum', '$stok_sesudah', 'Digunakan untuk transaksi servis #$no_invoice')");
            }
        }
        
        // Tambahkan history service
        mysqli_query($conn, "INSERT INTO history_service (kendaraan_id, pelanggan_id, transaksi_servis_id, tanggal_service, mekanik_id, status) 
                             VALUES ('$kendaraan_id', '$pelanggan_id', '$transaksi_id', '$tanggal_masuk', '$mekanik_id', 'berjalan')");
        
        $alert = '<div class="alert alert-success">Transaksi servis berhasil disimpan!</div>';
        
        // Reset form setelah sukses
        $no_invoice = generateNoInvoice();
        $pelanggan_id = '';
        $kendaraan_id = '';
        $mekanik_id = '';
        $tanggal_masuk = date('Y-m-d');
        $status_servis = 'dikerjakan';
        $total_biaya = 0;
        $diskon = 0;
        $total_bayar = 0;
        $status_pembayaran = 'belum_bayar';
        $keterangan = '';
    } else {
        $alert = '<div class="alert alert-danger">Gagal menyimpan transaksi servis: ' . mysqli_error($conn) . '</div>';
    }
}

// Jika no_invoice kosong, generate otomatis
if (empty($no_invoice)) {
    $no_invoice = generateNoInvoice();
}

// Proses edit transaksi servis
if (isset($_GET['edit'])) {
    $id = mysqli_real_escape_string($conn, $_GET['edit']);
    $queryEdit = mysqli_query($conn, "SELECT * FROM transaksi_servis WHERE id = '$id'");
    
    if (mysqli_num_rows($queryEdit) > 0) {
        $data = mysqli_fetch_assoc($queryEdit);
        $no_invoice = $data['no_invoice'];
        $pelanggan_id = $data['pelanggan_id'];
        $kendaraan_id = $data['kendaraan_id'];
        $mekanik_id = $data['mekanik_id'];
        $tanggal_masuk = $data['tanggal_masuk'];
        $tanggal_selesai = $data['tanggal_selesai'];
        $status_servis = $data['status_servis'];
        $total_biaya = $data['total_biaya'];
        $diskon = $data['diskon'];
        $total_bayar = $data['total_bayar'];
        $status_pembayaran = $data['status_pembayaran'];
        $keterangan = $data['keterangan'];
    }
}

// Proses update status servis menjadi selesai
if (isset($_GET['selesai'])) {
    $id = mysqli_real_escape_string($conn, $_GET['selesai']);
    $queryUpdate = mysqli_query($conn, "UPDATE transaksi_servis SET status_servis = 'selesai', tanggal_selesai = NOW() WHERE id = '$id'");
    
    if ($queryUpdate) {
        // Update status di history_service
        mysqli_query($conn, "UPDATE history_service SET status = 'selesai' WHERE transaksi_servis_id = '$id'");
        
        $alert = '<div class="alert alert-success">Status servis berhasil diperbarui menjadi Selesai!</div>';
    } else {
        $alert = '<div class="alert alert-danger">Gagal memperbarui status servis: ' . mysqli_error($conn) . '</div>';
    }
}

// Proses update status pembayaran
if (isset($_GET['bayar'])) {
    $id = mysqli_real_escape_string($conn, $_GET['bayar']);
    $queryUpdate = mysqli_query($conn, "UPDATE transaksi_servis SET status_pembayaran = 'lunas' WHERE id = '$id'");
    
    if ($queryUpdate) {
        // Tambahkan data ke tabel keuangan
        $queryTransaksi = mysqli_query($conn, "SELECT no_invoice, total_bayar FROM transaksi_servis WHERE id = '$id'");
        $dataTransaksi = mysqli_fetch_assoc($queryTransaksi);
        $jumlah = $dataTransaksi['total_bayar'];
        $referensi = $dataTransaksi['no_invoice'];
        $user_id = $_SESSION['user_id'];
        
        mysqli_query($conn, "INSERT INTO keuangan (tanggal, jenis_transaksi, kategori, deskripsi, jumlah, referensi_transaksi_id, user_id) 
                             VALUES (NOW(), 'pemasukan', 'Servis', 'Pembayaran servis invoice #$referensi', '$jumlah', '$id', '$user_id')");
        
        $alert = '<div class="alert alert-success">Status pembayaran berhasil diperbarui menjadi Lunas!</div>';
    } else {
        $alert = '<div class="alert alert-danger">Gagal memperbarui status pembayaran: ' . mysqli_error($conn) . '</div>';
    }
}

// Proses hapus transaksi servis
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Hapus data terkait di tabel lain
    mysqli_query($conn, "DELETE FROM detail_layanan WHERE transaksi_id = '$id'");
    
    // Kembalikan stok sparepart sebelum hapus detail_sparepart
    $queryDetailSparepart = mysqli_query($conn, "SELECT sparepart_id, jumlah FROM detail_sparepart WHERE transaksi_id = '$id'");
    while ($dataDetail = mysqli_fetch_assoc($queryDetailSparepart)) {
        $sparepart_id = $dataDetail['sparepart_id'];
        $jumlah = $dataDetail['jumlah'];
        
        // Update stok
        mysqli_query($conn, "UPDATE sparepart SET stok = stok + $jumlah WHERE id = '$sparepart_id'");
        
        // Tambahkan log
        $user_id = $_SESSION['user_id'];
        $stok_sebelum = 0;
        $stok_sesudah = 0;
        
        // Dapatkan stok saat ini
        $queryStok = mysqli_query($conn, "SELECT stok FROM sparepart WHERE id = '$sparepart_id'");
        $dataStok = mysqli_fetch_assoc($queryStok);
        $stok_sebelum = $dataStok['stok'] - $jumlah;
        $stok_sesudah = $dataStok['stok'];
        
        mysqli_query($conn, "INSERT INTO log_sparepart (sparepart_id, user_id, tanggal, aktivitas, jumlah, stok_sebelum, stok_sesudah, keterangan) 
                             VALUES ('$sparepart_id', '$user_id', NOW(), 'tambah_stok', '$jumlah', '$stok_sebelum', '$stok_sesudah', 'Pengembalian stok karena transaksi servis dihapus')");
    }
    
    // Hapus detail_sparepart
    mysqli_query($conn, "DELETE FROM detail_sparepart WHERE transaksi_id = '$id'");
    
    // Hapus history_service
    mysqli_query($conn, "DELETE FROM history_service WHERE transaksi_servis_id = '$id'");
    
    // Hapus transaksi
    $queryDelete = mysqli_query($conn, "DELETE FROM transaksi_servis WHERE id = '$id'");
    
    if ($queryDelete) {
        $alert = '<div class="alert alert-success">Transaksi servis berhasil dihapus!</div>';
    } else {
        $alert = '<div class="alert alert-danger">Gagal menghapus transaksi servis: ' . mysqli_error($conn) . '</div>';
    }
}

// Query untuk menampilkan daftar transaksi servis
$queryTransaksi = "SELECT ts.*, p.nama_pelanggan, m.nama_mekanik, k.no_polisi, k.merk_kendaraan
                  FROM transaksi_servis ts
                  LEFT JOIN pelanggan p ON ts.pelanggan_id = p.id
                  LEFT JOIN mekanik m ON ts.mekanik_id = m.id
                  LEFT JOIN kendaraan k ON ts.kendaraan_id = k.id
                  ORDER BY ts.id DESC";
$resultTransaksi = mysqli_query($conn, $queryTransaksi);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Transaksi Servis - Bengkel Watro Mulyo Joyo</title>

    <!-- Custom fonts for this template-->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom styles for this page -->
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
    /* Perbaikan CSS untuk Select2 */
    .select2-container {
    width: 100% !important;
    z-index: 9999;
    }

    .select2-container--open .select2-dropdown {
    z-index: 10000;
    }

    .select2-container--open .select2-dropdown--above {
    z-index: 10000;
    }

    .select2-container--open .select2-dropdown--below {
    z-index: 10000;
    }

    .select2-search--dropdown .select2-search__field {
    width: 100% !important;
    padding: 6px !important;
    box-sizing: border-box !important;
    }

    .modal-body .select2-container--default .select2-selection--single {
    border: 1px solid #d1d3e2;
    border-radius: 4px;
    height: 38px;
    padding: 4px;
    }

    .modal-body .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    }

    /* Membuat kolom pencarian Select2 lebih terlihat */
    .select2-search--dropdown {
    padding: 8px;
    }

    .select2-search--dropdown .select2-search__field {
    border: 1px solid #aaa;
    border-radius: 4px;
    }

    /* Pastikan dropdown Select2 muncul di atas modal */
    .modal-open .select2-container--open .select2-dropdown {
    z-index: 1056 !important;
    }
</style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard_admin.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-tools fa-fw"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Bengkel Watro Mulyo Joyo</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="dashboard_admin.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Master Data
            </div>

            <!-- Nav Item - Data Pelanggan Collapse Menu -->
          <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePelanggan"
            aria-expanded="true" aria-controls="collapsePelanggan">
            <i class="fas fa-users"></i>
            <span>Data Pelanggan</span>
        </a>
        <div id="collapsePelanggan" class="collapse" aria-labelledby="headingPelanggan" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Manajemen Pelanggan:</h6>
            <a class="collapse-item" href="pelanggan.php">Daftar Pelanggan</a>
            <a class="collapse-item" href="manajemenpelanggan.php">Akun Pelanggan</a>
        </div>
    </div>
</li>

            <!-- Nav Item - Data Mekanik -->
            <li class="nav-item">
                <a class="nav-link" href="mekanik.php">
                    <i class="fas fa-wrench fa-fw"></i>
                    <span>Data Mekanik</span></a>
            </li>

             <!-- Nav Item - Data Supplier -->
             <li class="nav-item">
                <a class="nav-link" href="supplier.php">
                    <i class="fas fa-truck fa-fw"></i>
                    <span>Data Supplier</span></a>
            </li>

            <!-- Nav Item - Manajemen Data User -->
             <li class="nav-item">
                <a class="nav-link" href="manajemenuser.php">
                    <i class="fas fa-user fa-fw"></i>
                    <span>Manajemen Data User</span></a>
            </li>
        
            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Transaksi
            </div>

            <!-- Nav Item - Stok Sparepart Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSparepart"
                    aria-expanded="true" aria-controls="collapseSparepart">
                    <i class="fas fa-tools fa-fw"></i>
                <span>Stok Sparepart</span>
            </a>
            <div id="collapseSparepart" class="collapse" aria-labelledby="headingSparepart" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Menu Sparepart:</h6>
                <a class="collapse-item" href="daftar_sparepart.php">Daftar Sparepart</a>
                <a class="collapse-item" href="stok_masuk.php">Stok Masuk</a>
                <a class="collapse-item" href="stok_keluar.php">Stok Keluar</a>
                <a class="collapse-item" href="kategori_sparepart.php">Kategori Sparepart</a>
            </div>
        </div>
    </li>

            <!-- Nav Item - Service -->
            <li class="nav-item">
                <a class="nav-link" href="layanan_servis.php">
                    <i class="fas fa-cogs fa-2x"></i>
                    <span>Layanan Servis</span></a>
            </li>

             <!-- Nav Item - Transaksi Servis -->
             <li class="nav-item active">
                <a class="nav-link" href="transaksi_servis.php">
                <i class="fas fa-cash-register fa-fw"></i>
                    <span>Transaksi Servis</span></a>
            </li>

            <!-- Nav Item - Riwayat Servis -->
            <li class="nav-item">
                <a class="nav-link" href="riwayat_servis.php">
                <i class="fas fa-history fa-fw"></i>
                    <span>Riwayat Servis</span></a>
            </li>

            <!-- Nav Item - keuangan -->
            <li class="nav-item">
                <a class="nav-link" href="keuangan.php">
                    <i class="fas fa-fw fa-money-bill"></i>
                    <span>Keuangan</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['nama_lengkap']; ?></span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Transaksi Servis</h1>
                        <button class="btn btn-dark" data-toggle="modal" data-target="#modalTransaksi">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Transaksi Baru
                        </button>
                    </div>

                    <!-- Alert messages -->
                    <?php echo $alert; ?>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-dark">Daftar Transaksi Servis</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>No. Invoice</th>
                                            <th>Tanggal Masuk</th>
                                            <th>Pelanggan</th>
                                            <th>Kendaraan</th>
                                            <th>Mekanik</th>
                                            <th>Status Servis</th>
                                            <th>Total Bayar</th>
                                            <th>Status Pembayaran</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($resultTransaksi)) : 
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo $row['no_invoice']; ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_masuk'])); ?></td>
                                            <td><?php echo $row['nama_pelanggan']; ?></td>
                                            <td><?php echo $row['no_polisi'] . ' - ' . $row['merk_kendaraan']; ?></td>
                                            <td><?php echo $row['nama_mekanik']; ?></td>
                                            <td>
                                                <?php 
                                                if ($row['status_servis'] == 'dikerjakan') {
                                                    echo '<span class="badge badge-warning">Dikerjakan</span>';
                                                } elseif ($row['status_servis'] == 'selesai') {
                                                    echo '<span class="badge badge-success">Selesai</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php 
                                                if ($row['status_pembayaran'] == 'belum_bayar') {
                                                    echo '<span class="badge badge-danger">Belum Bayar</span>';
                                                } else {
                                                    echo '<span class="badge badge-success">Lunas</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="detail_transaksi.php?id=<?php echo $row['id']; ?>" 
                                                    class="btn btn-info btn-sm" 
                                                    title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
        
                                                    <?php if ($row['status_servis'] == 'dikerjakan') : ?>
                                                        <!-- Tambah tombol Edit -->
                                                        <button type="button" 
                                                                class="btn btn-primary btn-sm" 
                                                                data-toggle="modal" 
                                                                data-target="#modalTransaksi" 
                                                                data-id="<?php echo $row['id']; ?>"
                                                                title="Edit Transaksi">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
            
                                                        <a href="?selesai=<?php echo $row['id']; ?>" 
                                                            class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Tandai servis ini sebagai selesai?')" 
                                                            title="Selesai">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
        
                                                    <a href="cetak_invoice.php?id=<?php echo $row['id']; ?>" 
                                                        class="btn btn-warning btn-sm" 
                                                        target="_blank" 
                                                        title="Cetak Invoice">
                                                        <i class="fas fa-print"></i>
                                                    </a>
        
                                                    <a href="?hapus=<?php echo $row['id']; ?>" 
                                                        class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Yakin ingin menghapus data ini?')" 
                                                        title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Bengkel Watro Mulyo Joyo <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda yakin ingin mengakhiri sesi Anda saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-dark" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Transaksi with Fixed Structure -->
<div class="modal fade" id="modalTransaksi" tabindex="-1" role="dialog" aria-labelledby="modalTransaksiLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTransaksiLabel">Transaksi Servis Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="post" id="formTransaksi" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Informasi Transaksi -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-dark">Informasi Transaksi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="no_invoice">No. Invoice:</label>
                                        <input type="text" class="form-control" id="no_invoice" name="no_invoice" value="<?php echo $no_invoice; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="tanggal_masuk">Tanggal Masuk:</label>
                                        <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo $tanggal_masuk; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="status_servis">Status Servis:</label>
                                        <select class="form-control" id="status_servis" name="status_servis" required>
                                            <option value="dikerjakan" <?php if($status_servis == 'dikerjakan') echo 'selected'; ?>>Dikerjakan</option>
                                            <option value="selesai" <?php if($status_servis == 'selesai') echo 'selected'; ?>>Selesai</option>
                                        </select>
                                    </div>
                                   <div class="form-group">
                                        <label for="status_pembayaran">Status Pembayaran:</label>
                                        <select class="form-control" id="status_pembayaran" name="status_pembayaran" required>
                                            <!-- Hapus opsi "belum_bayar" -->
                                            <option value="lunas" <?php if($status_pembayaran == 'lunas') echo 'selected'; ?>>Lunas</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Informasi Pelanggan & Kendaraan -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-dark">Informasi Pelanggan & Kendaraan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="pelanggan_id">Pelanggan:</label>
                                        <select class="form-control select2-pelanggan" id="pelanggan_id" name="pelanggan_id" required>
                                            <?php if (!empty($pelanggan_id)) : 
                                                $queryPelanggan = mysqli_query($conn, "SELECT id, nama_pelanggan FROM pelanggan WHERE id = '$pelanggan_id'");
                                                $dataPelanggan = mysqli_fetch_assoc($queryPelanggan);
                                            ?>
                                            <option value="<?php echo $pelanggan_id; ?>" selected><?php echo $dataPelanggan['nama_pelanggan']; ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="kendaraan_id">Kendaraan:</label>
                                        <select class="form-control select2-kendaraan" id="kendaraan_id" name="kendaraan_id" required <?php if(empty($pelanggan_id)) echo 'disabled'; ?>>
                                            <?php if (!empty($kendaraan_id)) : 
                                                $queryKendaraan = mysqli_query($conn, "SELECT id, no_polisi, merk_kendaraan FROM kendaraan WHERE id = '$kendaraan_id'");
                                                $dataKendaraan = mysqli_fetch_assoc($queryKendaraan);
                                            ?>
                                            <option value="<?php echo $kendaraan_id; ?>" selected><?php echo $dataKendaraan['no_polisi'] . ' - ' . $dataKendaraan['merk_kendaraan']; ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="mekanik_id">Mekanik:</label>
                                        <select class="form-control" id="mekanik_id" name="mekanik_id" required>
                                            <option value="">-- Pilih Mekanik --</option>
                                            <?php 
                                            // Reset the mekanik query pointer to start
                                            mysqli_data_seek($queryMekanik, 0);
                                            while ($mekanik = mysqli_fetch_assoc($queryMekanik)) : ?>
                                            <option value="<?php echo $mekanik['id']; ?>" <?php if($mekanik_id == $mekanik['id']) echo 'selected'; ?>>
                                                <?php echo $mekanik['nama_mekanik']; ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="keterangan">Keterangan:</label>
                                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?php echo $keterangan; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Layanan Servis -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-dark">Layanan Servis</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabelLayanan">
                                    <thead>
                                        <tr>
                                            <th width="50%">Nama Layanan</th>
                                            <th width="40%">Harga</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input type="text" class="form-control" name="nama_layanan[]" placeholder="Nama Layanan" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control harga-layanan" name="harga_layanan[]" placeholder="Harga Layanan" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm tambah-layanan">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sparepart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-dark">Sparepart</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabelSparepart">
                                    <thead>
                                        <tr>
                                            <th width="40%">Sparepart</th>
                                            <th width="15%">Jumlah</th>
                                            <th width="20%">Harga Satuan</th>
                                            <th width="15%">Subtotal</th>
                                            <th width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       <tr>
                                         <td>
                                            <select class="form-control select2-sparepart" name="sparepart[]" style="width: 100%;">
                                                <option value="">-- Pilih Sparepart --</option>
                                                </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control jumlah" name="jumlah[]" value="1" min="1">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control harga-satuan" name="harga_satuan[]" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control subtotal" name="subtotal[]" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm tambah-sparepart">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ringkasan Biaya -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-dark">Ringkasan Biaya</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="total_biaya">Total Biaya:</label>
                                        <input type="number" class="form-control" id="total_biaya" name="total_biaya" value="<?php echo $total_biaya; ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="diskon">Diskon (Rp):</label>
                                        <input type="number" class="form-control" id="diskon" name="diskon" value="<?php echo $diskon; ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="total_bayar">Total Bayar:</label>
                                        <input type="number" class="form-control" id="total_bayar" name="total_bayar" value="<?php echo $total_bayar; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark" name="simpan_transaksi">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Bootstrap core JavaScript-->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="assets/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="assets/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="assets/js/demo/datatables-demo.js"></script>

   <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>

// Fix z-index issues for Select2 dropdowns in modal
    $.fn.modal.Constructor.prototype._enforceFocus = function() {};

    // JavaScript for transaksi_servis.php
$(document).ready(function() {
    // Initialize DataTable
    $('#dataTable').DataTable();

    // Inisialisasi Select2 untuk semua sparepart saat modal dibuka
    $('#modalTransaksi').on('shown.bs.modal', function() {
        initializeSelect2Sparepart($('.select2-sparepart'));
    });
    
    // Inisialisasi Select2 untuk pelanggan
// Inisialisasi Select2 untuk pelanggan
   $('.select2-pelanggan').select2({
    placeholder: '-- Pilih Pelanggan --',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalTransaksi'),
    ajax: {
        url: 'get_pelanggan.php',
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return {
                term: params.term || ''
            };
        },
        processResults: function(data) {
            return {
                results: data
            };
        },
        cache: false
    }
}).on('select2:open', function() {
    // Muat data awal saat dropdown dibuka
    if (!$(this).data('loaded')) {
        var $this = $(this);
        $.ajax({
            url: 'get_pelanggan.php',
            dataType: 'json'
        }).then(function(data) {
            // Tambahkan opsi placeholder
            var placeholder = new Option('-- Pilih Pelanggan --', '', true, true);
            $this.append(placeholder).trigger('change');
            
            // Tambahkan data pelanggan
            data.forEach(function(item) {
                var option = new Option(item.text, item.id, false, false);
                $this.append(option);
            });
            
            $this.data('loaded', true);
        });
    }
});

// Reset loaded state saat modal ditutup
$('#modalTransaksi').on('hidden.bs.modal', function() {
    $('.select2-pelanggan').data('loaded', false)
                          .val(null)
                          .trigger('change');
});
    
    // When pelanggan is selected, load related kendaraan
    $('#pelanggan_id').on('change', function() {
        var pelangganId = $(this).val();
        if (pelangganId) {
            $('#kendaraan_id').prop('disabled', false);
            $('#kendaraan_id').val(null).trigger('change');
            
            // Initialize Select2 for kendaraan based on selected pelanggan
            $('.select2-kendaraan').select2({
                placeholder: '-- Pilih Kendaraan --',
                allowClear: true,
                ajax: {
                    url: 'get_kendaraan.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term || '',
                            pelanggan_id: pelangganId
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0,
                dropdownParent: $('#modalTransaksi')
            });
        } else {
            $('#kendaraan_id').prop('disabled', true);
            $('#kendaraan_id').val(null).trigger('change');
        }
    });

   function initializeSelect2Sparepart(element) {
    element.select2({
        placeholder: '-- Pilih Sparepart --',
        allowClear: true,
        width: '100%',
        ajax: {
            url: 'get_sparepart.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term || ''
                };
            },
            processResults: function(data) {
                console.log('Data dari server:', data); // Debugging
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 0,
        dropdownParent: $('#modalTransaksi')
    }).on('select2:select', function(e) {
        var data = e.params.data;
        var row = $(this).closest('tr');
        if(data.harga_jual) {
            row.find('.harga-satuan').val(data.harga_jual);
            var jumlah = row.find('.jumlah').val();
            var subtotal = data.harga_jual * jumlah;
            row.find('.subtotal').val(subtotal);
            calculateTotal();
        }
    });
}
    // Function to add new layanan row
    $(document).on('click', '.tambah-layanan', function() {
        var newRow = `
            <tr>
                <td>
                    <input type="text" class="form-control" name="nama_layanan[]" placeholder="Nama Layanan">
                </td>
                <td>
                    <input type="number" class="form-control harga-layanan" name="harga_layanan[]" placeholder="Harga Layanan">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm hapus-layanan">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#tabelLayanan tbody').append(newRow);
        calculateTotal();
    });

    // Function to remove layanan row
    $(document).on('click', '.hapus-layanan', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // Function to add new sparepart row
    $(document).on('click', '.tambah-sparepart', function() {
        var newRow = `
            <tr>
                <td>
                    <select class="form-control select2-sparepart-new" name="sparepart[]" style="width: 100%;">
                        <option value="">-- Pilih Sparepart --</option>
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control jumlah" name="jumlah[]" value="1" min="1">
                </td>
                <td>
                    <input type="number" class="form-control harga-satuan" name="harga_satuan[]" readonly>
                </td>
                <td>
                    <input type="number" class="form-control subtotal" name="subtotal[]" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm hapus-sparepart">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#tabelSparepart tbody').append(newRow);
        
        // Initialize Select2 for the new sparepart row
        var newSelect = $('#tabelSparepart tbody').find('.select2-sparepart-new').last();
        newSelect.removeClass('select2-sparepart-new').addClass('select2-sparepart');
        initializeSelect2Sparepart(newSelect);
        
        calculateTotal();
    });

    // Function to remove sparepart row
    $(document).on('click', '.hapus-sparepart', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // When sparepart is selected, get its price
    $(document).on('change', '.select2-sparepart', function() {
        var sparepartId = $(this).val();
        var row = $(this).closest('tr');
        
        if (sparepartId) {
            $.ajax({
                url: 'get_sparepart_info.php',
                type: 'POST',
                data: {sparepart_id: sparepartId},
                dataType: 'json',
                success: function(data) {
                    row.find('.harga-satuan').val(data.harga_jual);
                    
                    // Calculate subtotal
                    var jumlah = row.find('.jumlah').val();
                    var hargaSatuan = row.find('.harga-satuan').val();
                    var subtotal = jumlah * hargaSatuan;
                    row.find('.subtotal').val(subtotal);
                    
                    calculateTotal();
                }
            });
        } else {
            row.find('.harga-satuan').val('');
            row.find('.subtotal').val('');
            calculateTotal();
        }
    });

    // Calculate subtotal when quantity changes
    $(document).on('change keyup', '.jumlah', function() {
        var row = $(this).closest('tr');
        var jumlah = parseInt($(this).val());
        var hargaSatuan = parseInt(row.find('.harga-satuan').val()) || 0;
        
        var subtotal = jumlah * hargaSatuan;
        row.find('.subtotal').val(subtotal);
        
        calculateTotal();
    });

    // Update total when service fee changes
    $(document).on('change keyup', '.harga-layanan', function() {
        calculateTotal();
    });

    // Update total when discount changes
    $('#diskon').on('change keyup', function() {
        calculateTotal();
    });

    // Function to calculate total
    function calculateTotal() {
        var totalLayanan = 0;
        $('.harga-layanan').each(function() {
            var harga = parseInt($(this).val()) || 0;
            totalLayanan += harga;
        });
        
        var totalSparepart = 0;
        $('.subtotal').each(function() {
            var subtotal = parseInt($(this).val()) || 0;
            totalSparepart += subtotal;
        });
        
        var totalBiaya = totalLayanan + totalSparepart;
        var diskon = parseInt($('#diskon').val()) || 0;
        var totalBayar = totalBiaya - diskon;
        
        $('#total_biaya').val(totalBiaya);
        $('#total_bayar').val(totalBayar);
    }

    // Open Modal if edit or add
    <?php if (isset($_GET['edit'])) : ?>
    $('#modalTransaksi').modal('show');
    <?php endif; ?>
    
    // Fix Select2 CSS issues in modal
    $('#modalTransaksi').on('shown.bs.modal', function () {
        $('.select2-container').css('width', '100%');
    });
});

    // Tambahkan event handler untuk tombol edit
$('#modalTransaksi').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var modal = $(this);
    
    // Jika ini adalah mode edit (ada ID transaksi)
    if (id) {
        modal.find('.modal-title').text('Edit Transaksi Servis');
        
        // Load data transaksi yang ada
        $.ajax({
            url: 'get_transaksi_data.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(data) {
                // Isi form dengan data yang ada
                modal.find('#no_invoice').val(data.transaksi.no_invoice);
                modal.find('#tanggal_masuk').val(data.transaksi.tanggal_masuk);
                modal.find('#status_servis').val(data.transaksi.status_servis);
                modal.find('#status_pembayaran').val(data.transaksi.status_pembayaran);
                
                // Set pelanggan dan trigger change untuk load kendaraan
                modal.find('#pelanggan_id')
                    .val(data.transaksi.pelanggan_id)
                    .trigger('change');
                
                // Setelah pelanggan di-load, set kendaraan
                setTimeout(function() {
                    modal.find('#kendaraan_id')
                        .val(data.transaksi.kendaraan_id)
                        .trigger('change');
                }, 500);
                
                modal.find('#mekanik_id').val(data.transaksi.mekanik_id);
                modal.find('#keterangan').val(data.transaksi.keterangan);
                
                // Hapus semua baris layanan kecuali baris pertama
                $('#tabelLayanan tbody tr:not(:first)').remove();
                
                // Load layanan
                if (data.layanan.length > 0) {
                    data.layanan.forEach(function(item, index) {
                        if (index === 0) {
                            // Update baris pertama
                            var firstRow = $('#tabelLayanan tbody tr:first');
                            firstRow.find('input[name="nama_layanan[]"]').val(item.nama_layanan);
                            firstRow.find('input[name="harga_layanan[]"]').val(item.harga);
                        } else {
                            // Tambah baris baru untuk layanan tambahan
                            addLayananRow(item);
                        }
                    });
                }
                
                // Hapus semua baris sparepart kecuali baris pertama
                $('#tabelSparepart tbody tr:not(:first)').remove();
                
                // Load sparepart
                if (data.sparepart.length > 0) {
                    data.sparepart.forEach(function(item, index) {
                        if (index === 0) {
                            // Update baris pertama
                            var firstRow = $('#tabelSparepart tbody tr:first');
                            var newOption = new Option(item.nama_part, item.sparepart_id, true, true);
                            firstRow.find('select[name="sparepart[]"]')
                                .append(newOption)
                                .trigger('change');
                            firstRow.find('input[name="jumlah[]"]').val(item.jumlah);
                            firstRow.find('input[name="harga_satuan[]"]').val(item.harga_satuan);
                            firstRow.find('input[name="subtotal[]"]').val(item.subtotal);
                        } else {
                            // Tambah baris baru untuk sparepart tambahan
                            addSparepartRow(item);
                        }
                    });
                }
                
                // Update total
                calculateTotal();
                
                // Tambah input hidden untuk ID transaksi
                modal.find('form').append('<input type="hidden" name="edit_id" value="' + id + '">');
            }
        });
    } else {
        // Mode tambah baru
        modal.find('.modal-title').text('Transaksi Servis Baru');
        modal.find('form')[0].reset();
        modal.find('form').find('input[name="edit_id"]').remove();
    }
});

// Tambahkan fungsi helper untuk menambah baris layanan dengan data
function addLayananRow(data = null) {
    var newRow = `
        <tr>
            <td>
                <input type="text" class="form-control" name="nama_layanan[]" 
                       value="${data ? data.nama_layanan : ''}" placeholder="Nama Layanan">
            </td>
            <td>
                <input type="number" class="form-control harga-layanan" name="harga_layanan[]" 
                       value="${data ? data.harga : ''}" placeholder="Harga Layanan">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm hapus-layanan">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#tabelLayanan tbody').append(newRow);
}

// Tambahkan fungsi helper untuk menambah baris sparepart dengan data
function addSparepartRow(data = null) {
    var newRow = `
        <tr>
            <td>
                <select class="form-control select2-sparepart" name="sparepart[]" style="width: 100%;">
                    <option value="">-- Pilih Sparepart --</option>
                    ${data ? `<option value="${data.sparepart_id}" selected>${data.nama_part}</option>` : ''}
                </select>
            </td>
            <td>
                <input type="number" class="form-control jumlah" name="jumlah[]" 
                       value="${data ? data.jumlah : '1'}" min="1">
            </td>
            <td>
                <input type="number" class="form-control harga-satuan" name="harga_satuan[]" 
                       value="${data ? data.harga_satuan : ''}" readonly>
            </td>
            <td>
                <input type="number" class="form-control subtotal" name="subtotal[]" 
                       value="${data ? data.subtotal : ''}" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm hapus-sparepart">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    var $newRow = $(newRow);
    $('#tabelSparepart tbody').append($newRow);
    initializeSelect2Sparepart($newRow.find('.select2-sparepart'));
}

    </script>

<!-- Custom CSS override -->
<style>
        .sidebar.bg-gradient-primary {
            background-color: #0e1b2a !important;
            background-image: linear-gradient(180deg, #0e1b2a 10%, #0a1520 100%) !important;
        }
        
        .sidebar-dark .nav-item.active .nav-link {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-dark .nav-item .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

/* Improved Modal Z-index handling */
/* Z-index yang lebih tinggi untuk Select2 dalam modal */
.modal-open .select2-container--open {
    z-index: 9999999 !important;
}

/* Perbaikan tampilan Select2 */
.select2-container--default .select2-selection--single {
    height: 38px !important;
    padding: 4px !important;
    border: 1px solid #d1d3e2 !important;
    border-radius: 4px !important;
    width: 100% !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    padding-left: 8px !important;
    padding-right: 20px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    position: absolute !important;
    top: 1px !important;
    right: 1px !important;
    width: 20px !important;
}

/* Perbaikan posisi dropdown */
.select2-container--open .select2-dropdown {
    margin-top: 0 !important;
    border: 1px solid #d1d3e2 !important;
    border-radius: 4px !important;
    left: 0 !important; /* Penting untuk alignment */
}

/* Pastikan lebar dropdown sesuai dengan input */
.select2-container {
    width: 100% !important;
    box-sizing: border-box !important;
}

/* Perbaikan tampilan hasil pencarian */
.select2-results__options {
    max-height: 200px !important;
    overflow-y: auto !important;
    padding: 4px 0 !important;
}

.select2-results__option {
    padding: 8px 12px !important;
    margin: 0 !important;
}

/* Perbaikan box pencarian */
.select2-search--dropdown {
    padding: 8px !important;
}

.select2-search--dropdown .select2-search__field {
    padding: 6px !important;
    border-radius: 4px !important;
    border: 1px solid #d1d3e2 !important;
    width: 100% !important;
}

/* Z-index untuk modal */
.modal-open .select2-container--open .select2-dropdown {
    z-index: 1056 !important;
}

/* Make modal bigger and more accessible */
.modal-xl {
    max-width: 90%;
}

.modal-body {
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 1.5rem;
}

/* Fix table layout in modals */
.modal-body .table th, 
.modal-body .table td {
    vertical-align: middle;
}

/* Make form elements consistent */
.modal-body .form-control {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
}

/* Fix button appearance */
.modal-body .btn {
    padding: 0.375rem 0.75rem;
}

/* Improve overall spacing */
.modal-body .card {
    margin-bottom: 1.5rem;
}

.modal-body .card-header {
    padding: 0.75rem 1rem;
}

.modal-body .card-body {
    padding: 1rem;
}

/* Ensure modal appears above select2 */
.modal-open .modal {
    overflow-x: hidden;
    overflow-y: auto;
}

/* Fix the float clearing issue */
.clearfix::after {
    content: "";
    clear: both;
    display: table;
}

/* Prevent text selection on double-click */
.modal-body {
    user-select: text;
}

/* Make sure dropdowns don't get cut off */
.modal-open {
    padding-right: 0 !important;
    overflow: auto !important;
}

/* Styling untuk button group dan spacing */
.btn-group {
    display: flex;
    gap: 8px; /* Tambah jarak antar tombol */
    flex-wrap: wrap;
    padding: 4px; /* Tambah padding di sekitar group tombol */
}

.btn-group .btn {
    margin: 2px; /* Tambah margin di setiap sisi tombol */
    padding: 6px 10px; /* Tambah padding dalam tombol */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 35px; /* Tambah lebar minimum tombol */
    height: 35px; /* Tetapkan tinggi yang konsisten */
}

/* Spacing untuk icon dalam tombol */
.btn-group .btn i {
    margin: 0;
    font-size: 14px;
}

/* Hover effect yang lebih smooth */
.btn-group .btn:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Tambah padding pada cell tabel */
.table td {
    padding: 12px 15px; /* Tambah padding dalam cell */
    vertical-align: middle;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group {
        gap: 6px;
    }
    
    .btn-group .btn {
        padding: 5px 8px;
        min-width: 32px;
        height: 32px;
    }
}


</style>           

</body>
</html>