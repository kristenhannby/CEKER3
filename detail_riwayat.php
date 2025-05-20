<?php
// detail_riwayat.php
require_once 'session_checker.php';
checkRole(['admin', 'staff']);

// Connect to database
include 'koneksi.php';

// Initialize variables
$alert = '';
$id = '';

// Check if ID is provided
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Get transaction details
    $queryTransaksi = "SELECT 
                        ts.id AS transaksi_id,
                        ts.no_invoice, 
                        ts.tanggal_masuk, 
                        ts.tanggal_selesai, 
                        ts.status_servis,
                        ts.total_biaya,
                        ts.diskon,
                        ts.total_bayar,
                        ts.status_pembayaran,
                        ts.keterangan,
                        p.id AS pelanggan_id,
                        p.nama_pelanggan, 
                        p.alamat,
                        p.no_telp,
                        k.id AS kendaraan_id,
                        k.no_polisi, 
                        k.merk_kendaraan, 
                        k.jenis_kendaraan,
                        k.tipe_kendaraan,
                        k.tahun_pembuatan,
                        k.warna_kendaraan as warna,
                        m.id AS mekanik_id,
                        m.nama_mekanik,
                        m.spesialisasi as spesialis
                      FROM transaksi_servis ts
                      LEFT JOIN pelanggan p ON ts.pelanggan_id = p.id
                      LEFT JOIN kendaraan k ON ts.kendaraan_id = k.id
                      LEFT JOIN mekanik m ON ts.mekanik_id = m.id
                      WHERE ts.id = '$id'";
    
    $resultTransaksi = mysqli_query($conn, $queryTransaksi);
    
    if (!$resultTransaksi || mysqli_num_rows($resultTransaksi) == 0) {
        $alert = '<div class="alert alert-danger">Data transaksi tidak ditemukan.</div>';
    } else {
        $transaksi = mysqli_fetch_assoc($resultTransaksi);
        
        // Get service details
        $queryLayanan = "SELECT 
                          dl.id,
                          ls.nama_layanan,
                          dl.harga
                        FROM detail_layanan dl
                        LEFT JOIN layanan_servis ls ON dl.layanan_id = ls.id
                        WHERE dl.transaksi_id = '$id'";
        
        $resultLayanan = mysqli_query($conn, $queryLayanan);
        
        // Get sparepart details
        $querySparepart = "SELECT 
                            ds.id,
                            sp.nama_part,
                            sp.kode_part,
                            ds.jumlah,
                            ds.harga_satuan,
                            ds.subtotal
                          FROM detail_sparepart ds
                          LEFT JOIN sparepart sp ON ds.sparepart_id = sp.id
                          WHERE ds.transaksi_id = '$id'";
        
        $resultSparepart = mysqli_query($conn, $querySparepart);
    }
} else {
    $alert = '<div class="alert alert-danger">ID transaksi tidak ditemukan.</div>';
}

// Get service history for the vehicle
if (isset($transaksi['kendaraan_id'])) {
    $kendaraanId = $transaksi['kendaraan_id'];
    $currentTransaksiId = $id;
    
    $queryRiwayat = "SELECT 
                        ts.id,
                        ts.no_invoice,
                        ts.tanggal_masuk,
                        ts.status_servis,
                        ts.total_bayar,
                        m.nama_mekanik
                      FROM transaksi_servis ts
                      LEFT JOIN mekanik m ON ts.mekanik_id = m.id
                      WHERE ts.kendaraan_id = '$kendaraanId' AND ts.id != '$currentTransaksiId'
                      ORDER BY ts.tanggal_masuk DESC
                      LIMIT 5";
    
    $resultRiwayat = mysqli_query($conn, $queryRiwayat);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Detail Riwayat Servis - Bengkel Watro Mulyo Joyo</title>

    <!-- Custom fonts for this template-->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
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

            <!-- Nav Item - Data Pelanggan -->
            <li class="nav-item">
                <a class="nav-link" href="pelanggan.php">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Data Pelanggan</span></a>
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
             <li class="nav-item">
                <a class="nav-link" href="transaksi_servis.php">
                    <i class="fas fa-cash-register fa-fw"></i>
                    <span>Transaksi Servis</span></a>
            </li>
            
            <!-- Nav Item - Riwayat Servis -->
            <li class="nav-item active">
                <a class="nav-link" href="riwayat_servis.php">
                    <i class="fas fa-history fa-fw"></i>
                    <span>Riwayat Servis</span></a>
            </li>

            <!-- Nav Item - Keuangan -->
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

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['role']; ?></span>
                                <img class="img-profile rounded-circle"
                                    src="assets/img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profil.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
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
                    <h1 class="h3 mb-4 text-gray-800">Detail Riwayat Servis</h1>

                    <?php echo $alert; ?>

                    <?php if (isset($transaksi)): ?>
                        <!-- Transaksi Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Informasi Transaksi</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                         aria-labelledby="dropdownMenuLink">
                                        <div class="dropdown-header">Opsi:</div>
                                        <a class="dropdown-item" href="cetak_invoice.php?id=<?php echo $id; ?>" target="_blank">
                                            <i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Cetak Invoice
                                        </a>
                                        <a class="dropdown-item" href="edit_transaksi.php?id=<?php echo $id; ?>">
                                            <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i>
                                            Edit Transaksi
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>No Invoice</th>
                                                <td>: <?php echo $transaksi['no_invoice']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Masuk</th>
                                                <td>: <?php echo date('d M Y', strtotime($transaksi['tanggal_masuk'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Selesai</th>
                                                <td>: <?php echo $transaksi['tanggal_selesai'] ? date('d M Y', strtotime($transaksi['tanggal_selesai'])) : '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status Servis</th>
                                                <td>: 
                                                    <?php 
                                                        $statusClass = '';
                                                        switch($transaksi['status_servis']) {
                                                            case 'Antri':
                                                                $statusClass = 'badge-warning';
                                                                break;
                                                            case 'Proses':
                                                                $statusClass = 'badge-info';
                                                                break;
                                                            case 'Selesai':
                                                                $statusClass = 'badge-success';
                                                                break;
                                                            case 'Dibatalkan':
                                                                $statusClass = 'badge-danger';
                                                                break;
                                                            default:
                                                                $statusClass = 'badge-secondary';
                                                        }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $transaksi['status_servis']; ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Status Pembayaran</th>
                                                <td>: 
                                                    <span class="badge <?php echo $transaksi['status_pembayaran'] == 'Lunas' ? 'badge-success' : 'badge-danger'; ?>">
                                                        <?php echo $transaksi['status_pembayaran']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>Total Biaya</th>
                                                <td>: Rp <?php echo number_format($transaksi['total_biaya'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Diskon</th>
                                                <td>: Rp <?php echo number_format($transaksi['diskon'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Total Bayar</th>
                                                <td>: <strong>Rp <?php echo number_format($transaksi['total_bayar'], 0, ',', '.'); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <th>Mekanik</th>
                                                <td>: <?php echo $transaksi['nama_mekanik']; ?> (<?php echo $transaksi['spesialis']; ?>)</td>
                                            </tr>
                                            <tr>
                                                <th>Keterangan</th>
                                                <td>: <?php echo $transaksi['keterangan'] ? $transaksi['keterangan'] : '-'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer & Vehicle Info -->
                        <div class="row">
                            <!-- Customer Info -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Informasi Pelanggan</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>Nama Pelanggan</th>
                                                <td>: <?php echo $transaksi['nama_pelanggan']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Alamat</th>
                                                <td>: <?php echo $transaksi['alamat']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>No. Telepon</th>
                                                <td>: <?php echo $transaksi['no_telp']; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vehicle Info -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Informasi Kendaraan</h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>No. Polisi</th>
                                                <td>: <?php echo $transaksi['no_polisi']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Merk / Model</th>
                                                <td>: <?php echo $transaksi['merk_kendaraan']; ?> / <?php echo $transaksi['jenis_kendaraan']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tipe</th>
                                                <td>: <?php echo $transaksi['tipe_kendaraan']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tahun</th>
                                                <td>: <?php echo $transaksi['tahun_pembuatan']; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Warna</th>
                                                <td>: <?php echo $transaksi['warna']; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Services & Spareparts -->
                        <div class="row">
                            <!-- Service List -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Daftar Layanan Servis</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <?php if (mysqli_num_rows($resultLayanan) > 0): ?>
                                                <table class="table table-bordered" width="100%" cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Nama Layanan</th>
                                                            <th>Biaya</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $no = 1;
                                                        $totalLayanan = 0;
                                                        while ($row = mysqli_fetch_assoc($resultLayanan)) {
                                                            $totalLayanan += $row['harga'];
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo $row['nama_layanan']; ?></td>
                                                            <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="2" class="text-right">Total Biaya Layanan:</th>
                                                            <th>Rp <?php echo number_format($totalLayanan, 0, ',', '.'); ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            <?php else: ?>
                                                <p class="text-center">Tidak ada layanan servis yang dipilih.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sparepart List -->
                            <div class="col-md-6">
                                <div class="card shadow mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Daftar Sparepart</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <?php if (mysqli_num_rows($resultSparepart) > 0): ?>
                                                <table class="table table-bordered" width="100%" cellspacing="0">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Nama Part</th>
                                                            <th>Kode</th>
                                                            <th>Jumlah</th>
                                                            <th>Harga</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $no = 1;
                                                        $totalSparepart = 0;
                                                        while ($row = mysqli_fetch_assoc($resultSparepart)) {
                                                            $totalSparepart += $row['subtotal'];
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $no++; ?></td>
                                                            <td><?php echo $row['nama_part']; ?></td>
                                                            <td><?php echo $row['kode_part']; ?></td>
                                                            <td><?php echo $row['jumlah']; ?></td>
                                                            <td>Rp <?php echo number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                                                            <td>Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="5" class="text-right">Total Biaya Sparepart:</th>
                                                            <th>Rp <?php echo number_format($totalSparepart, 0, ',', '.'); ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            <?php else: ?>
                                                <p class="text-center">Tidak ada sparepart yang digunakan.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service History for this Vehicle -->
                        <?php if (isset($resultRiwayat) && mysqli_num_rows($resultRiwayat) > 0): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Riwayat Servis Kendaraan</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>No Invoice</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th>Total Bayar</th>
                                                <th>Mekanik</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($resultRiwayat)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo $row['no_invoice']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($row['tanggal_masuk'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = '';
                                                    switch($row['status_servis']) {
                                                        case 'Antri':
                                                            $statusClass = 'badge-warning';
                                                            break;
                                                        case 'Proses':
                                                            $statusClass = 'badge-info';
                                                            break;
                                                        case 'Selesai':
                                                            $statusClass = 'badge-success';
                                                            break;
                                                        case 'Dibatalkan':
                                                            $statusClass = 'badge-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'badge-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $row['status_servis']; ?></span>
                                                </td>
                                                <td>Rp <?php echo number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                                <td><?php echo $row['nama_mekanik']; ?></td>
                                                <td>
                                                    <a href="detail_riwayat.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Bengkel Watro Mulyo Joyo 2025</span>
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
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi Anda saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
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

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#dataTable').DataTable();
    });
    </script>

    <!-- Custom styles -->
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