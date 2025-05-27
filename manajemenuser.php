<?php
// manajemenuser.php
require_once 'session_checker.php';
checkRole(['admin', 'pemilik', 'staff']);

// Connect to database
include 'koneksi.php';

// Inisialisasi pesan
$pesan = "";
$jenisAlert = "";

// Proses tambah user
if (isset($_POST['tambah_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Cek apakah username sudah ada
    $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_username) > 0) {
        $pesan = "Username sudah digunakan. Silahkan gunakan username lain!";
        $jenisAlert = "danger";
    } else {
        // Insert user baru
        $query = "INSERT INTO users (username, password, nama_lengkap, role) 
                  VALUES ('$username', '$password', '$nama_lengkap', '$role')";
        
        if (mysqli_query($conn, $query)) {
            $pesan = "User berhasil ditambahkan!";
            $jenisAlert = "success";
        } else {
            $pesan = "Error: " . mysqli_error($conn);
            $jenisAlert = "danger";
        }
    }
}

// Proses edit user
if (isset($_POST['edit_user'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Update dengan atau tanpa password baru
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $query = "UPDATE users SET 
                  username = '$username', 
                  password = '$password', 
                  nama_lengkap = '$nama_lengkap', 
                  role = '$role'
                  WHERE id = $id";
    } else {
        $query = "UPDATE users SET 
                  username = '$username', 
                  nama_lengkap = '$nama_lengkap', 
                  role = '$role'
                  WHERE id = $id";
    }
    
    if (mysqli_query($conn, $query)) {
        $pesan = "Data user berhasil diperbarui!";
        $jenisAlert = "success";
    } else {
        $pesan = "Error: " . mysqli_error($conn);
        $jenisAlert = "danger";
    }
}

// Proses hapus user
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Cek apakah user digunakan di tabel mekanik
    $cek_mekanik = mysqli_query($conn, "SELECT * FROM mekanik WHERE user_id = $id");
    if (mysqli_num_rows($cek_mekanik) > 0) {
        $pesan = "User tidak dapat dihapus karena digunakan sebagai akun mekanik!";
        $jenisAlert = "warning";
    } else {
        $query = "DELETE FROM users WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $pesan = "User berhasil dihapus!";
            $jenisAlert = "success";
        } else {
            $pesan = "Error: " . mysqli_error($conn);
            $jenisAlert = "danger";
        }
    }
}

// Ambil semua data user
$query_users = "
    SELECT u.id, u.username, u.nama_lengkap, u.role 
    FROM users u 
    WHERE u.role != 'pelanggan'  
    ORDER BY u.id DESC";

$result = mysqli_query($conn, $query_users);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Manajemen User - Bengkel Watro Mulyo Joyo</title>

    <!-- Custom fonts -->
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles -->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!-- Sidebar CSS -->
    <link href="assets/css/smooth-sidebar.css" rel="stylesheet">
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
             <li class="nav-item active">
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

             <!-- Nav Item - Transaksi Servis -->
             <li class="nav-item">
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
                    <h1 class="h3 mb-2 text-gray-800">Manajemen Data User</h1>
                    <p class="mb-4">Halaman ini digunakan untuk mengelola data pengguna sistem bengkel.</p>

                    <!-- Alert Message -->
                    <?php if (!empty($pesan)) : ?>
                    <div class="alert alert-<?php echo $jenisAlert; ?> alert-dismissible fade show" role="alert">
                        <?php echo $pesan; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-dark">Daftar User</h6>
                            <button class="btn btn-dark" data-toggle="modal" data-target="#tambahUserModal">
                                <i class="fas fa-plus-circle fa-sm"></i> Tambah User
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Username</th>
                                            <th>Nama Lengkap</th>
                                            <th>Role</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    switch($row['role']) {
                                                        case 'admin': echo 'primary'; break;
                                                        case 'pemilik': echo 'success'; break;
                                                        case 'mekanik': echo 'info'; break;
                                                        case 'staff': echo 'secondary'; break;
                                                        default: echo 'dark';
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars($row['role'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                           <td>
                                                <button class="btn btn-sm btn-warning edit-btn mr-1" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                    data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
                                                    data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                                    data-toggle="modal" 
                                                    data-target="#editUserModal"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="konfirmasiHapus(<?php echo $row['id']; ?>)" 
                                                    class="btn btn-sm btn-danger"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tambah User Modal -->
    <div class="modal fade" id="tambahUserModal" tabindex="-1" role="dialog" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahUserModalLabel">Tambah User Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="pemilik">Pemilik</option>
                                <option value="mekanik">Mekanik</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_user" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit Data User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                        <label for="username">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password <small class="text-muted">(Kosongkan jika tidak ingin mengubah password)</small></label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" class="form-control" id="edit_nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="pemilik">Pemilik</option>
                                <option value="mekanik">Mekanik</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
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
    
    <!--  Sidebar JS -->
    <script src="assets/js/smooth-sidebar.js"></script>



    <script>
        // Inisialisasi DataTable
        $(document).ready(function() {
            $('#dataTable').DataTable();
            
            // Mengisi form edit dengan data user yang dipilih
            $('.edit-btn').on('click', function() {
                var id = $(this).data('id');
                var username = $(this).data('username');
                var nama = $(this).data('nama');
                var role = $(this).data('role');
                
                $('#edit_id').val(id);
                $('#edit_username').val(username);
                $('#edit_nama_lengkap').val(nama);
                $('#edit_role').val(role);
                $('#edit_password').val(''); // Reset password field
            });
        });
        
        // Konfirmasi hapus user
        function konfirmasiHapus(id) {
            if(confirm("Apakah Anda yakin ingin menghapus user ini?")) {
                window.location.href = "manajemenuser.php?hapus=" + id;
            }
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
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
        }

        .btn-sm i {
            font-size: 0.875rem;
        }

        td .btn + .btn {
            margin-left: 0.25rem;
        }
    </style>

</body>

</html>