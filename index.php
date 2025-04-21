<?php
// Koneksi ke database MySQL
$koneksi = mysqli_connect("localhost", "root", "", "ukk2025_todolist");

// Inisialisasi variabel untuk mode edit
$edit_mode = false;
$edit_id = "";
$edit_task = "";
$edit_priority = "";
$edit_due_date = "";

// Proses update task
if (isset($_POST['update_task'])) {
    $id = $_POST['id'];
    $task = $_POST['task'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];

    // Cek apakah field tidak kosong
    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        // Update data task di database
        mysqli_query($koneksi, "UPDATE task SET task = '$task', priority = '$priority', due_date = '$due_date' WHERE id = '$id'");
        echo "<script>alert('Task berhasil diperbarui')</script>";
        header("location: index.php"); // Redirect ke halaman utama
        exit;
    } else {
        echo "<script>alert('Task gagal diperbarui')</script>";
    }
}

// Proses tambah task
if (isset($_POST['add_task'])) {
    $task = $_POST['task'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];

    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        // Insert data baru ke database
        mysqli_query($koneksi, "INSERT INTO task VALUES ('', '$task', '$priority', '$due_date', '0')"); // 0 = belum selesai
        echo "<script>alert('Task berhasil ditambahkan')</script>";
    } else {
        echo "<script>alert('Task gagal ditambahkan')</script>";
    }
    header("location: index.php");
    exit;
}

// Mode edit: ambil data berdasarkan id
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_mode = true;

    // Ambil data task berdasarkan id
    $query_edit = mysqli_query($koneksi, "SELECT * FROM task WHERE id = '$edit_id'");
    $data_edit = mysqli_fetch_assoc($query_edit);

    // Simpan ke variabel untuk ditampilkan di form
    $edit_task = $data_edit['task'];
    $edit_priority = $data_edit['priority'];
    $edit_due_date = $data_edit['due_date'];
}

// Selesaikan task
if (isset($_GET['complete'])) {
    $id = $_GET['complete'];
    mysqli_query($koneksi, "UPDATE task SET status = '1' WHERE id = '$id'");
    echo "<script>alert('Task berhasil diselesaikan')</script>";
    header("location: index.php");
    exit;
}

// Undo task
if (isset($_GET['undo'])) {
    $id = $_GET['undo'];
    mysqli_query($koneksi, "UPDATE task SET status = '0' WHERE id = '$id'");
    echo "<script>alert('Task berhasil dikembalikan ke status belum selesai')</script>";
    header("location: index.php");
    exit;
}

// Hapus task
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM task WHERE id = '$id'");
    echo "<script>alert('Task berhasil dihapus')</script>";
    header("location: index.php");
    exit;
}

// Pagination: atur jumlah data per halaman
$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$where = []; // Menyimpan kondisi pencarian/filter

// Pencarian task berdasarkan keyword
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($koneksi, $_GET['search']);
    $where[] = "task LIKE '%$search%'";
}

// Filter berdasarkan status
if (isset($_GET['status_filter']) && ($_GET['status_filter'] === '0' || $_GET['status_filter'] === '1')) {
    $status = $_GET['status_filter'];
    $where[] = "status = '$status'";
}

// Filter berdasarkan prioritas
if (isset($_GET['priority_filter']) && in_array($_GET['priority_filter'], ['1', '2', '3'])) {
    $priority = $_GET['priority_filter'];
    $where[] = "priority = '$priority'";
}

// Gabungkan kondisi WHERE jika ada
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Hitung total data sesuai filter
$total_result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM task $where_sql");
$total_data = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data task sesuai filter dan pagination
$result = mysqli_query($koneksi, "SELECT * FROM task $where_sql ORDER BY status ASC, priority DESC, due_date ASC LIMIT $start, $limit");
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Metadata dan link CSS Bootstrap dan FontAwesome -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi To Do List | UKK RPL 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container mt-2">
        <h2 class="text-center">Aplikasi To Do List</h2>

        <!-- Form untuk menambah/edit task -->
        <form action="" method="post" class="border rounded bg-light p-2">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_id) ?>">

            <label class="form-label">Nama Task</label>
            <input type="text" name="task" class="form-control" placeholder="Masukkan Task Baru" required
                value="<?= htmlspecialchars($edit_task) ?>">

            <label class="form-label">Prioritas</label>
            <select name="priority" class="form-control" required>
                <option value="">--Pilih Prioritas--</option>
                <option value="1" <?= $edit_priority == 1 ? 'selected' : '' ?>>Low</option>
                <option value="2" <?= $edit_priority == 2 ? 'selected' : '' ?>>Medium</option>
                <option value="3" <?= $edit_priority == 3 ? 'selected' : '' ?>>High</option>
            </select>

            <label class="form-label">Tanggal</label>
            <input type="date" name="due_date" class="form-control"
                value="<?= htmlspecialchars($edit_due_date ?: date('Y-m-d')) ?>" min="<?= date('Y-m-d') ?>" required>

            <?php if ($edit_mode) { ?>
                <button class="btn btn-warning w-100 mt-2" name="update_task">Update</button>
                <a href="index.php" class="btn btn-secondary w-100 mt-2">Batal</a>
            <?php } else { ?>
                <button class="btn btn-primary w-100 mt-2" name="add_task">Tambah</button>
            <?php } ?>
        </form>

        <!-- Form pencarian dan filter -->
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari task..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>

            <div class="col-md-2">
                <select name="status_filter" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="0" <?= $_GET['status_filter'] === '0' ? 'selected' : '' ?>>Belum Selesai</option>
                    <option value="1" <?= $_GET['status_filter'] === '1' ? 'selected' : '' ?>>Selesai</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="priority_filter" class="form-select">
                    <option value="">-- Semua Prioritas --</option>
                    <option value="1" <?= $_GET['priority_filter'] === '1' ? 'selected' : '' ?>>Low</option>
                    <option value="2" <?= $_GET['priority_filter'] === '2' ? 'selected' : '' ?>>Medium</option>
                    <option value="3" <?= $_GET['priority_filter'] === '3' ? 'selected' : '' ?>>High</option>
                </select>
            </div>

            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Cari / Filter</button>
            </div>
        </form>

        <hr>
    </div>

    <!-- Tabel daftar task -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Task</th>
                <th>Priority</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($result) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= $row['task']; ?></td>
                        <td>
                            <?= $row['priority'] == 1 ? 'Low' : ($row['priority'] == 2 ? 'Medium' : 'High') ?>
                        </td>
                        <td><?= $row['due_date']; ?></td>
                        <td>
                            <?= $row['status'] == 0 ? "<span style='color: red;'>Belum Selesai</span>" : "<span style='color: green;'>Selesai</span>" ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 0) { ?>
                                <a href="?complete=<?= $row['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Selesai</a>
                            <?php } else { ?>
                                <a href="?undo=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-undo"></i> Undo</a>
                            <?php } ?>
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</a>
                        </td>
                    </tr>
                <?php }
            }
            ?>
        </tbody>
    </table>

    <!-- Navigasi Pagination -->
    <div class="container">
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="?page=<?= $page - 1 ?>&search=<?= $_GET['search'] ?? '' ?>&status_filter=<?= $_GET['status_filter'] ?? '' ?>&priority_filter=<?= $_GET['priority_filter'] ?? '' ?>"
                           aria-label="Previous">&laquo;</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $i ?>&search=<?= $_GET['search'] ?? '' ?>&status_filter=<?= $_GET['status_filter'] ?? '' ?>&priority_filter=<?= $_GET['priority_filter'] ?? '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="?page=<?= $page + 1 ?>&search=<?= $_GET['search'] ?? '' ?>&status_filter=<?= $_GET['status_filter'] ?? '' ?>&priority_filter=<?= $_GET['priority_filter'] ?? '' ?>"
                           aria-label="Next">&raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>
</html>