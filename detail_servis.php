<?php
// detail_servis.php
require_once '../session_checker.php';
checkRole(['mekanik']);

// koneksi database
require_once '../koneksi.php';

// Get service ID from URL
$servis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($servis_id === 0) {
    header("Location: dashboard_mekanik.php");
    exit();
}

// Get mekanik_id from users and mekanik tables
$user_id = $_SESSION['user_id'];
$queryMekanik = "SELECT m.id as mekanik_id 
                 FROM mekanik m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE u.id = ?";

$stmt = mysqli_prepare($conn, $queryMekanik);
if (!$stmt) {
    die("Error in prepare mekanik statement: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$resultMekanik = mysqli_stmt_get_result($stmt);
$mekanik = mysqli_fetch_assoc($resultMekanik);

if (!$mekanik) {
    die("Error: Mekanik not found for this user");
}

$mekanik_id = $mekanik['mekanik_id'];

// Query untuk detail servis
$queryDetail = "SELECT 
    ts.id,
    ts.no_invoice,
    ts.tanggal_masuk,
    ts.tanggal_selesai,
    p.nama_pelanggan,
    p.no_telp as telepon_pelanggan,
    k.no_polisi,
    k.merk_kendaraan,
    k.tipe_kendaraan,
    k.tahun_pembuatan as tahun_kendaraan,
    k.warna_kendaraan,
    ts.keluhan,
    ts.status_servis,
    ts.total_biaya,
    ts.keterangan as catatan_mekanik,
    m.nama_mekanik
FROM transaksi_servis ts
LEFT JOIN pelanggan p ON ts.pelanggan_id = p.id
LEFT JOIN kendaraan k ON ts.kendaraan_id = k.id
LEFT JOIN mekanik m ON ts.mekanik_id = m.id
WHERE ts.id = ? AND ts.mekanik_id = ?";


$stmt = mysqli_prepare($conn, $queryDetail);
if (!$stmt) {
    die("Error in prepare detail statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "ii", $servis_id, $mekanik_id);
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing detail query: " . mysqli_error($conn));
}

$resultDetail = mysqli_stmt_get_result($stmt);
$detail = mysqli_fetch_assoc($resultDetail);

if (!$detail) {
    // Redirect if no data found or unauthorized access
    $_SESSION['error'] = "Data servis tidak ditemukan atau Anda tidak memiliki akses.";
    header("Location: dashboard_mekanik.php");
    exit();
}

// Query untuk layanan yang diberikan
$queryLayanan = "SELECT 
    dl.id,
    dl.nama_layanan,
    dl.harga as harga_layanan
FROM detail_layanan dl
WHERE dl.transaksi_id = ?";

$stmt = mysqli_prepare($conn, $queryLayanan);
if (!$stmt) {
    die("Error in prepare layanan statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $servis_id);
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing layanan query: " . mysqli_error($conn));
}
$resultLayanan = mysqli_stmt_get_result($stmt);

// Query untuk sparepart yang digunakan
$querySparepart = "SELECT 
    ds.id,
    ds.jumlah,
    ds.subtotal,
    sp.nama_part,
    sp.harga as harga_part
FROM detail_sparepart ds
JOIN sparepart sp ON ds.sparepart_id = sp.id
WHERE ds.transaksi_id = ?";

$stmt = mysqli_prepare($conn, $querySparepart);
if (!$stmt) {
    die("Error in prepare sparepart statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $servis_id);
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing sparepart query: " . mysqli_error($conn));
}
$resultSparepart = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Detail Servis - Bengkel Watro Mulyo Joyo</title>

    <!-- Custom fonts and styles -->
    <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Sidebar CSS -->
    <link href="../assets/css/smooth-sidebar.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard_mekanik.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-tools fa-fw"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Bengkel Watro Mulyo Joyo</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Servis
            </div>

            <!-- Nav Item - Daftar Tugas Servis -->
            <li class="nav-item">
                <a class="nav-link" href="dashboard_mekanik.php">
                    <i class="fas fa-list fa-fw"></i>
                    <span>Daftar Tugas Servis</span>
                </a>
            </li>

            <!-- Nav Item - Riwayat Servis -->
            <li class="nav-item">
                <a class="nav-link" href="riwayat_servis_mekanik.php">
                    <i class="fas fa-history fa-fw"></i>
                    <span>Riwayat Servis Saya</span>
                </a>
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
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo $_SESSION['nama_lengkap']; ?>
                                </span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
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
                        <h1 class="h3 mb-0 text-gray-800">Detail Servis</h1>
                        <a href="dashboard_mekanik.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                        </a>
                    </div>

                    <?php if(isset($alert)) echo $alert; ?>

                    <!-- Detail Servis -->
                    <div class="row">
                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Informasi Servis</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%">No. Invoice</td>
                                            <td width="5%">:</td>
                                            <td><strong><?php echo $detail['no_invoice']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal Masuk</td>
                                            <td>:</td>
                                            <td><?php echo date('d-m-Y', strtotime($detail['tanggal_masuk'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Status</td>
                                            <td>:</td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?php echo ucfirst($detail['status_servis']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Keluhan</td>
                                            <td>:</td>
                                            <td><?php echo nl2br($detail['keluhan']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Data Pelanggan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%">Nama</td>
                                            <td width="5%">:</td>
                                            <td><strong><?php echo $detail['nama_pelanggan']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>No. Telepon</td>
                                            <td>:</td>
                                            <td><?php echo $detail['telepon_pelanggan']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Data Kendaraan</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="30%">No. Polisi</td>
                                            <td width="5%">:</td>
                                            <td><strong><?php echo $detail['no_polisi']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Merk & Tipe</td>
                                            <td>:</td>
                                            <td><?php echo $detail['merk_kendaraan'] . ' ' . $detail['tipe_kendaraan']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Tahun</td>
                                            <td>:</td>
                                            <td><?php echo $detail['tahun_kendaraan']; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Warna</td>
                                            <td>:</td>
                                            <td><?php echo $detail['warna_kendaraan']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Layanan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Layanan</th>
                                                    <th class="text-right">Harga</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                $total_layanan = 0;
                                                while($layanan = mysqli_fetch_assoc($resultLayanan)): 
                                                    $total_layanan += $layanan['harga_layanan'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($layanan['nama_layanan'] ?? ''); ?></td>
                                                    <td class="text-right">
                                                        Rp <?php echo number_format($layanan['harga_layanan'] ?? 0, 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                                <tr>
                                                    <td colspan="2" class="text-right"><strong>Total Layanan</strong></td>
                                                    <td class="text-right">
                                                        <strong>
                                                            Rp <?php echo number_format($total_layanan, 0, ',', '.'); ?>
                                                        </strong>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Setelah tabel sparepart, sebelum footer -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Sparepart</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Sparepart</th>
                                                    <th>Jumlah</th>
                                                    <th class="text-right">Harga</th>
                                                    <th class="text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                $total_sparepart = 0;
                                                while($sparepart = mysqli_fetch_assoc($resultSparepart)): 
                                                    $subtotal = $sparepart['harga_part'] * $sparepart['jumlah'];
                                                    $total_sparepart += $subtotal;
                                                ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo $sparepart['nama_part']; ?></td>
                                                    <td><?php echo $sparepart['jumlah']; ?></td>
                                                    <td class="text-right">
                                                        Rp <?php echo number_format($sparepart['harga_part'], 0, ',', '.'); ?>
                                                    </td>
                                                    <td class="text-right">
                                                        Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                                <tr>
                                                    <td colspan="4" class="text-right">
                                                        <strong>Total Sparepart</strong>
                                                    </td>
                                                    <td class="text-right">
                                                        <strong>
                                                            Rp <?php echo number_format($total_sparepart, 0, ',', '.'); ?>
                                                        </strong>
                                                    </td>
                                                </tr>
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
                <div class="modal-body">Pilih "Logout" di bawah jika Anda yakin ingin mengakhiri sesi.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../assets/js/sb-admin-2.min.js"></script>
    
    <!--  Sidebar JS -->
    <script src="../assets/js/smooth-sidebar.js"></script>

    
    <!-- Custom styling -->
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
    </style>
</body>
</html>