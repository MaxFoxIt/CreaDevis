<?php
require_once __DIR__ . '/../includes/db.php';
$paths = require __DIR__ . '/../includes/config_paths.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID invalide');
}

/* =========================
   Récupération devis
========================= */
$stmt = $pdo->prepare("SELECT id, numero_devis FROM quotes WHERE id = :id");
$stmt->execute([':id' => $id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    die('Devis introuvable');
}

/* =========================
   Suppression du PDF
========================= */
$pdfPath = $paths['quotes_dir'] . 'devis_' . $quote['numero_devis'] . '.pdf';

if (file_exists($pdfPath)) {
	var_dump($pdfPath);
var_dump(file_exists($pdfPath));
exit;

    unlink($pdfPath); // 🧨 suppression fichier
}

/* =========================
   Suppression DB (ordre important)
========================= */
$pdo->prepare("DELETE FROM quote_lines WHERE quote_id = :id")
    ->execute([':id' => $id]);

$pdo->prepare("DELETE FROM quotes WHERE id = :id")
    ->execute([':id' => $id]);

/* =========================
   Redirection
========================= */
header('Location: quotes_list.php?deleted=1');
exit;
