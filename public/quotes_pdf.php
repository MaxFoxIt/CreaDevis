<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/fpdf.php';
require_once __DIR__ . '/../includes/config_paths.php';
$wDesc = 90;
$wQty  = 30;
$wPU   = 35;
$wTot  = 35;

function fmt_qty($qty, $unit) {
    $map = ['jour'=>'jours','heure'=>'heures','unité'=>'unités'];
    $u = ($qty > 1 && isset($map[$unit])) ? $map[$unit] : $unit;
    $q = rtrim(rtrim(number_format((float)$qty, 2, '.', ''), '0'), '.');
    return $q.' '.$u;
}


function pdf_txt(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
}

$id   = (int)($_GET['id'] ?? 0);
$mode = $_GET['mode'] ?? 'preview'; // preview | save

if ($id <= 0) die("Devis invalide");

/* =========================
   Chargement devis
========================= */
$stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = :id");
$stmt->execute([':id' => $id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quote) die("Devis introuvable");

$meta = json_decode($quote['devis_json'], true);
if (!$meta) die("JSON devis invalide");

/* =========================
   Société
========================= */
$companies = require __DIR__ . '/../includes/companies.php';
$company = $companies[$meta['company_key']] ?? null;
if (!$company) die("Société inconnue");

/* =========================
   Lignes
========================= */
$stmt = $pdo->prepare("SELECT * FROM quote_lines WHERE quote_id = :id");
$stmt->execute([':id' => $id]);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PDF
========================= */
$pdf = new FPDF();
$pdf->AddPage();

/* Logo */
if (!empty($company['logo'])) {
    $logo = realpath(__DIR__ . '/../assets/' . $company['logo']);
    if ($logo) {
        $pdf->Image($logo, 10, 10, 22);
    }
}

/* Titre */
$pdf->SetFont('Arial','B',16);
$pdf->SetXY(70,15);
$pdf->Cell(0,10,'DEVIS '.$quote['numero_devis'],0,1);
$pdf->Ln(15);

/* Société */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,pdf_txt($company['nom']),0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,5,pdf_txt(
		trim(
		 ($company['adresse']?? '') . "\n" .
		 ($company['siret']?? '')
		 )));
$pdf->Ln(6);

/* Client */
// --------------------
// Client (robuste)
// --------------------
$client = [
    'nom'     => '',
    'societe' => '',
    'adresse' => '',
    'ref'     => $quote['ref_client'] ?? ''
];

// 1️⃣ Charger depuis la table clients (source fiable)
if (!empty($quote['client_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
    $stmt->execute([':id' => $quote['client_id']]);
    if ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $client = [
            'nom'     => $c['nom_client'] ?? '',
            'societe' => $c['societe'] ?? '',
            'adresse' => $c['adresse'] ?? '',
            'ref'     => $c['numero_client'] ?? ($quote['ref_client'] ?? '')
        ];
    }
}

// 2️⃣ Compléter avec le JSON si présent
if (!empty($meta['client']) && is_array($meta['client'])) {
    $client = array_merge($client, $meta['client']);
}

// Position bloc client (DROITE)
$xClient = 120;
$yClient = 55;
$wClient = 70;

$pdf->SetXY($xClient, $yClient);

// Titre
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($wClient, 6, pdf_txt('Devis pour :'), 0, 1);

// Contenu client
$pdf->SetFont('Arial', '', 10);
$pdf->SetX($xClient);

$pdf->MultiCell(
    $wClient,
    6,
    pdf_txt(
        trim(
            ($client['societe'] ? $client['societe']."\n" : '') .
            ($client['nom'] ? $client['nom']."\n" : '') .
            ($client['adresse'] ? $client['adresse']."\n" : '') .
            ($client['ref'] ? 'Réf : '.$client['ref'] : '')
        )
    ),
    0
);
$pdf->SetY(max($pdf->GetY(), $yClient + 30));
// Infos devis
$pdf->SetFont('Arial', '', 10);
$dateDevis = date('d-m-Y', strtotime($quote['date_devis']));
$dateValidite = date('d-m-Y', strtotime($quote['date_validite']));

$pdf->Cell(0, 6, 'Date du devis : ' . $dateDevis, 0, 1);
$pdf->Cell(0, 6, 'Valable jusqu\'au : ' . $dateValidite, 0, 1);
if (!empty($meta['auteur'])) {
    $pdf->Cell(
        0,
        6,
        'Auteur du devis : ' . pdf_txt($meta['auteur']),
        0,
        1
    );
}
if (!empty($meta['commentaires'])) {
    $pdf->Ln(8);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,6,pdf_txt('Commentaires / instructions spéciales'),0,1);

    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(
        0,
        6,
        pdf_txt($meta['commentaires']),
        1
    );
}

/* Tableau */
$pdf->Ln(10);
$pdf->SetFont('Arial','B',10);
$pdf->Cell($wDesc,7,pdf_txt('Description'),1);
$pdf->Cell($wQty,7,pdf_txt('Quantité'),1,0,'C');
$pdf->Cell($wPU,7,pdf_txt('PU HT (XPF)'),1,0,'R');
$pdf->Cell($wTot,7,pdf_txt('Total HT (XPF)'),1,1,'R');

$pdf->SetFont('Arial','',10);
$pdf->SetX(10);
foreach ($lines as $l) {
    $x = $pdf->GetX();
    $y = $pdf->GetY();

$pdf->MultiCell($wDesc, 6, pdf_txt($l['description']), 1);
$h = $pdf->GetY() - $y;

$pdf->SetXY($x + $wDesc, $y);

$qty = (float)$l['quantite'];
$unite = $l['unite'] ?? 'unité';

if ($qty > 1) {
    $map = [
        'jour'  => 'jours',
        'heure' => 'heures',
        'unité' => 'unités'
    ];
    $unite = $map[$unite] ?? $unite;
}
// qty == 0 ou 1 → singulier

$qtyLabel = fmt_qty($l['quantite'], $l['unite']);


$pdf->Cell($wQty, $h, pdf_txt($qtyLabel), 1, 0, 'C');

$pdf->Cell(
    $wPU,
    $h,
    number_format($l['prix_unitaire'], 0, '', ' '),
    1,
    0,
    'R'
);

$pdf->Cell(
    $wTot,
    $h,
    number_format($l['total_ht'], 0, '', ' '),
    1,
    1,
    'R'
);

}

/* =========================
   TOTAUX
========================= */
$pdf->Ln(5);
$pdf->SetFont('Arial','B',10);

// Total HT
$pdf->Cell(
    0,
    6,
    'Total HT : ' . number_format($quote['prix_remise_ht'], 0, '', ' ') . ' XPF',
    0,
    1
);

// TVA (uniquement si > 0)
$tauxTVA = (float)($quote['taux_tva'] ?? 0);
$montantTVA = (float)($quote['montant_tva'] ?? 0);

if ($tauxTVA > 0 && $montantTVA > 0) {
    $pdf->Cell(
        0,
        6,
        'Montant TVA (' . rtrim(rtrim($tauxTVA, '0'), '.') . ' %) : ' .
        number_format($montantTVA, 0, '', ' ') . ' XPF',
        0,
        1
    );
}

// Total TTC
$pdf->Cell(
    0,
    6,
    'Total TTC : ' . number_format($quote['montant_ttc'], 0, '', ' ') . ' XPF',
    0,
    1
);
$pdf->Ln(8);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,pdf_txt('Facturation'),0,1);

$pdf->SetFont('Arial','',10);
$pdf->MultiCell(
    0,
    6,
    pdf_txt('40 % à la commande, le solde à la livraison.')
);



/* =========================
   MODE
========================= */
$year    = date('Y', strtotime($quote['date_devis']));
$baseDir = rtrim(QUOTES_PDF_BASE_DIR, '/\\');
$pdfDir  = $baseDir . DIRECTORY_SEPARATOR . $year;
$filename = 'devis_'.$quote['numero_devis'].'.pdf';
$path     = $pdfDir . DIRECTORY_SEPARATOR . $filename;

/* SAVE */
if ($mode === 'save') {

    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0775, true);
    }

    $pdf->Output('F', $path);

    // Sauvegarde du chemin en base (optionnel mais recommandé)
    $stmt = $pdo->prepare("UPDATE quotes SET pdf_path = :p WHERE id = :id");
    $stmt->execute([':p' => $path, ':id' => $id]);

    // Retour UX
    header('Location: quotes_list.php');
    exit;
}

/* PREVIEW */
$pdf->Output('I', $filename);
exit;

