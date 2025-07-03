<?php
// --- BAGIAN LOGIKA PHP (TIDAK ADA PERUBAHAN) ---
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') { header("Location: ../login.php"); exit(); }

$mahasiswa_id = $_SESSION['user_id'];

// Hitung praktikum yang diikuti
$stmt_prak = $conn->prepare("SELECT COUNT(id) AS total FROM pendaftaran_praktikum WHERE mahasiswa_id = ?");
$stmt_prak->bind_param("i", $mahasiswa_id);
$stmt_prak->execute();
$result_prak = $stmt_prak->get_result()->fetch_assoc();
$prak_diikuti = $result_prak ? $result_prak['total'] : 0;
$stmt_prak->close();

// Hitung tugas yang selesai dinilai
$stmt_selesai = $conn->prepare("SELECT COUNT(l.id) AS total FROM laporan l WHERE l.mahasiswa_id = ? AND l.status = 'Dinilai'");
$stmt_selesai->bind_param("i", $mahasiswa_id);
$stmt_selesai->execute();
$result_selesai = $stmt_selesai->get_result()->fetch_assoc();
$tugas_selesai = $result_selesai ? $result_selesai['total'] : 0;
$stmt_selesai->close();

// Hitung total modul dari praktikum yang diikuti
$stmt_total_modul = $conn->prepare("SELECT COUNT(m.id) AS total FROM modul m JOIN pendaftaran_praktikum pp ON m.praktikum_id = pp.praktikum_id WHERE pp.mahasiswa_id = ?");
$stmt_total_modul->bind_param("i", $mahasiswa_id);
$stmt_total_modul->execute();
$result_total_modul = $stmt_total_modul->get_result()->fetch_assoc();
$total_modul = $result_total_modul ? $result_total_modul['total'] : 0;
$stmt_total_modul->close();

// Hitung laporan yang sudah terkumpul
$stmt_terkumpul = $conn->prepare("SELECT COUNT(id) AS total FROM laporan WHERE mahasiswa_id = ?");
$stmt_terkumpul->bind_param("i", $mahasiswa_id);
$stmt_terkumpul->execute();
$result_terkumpul = $stmt_terkumpul->get_result()->fetch_assoc();
$laporan_terkumpul = $result_terkumpul ? $result_terkumpul['total'] : 0;
$stmt_terkumpul->close();

// Hitung tugas yang perlu dikerjakan (modul yang belum dikumpulkan laporannya)
$tugas_menunggu = $total_modul - $laporan_terkumpul;
if ($tugas_menunggu < 0) $tugas_menunggu = 0;

// Ambil notifikasi nilai terbaru (misal 5 terakhir)
$notifikasi_nilai = $conn->query("SELECT m.nama_modul, mp.nama_praktikum, l.nilai FROM laporan l JOIN modul m ON l.modul_id = m.id JOIN mata_praktikum mp ON m.praktikum_id = mp.id WHERE l.mahasiswa_id = $mahasiswa_id AND l.status = 'Dinilai' ORDER BY l.tanggal_kumpul DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Menghitung persentase progres yang logis (tugas selesai / total modul)
$completion_percentage = $total_modul > 0 ? ($tugas_selesai / $total_modul) * 100 : 0;

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php';
?>

<!-- ======================================================= -->
<!-- BAGIAN TAMPILAN HTML BARU -->
<!-- ======================================================= -->
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>

    <!-- Kartu Statistik Utama -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card: Praktikum Diikuti -->
        <div class="bg-white p-5 rounded-lg shadow-sm flex items-center gap-4 border-l-4 border-blue-500">
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-layer-group text-xl text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Praktikum Diikuti</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $prak_diikuti; ?></p>
            </div>
        </div>
        <!-- Card: Selesai Dinilai -->
        <div class="bg-white p-5 rounded-lg shadow-sm flex items-center gap-4 border-l-4 border-green-500">
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-check-double text-xl text-green-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Laporan Dinilai</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $tugas_selesai; ?></p>
            </div>
        </div>
        <!-- Card: Perlu Dikerjakan -->
        <div class="bg-white p-5 rounded-lg shadow-sm flex items-center gap-4 border-l-4 border-yellow-500">
            <div class="bg-yellow-100 p-3 rounded-full">
                <i class="fas fa-hourglass-half text-xl text-yellow-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Perlu Dikerjakan</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $tugas_menunggu; ?></p>
            </div>
        </div>
    </div>

    <!-- Baris untuk Progres dan Aktivitas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kolom: Progres Praktikum -->
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Progres Laporan</h3>
            <p class="text-sm text-gray-500 mb-4">Ringkasan laporan yang telah dinilai.</p>
            
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-gradient-to-r from-blue-500 to-teal-400 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $completion_percentage; ?>%"></div>
            </div>
            <div class="flex justify-between mt-2 text-sm font-medium text-gray-600">
                <span>Selesai</span>
                <span><?php echo $tugas_selesai; ?> / <?php echo $total_modul; ?> Modul</span>
            </div>
        </div>

        <!-- Kolom: Aktivitas Terbaru -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Aktivitas Terbaru</h3>
            <div class="space-y-4">
                <?php if (!empty($notifikasi_nilai)): ?>
                    <?php foreach ($notifikasi_nilai as $notif): ?>
                        <div class="flex items-center gap-4 border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                            <div class="bg-green-100 text-green-600 rounded-full h-10 w-10 flex-shrink-0 flex items-center justify-center">
                               <i class="fas fa-award"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="font-semibold text-gray-700">
                                    Nilai diterima untuk <span class="font-bold text-gray-800"><?php echo htmlspecialchars($notif['nama_modul']); ?></span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Praktikum: <?php echo htmlspecialchars($notif['nama_praktikum']); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Nilai</p>
                                <p class="text-xl font-bold text-green-600"><?php echo htmlspecialchars($notif['nilai']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-6">
                        <div class="mx-auto bg-gray-100 rounded-full h-16 w-16 flex items-center justify-center">
                            <i class="fas fa-bell-slash text-2xl text-gray-400"></i>
                        </div>
                        <p class="mt-4 text-gray-600 font-semibold">Belum ada aktivitas terbaru.</p>
                        <p class="text-sm text-gray-400">Nilai akan muncul di sini setelah laporan Anda diperiksa.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- ======================================================= -->
<!-- AKHIR DARI BAGIAN TAMPILAN HTML BARU -->
<!-- ======================================================= -->

<?php
require_once 'templates/footer_mahasiswa.php';
?>