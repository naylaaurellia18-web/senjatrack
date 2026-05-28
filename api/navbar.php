<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-slate-200 h-20 flex justify-around items-center px-2 shadow-2xl z-50">
    <!-- Beranda -->
    <a href="dashboard.php" class="flex flex-col items-center gap-1 <?= $current_page == 'dashboard.php' ? 'text-orange-500 font-bold' : 'text-slate-400' ?> no-underline">
        <i class="fa-solid fa-house text-lg"></i>
        <span class="text-[10px]">Beranda</span>
    </a>
    
    <!-- Celengan -->
    <a href="celengan.php" class="flex flex-col items-center gap-1 <?= $current_page == 'celengan.php' ? 'text-orange-500 font-bold' : 'text-slate-400' ?> no-underline">
        <i class="fa-solid fa-piggy-bank text-lg"></i>
        <span class="text-[10px]">Celengan</span>
    </a>

    <!-- Tombol Catat Transaksi di Tengah Mencolok -->
    <a href="catat_transaksi.php" class="flex flex-col items-center justify-center bg-gradient-to-br from-indigo-950 to-orange-500 text-white w-14 h-14 rounded-full -translate-y-4 shadow-lg border-4 border-white transform active:scale-95 transition-all">
        <i class="fa-solid fa-plus text-xl"></i>
    </a>

    <!-- Rencana Belanja -->
    <a href="rencana_belanja.php" class="flex flex-col items-center gap-1 <?= $current_page == 'rencana_belanja.php' ? 'text-orange-500 font-bold' : 'text-slate-400' ?> no-underline">
        <i class="fa-solid fa-basket-shopping text-lg"></i>
        <span class="text-[10px]">Rencana</span>
    </a>

    <!-- Profil -->
    <a href="profil.php" class="flex flex-col items-center gap-1 <?= $current_page == 'profil.php' ? 'text-orange-500 font-bold' : 'text-slate-400' ?> no-underline">
        <i class="fa-solid fa-user text-lg"></i>
        <span class="text-[10px]">Profil</span>
    </a>
</div>