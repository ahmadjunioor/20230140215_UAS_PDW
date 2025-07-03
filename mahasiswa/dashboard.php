<?php
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'mahasiswa') { header("Location: ../login.php"); exit(); }

$mahasiswa_id = $_SESSION['user_id'];


$stmt_prak = $conn->prepare("SELECT COUNT(id) AS total FROM pendaftaran_praktikum WHERE mahasiswa_id = ?");
$stmt_prak->bind_param("i", $mahasiswa_id);
$stmt_prak->execute();
$result_prak = $stmt_prak->get_result()->fetch_assoc();
$prak_diikuti = $result_prak ? $result_prak['total'] : 0;
$stmt_prak->close();


$stmt_selesai = $conn->prepare("SELECT COUNT(l.id) AS total FROM laporan l WHERE l.mahasiswa_id = ? AND l.status = 'Dinilai'");
$stmt_selesai->bind_param("i", $mahasiswa_id);
$stmt_selesai->execute();
$result_selesai = $stmt_selesai->get_result()->fetch_assoc();
$tugas_selesai = $result_selesai ? $result_selesai['total'] : 0;
$stmt_selesai->close();


$stmt_total_modul = $conn->prepare("SELECT COUNT(m.id) AS total FROM modul m JOIN pendaftaran_praktikum pp ON m.praktikum_id = pp.praktikum_id WHERE pp.mahasiswa_id = ?");
$stmt_total_modul->bind_param("i", $mahasiswa_id);
$stmt_total_modul->execute();
$result_total_modul = $stmt_total_modul->get_result()->fetch_assoc();
$total_modul = $result_total_modul ? $result_total_modul['total'] : 0;
$stmt_total_modul->close();


$stmt_terkumpul = $conn->prepare("SELECT COUNT(id) AS total FROM laporan WHERE mahasiswa_id = ?");
$stmt_terkumpul->bind_param("i", $mahasiswa_id);
$stmt_terkumpul->execute();
$result_terkumpul = $stmt_terkumpul->get_result()->fetch_assoc();
$laporan_terkumpul = $result_terkumpul ? $result_terkumpul['total'] : 0;
$stmt_terkumpul->close();


$tugas_menunggu = $total_modul - $laporan_terkumpul;
if ($tugas_menunggu < 0) $tugas_menunggu = 0;


$notifikasi_nilai = $conn->query("SELECT m.nama_modul, mp.nama_praktikum, l.nilai FROM laporan l JOIN modul m ON l.modul_id = m.id JOIN mata_praktikum mp ON m.praktikum_id = mp.id WHERE l.mahasiswa_id = $mahasiswa_id AND l.status = 'Dinilai' ORDER BY l.tanggal_kumpul DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);



$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php'; 
?>


<div class="space-y-6">

    <?php
        // PHP logic remains unchanged
        $total_assignments = $tugas_selesai + $tugas_menunggu;
        $completion_percentage = $total_assignments > 0 ? ($tugas_selesai / $total_assignments) * 100 : 0;
    ?>

    <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-6 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-indigo-900 mb-2">Progres Praktikum</h3>
        <p class="text-sm text-slate-500 mb-5">Ringkasan progres Anda sejauh ini.</p>
        
        <div class="w-full bg-slate-200 rounded-full h-2">
            <div class="bg-gradient-to-r from-indigo-500 to-violet-500 h-2 rounded-full transition-all duration-700 ease-out" style="width: <?php echo $completion_percentage; ?>%"></div>
        </div>
        <div class="flex justify-between mt-2 text-sm font-medium text-slate-600">
            <span>Selesai</span>
            <span><?php echo $tugas_selesai; ?> / <?php echo $total_assignments; ?> Modul</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-6 rounded-2xl shadow-lg flex justify-between items-center">
            <div>
                <p class="text-sm font-normal text-slate-500">Praktikum Diikuti</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo $prak_diikuti; ?></p>
            </div>
            <div class="bg-indigo-100 p-3 rounded-xl">
                 <i class="fa-solid fa-layer-group text-xl text-indigo-600"></i>
            </div>
        </div>
        <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-6 rounded-2xl shadow-lg flex justify-between items-center">
            <div>
                <p class="text-sm font-normal text-slate-500">Selesai Dinilai</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo $tugas_selesai; ?></p>
            </div>
             <div class="bg-indigo-100 p-3 rounded-xl">
                <i class="fa-solid fa-check-double text-xl text-indigo-600"></i>
            </div>
        </div>
        <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-6 rounded-2xl shadow-lg flex justify-between items-center">
            <div>
                <p class="text-sm font-normal text-slate-500">Perlu Dikerjakan</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo $tugas_menunggu; ?></p>
            </div>
             <div class="bg-indigo-100 p-3 rounded-xl">
                <i class="fa-solid fa-hourglass-half text-xl text-indigo-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-6 rounded-2xl shadow-lg">
        <h3 class="text-xl font-bold text-indigo-900 mb-6">Aktivitas Terbaru</h3>
        <div class="relative border-l-2 border-slate-200 pl-8 space-y-8">
            <?php if (!empty($notifikasi_nilai)): ?>
                <?php foreach ($notifikasi_nilai as $notif): ?>
                    <div class="relative group">
                        <div class="absolute -left-[43px] top-1 w-5 h-5 bg-indigo-600 rounded-full ring-8 ring-white transition-transform duration-300 group-hover:scale-110"></div>
                        <div>
                             <p class="font-bold text-slate-800 text-base">
                                Nilai <span class="text-2xl font-extrabold text-indigo-600"><?php echo htmlspecialchars($notif['nilai']); ?></span> diterima
                             </p>
                             <p class="text-md text-slate-600 mt-1">
                                <?php echo htmlspecialchars($notif['nama_modul']); ?>
                             </p>
                             <p class="text-sm text-slate-500">
                                <span class="font-medium">Praktikum:</span> <?php echo htmlspecialchars($notif['nama_praktikum']); ?>
                             </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="relative">
                    <div class="absolute -left-[43px] top-1.5 w-5 h-5 bg-slate-300 rounded-full ring-8 ring-white"></div>
                    <p class="text-slate-600">Belum ada aktivitas terbaru.</p>
                    <p class="text-sm text-slate-400">Nilai akan muncul di sini setelah laporan Anda diperiksa.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php
require_once 'templates/footer_mahasiswa.php';
?>