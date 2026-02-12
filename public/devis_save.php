<?php
ob_start();

require_once __DIR__ . '/../includes/db.php';
$action = $_POST['action'] ?? 'save';

// --------------------
// Sécurité minimale
// --------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accès interdit');
}

// --------------------
// Données principales
// --------------------
$clientId      = (int)($_POST['client_id'] ?? 0);
$units = $_POST['unit'] ?? [];
$auteur        = trim($_POST['auteur'] ?? '');
$dateDevis     = $_POST['date_devis'] ?? date('Y-m-d');
$dateValidite  = $_POST['date_validite'] ?? date('Y-m-d');
$tva           = (float)($_POST['tva'] ?? 0);
$remise        = (float)($_POST['remise'] ?? 0);
$raisonRemise  = trim($_POST['raison_remise'] ?? '');
$acompte       = isset($_POST['acompte']) ? 40 : 0;
$commentaires  = trim($_POST['commentaires'] ?? '');
$companyKey    = $_POST['company_key'] ?? null;

// --------------------
// Lignes
// --------------------
$descs = $_POST['desc'] ?? [];
$qtys  = $_POST['qty'] ?? [];
$pus   = $_POST['pu'] ?? [];

// --------------------
// Calculs
// --------------------
$totalHT = 0;
$lines = [];

for ($i = 0; $i < count($descs); $i++) {
    $desc = trim($descs[$i]);
	$unite = $units[$i] ?? 'unité';
    if ($desc === '') continue;

    $qty = (float)$qtys[$i];
    $pu  = (float)$pus[$i];
    $lineTotal = $qty * $pu;

    $totalHT += $lineTotal;

$lines[] = [
    'description' => $desc,
    'quantite' => $qty,
    'unite' => $unite,
    'prix_unitaire' => $pu,
    'total_ht' => $lineTotal,
];


}

$montantRemise = $totalHT * $remise / 100;
$htRemise = $totalHT - $montantRemise;
$montantTVA = $htRemise * $tva / 100;
$totalTTC = $htRemise + $montantTVA;

// --------------------
// CLIENT (source de vérité)
// --------------------
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
$stmt->execute([':id' => $clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die('Client invalide');
}

$refClient = trim((string)($client['numero_client'] ?? ''));

if ($refClient === '') {
    die("Client sans référence — impossible de créer un devis");
}

// --------------------
// NUMÉRO DEVIS (OPTION C)
// --------------------
$year = date('Y');

$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM quotes 
    WHERE YEAR(created_at) = $year
");
$seq = (int)$stmt->fetchColumn() + 1;

$numeroDevis = sprintf(
    'D%s-%04d-%s',
    $year,
    $seq,
    $refClient
);


// --------------------
// JSON devis
// --------------------
$devisJson = json_encode([
    'auteur' => $auteur,
    'commentaires' => $commentaires,
    'raison_remise' => $raisonRemise,
    'company_key' => $companyKey,
'client' => [
    'id' => $client['id'],
    'nom' => $client['nom_client'],
    'type' => $client['type_client'] ?? '',
    'ref' => $refClient
],
    'acompte' => $acompte,
    'lines' => $lines,
], JSON_UNESCAPED_UNICODE);

// --------------------
// INSERT QUOTE
// --------------------
$stmt = $pdo->prepare("
    INSERT INTO quotes (
        client_id, numero_devis, ref_client, nom_client, type_client,
        date_devis, date_validite, statut,
        montant_ht, remise, prix_remise_ht,
        taux_tva, montant_tva, montant_ttc,
        acompte_pourcent, devis_json
    ) VALUES (
        :client_id, :numero, :ref, :nom, :type,
        :date_devis, :date_validite, 'BROUILLON',
        :ht, :remise, :ht_remise,
        :tva, :mt_tva, :ttc,
        :acompte, :json
    )
");

$stmt->execute([
    ':client_id' => $clientId,
    ':numero' => $numeroDevis,
    ':ref' => $refClient,
    ':nom' => $client['nom_client'],
    ':type' => $client['type_client'] ?? '',
    ':date_devis' => $dateDevis,
    ':date_validite' => $dateValidite,
    ':ht' => $totalHT,
    ':remise' => $remise,
    ':ht_remise' => $htRemise,
    ':tva' => $tva,
    ':mt_tva' => $montantTVA,
    ':ttc' => $totalTTC,
    ':acompte' => $acompte,
    ':json' => $devisJson,
]);

$quoteId = $pdo->lastInsertId();

// --------------------
// INSERT LIGNES
// --------------------
$stmtLine = $pdo->prepare("
    INSERT INTO quote_lines
    (quote_id, description, quantite, unite, prix_unitaire, total_ht)
    VALUES
    (:qid, :desc, :qty, :unite, :pu, :total)
");


foreach ($lines as $l) {
$stmtLine->execute([
    ':qid'   => $quoteId,
    ':desc'  => $l['description'],
    ':qty'   => $l['quantite'],
    ':unite' => $l['unite'],
    ':pu'    => $l['prix_unitaire'],
    ':total' => $l['total_ht'],
]);

}
if (empty($lines)) {
    die("Aucune ligne de devis n’a été ajoutée.");
}

// --------------------
// REDIRECTION PDF (CRITIQUE)
// --------------------
if ($action === 'pdf') {
    header('Location: quotes_pdf.php?id=' . $quoteId . '&mode=save');
    exit;
}


// Sinon : simple enregistrement
header('Location: quotes_list.php');
exit;

