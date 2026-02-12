<?php
require_once __DIR__ . '/../includes/fpdf.php';
require_once __DIR__ . '/../includes/companies.php';

/**
 * Conversion texte UTF-8 → compatible FPDF
 */
function pdf_txt(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
}

/**
 * =========================
 * Données POST
 * =========================
 */
$auteur       = $_POST['auteur'] ?? '';
$dateDevis    = $_POST['date_devis'] ?? date('Y-m-d');
$dateValidite = $_POST['date_validite'] ?? '';
$tva          = (float)($_POST['tva'] ?? 0);
$remise       = (float)($_POST['remise'] ?? 0);
$commentaires = $_POST['commentaires'] ?? '';
$companyKey   = $_POST['company_key'] ?? null;

/**
 * =========================
 * Société
 * =========================
 */
$company = $companies[$companyKey] ?? null;
if (!$company) die('Société inconnue');

/**
 * =========================
 * Lignes
 * =========================
 */
$descs = $_POST['desc'] ?? [];
$qtys  = $_POST['qty'] ?? [];
$pus   = $_POST['pu'] ?? [];

$lines = [];
$totalHT = 0;

for ($i = 0; $i < count($descs); $i++) {
    $desc = trim($descs[$i]);
    if ($desc === '') continue;

    $qty = (float)$qtys[$i];
    $pu  = (float)$pus[$i];
    $total = $qty * $pu;

    $lines[] = [
        'description' => $desc,
        'quantite' => $qty,
        'prix_unitaire' => $pu,
        'total_ht' => $total,
    ];

    $totalHT += $total;
}

/**
 * =========================
 * Calculs
 * =========================
 */
$montantRemise = $totalHT * $remise / 100;
$htRemise = $totalHT - $montantRemise;
$montantTVA = $htRemise * $tva / 100;
$totalTTC = $htRemise + $montantTVA;

/**
 * =========================
 * PDF
 * =========================
 */
$pdf = new FPDF();
$pdf->AddPage();

/* LOGO */
$logoPath = __DIR__ . '/../assets/' . $company['logo'];
if (is_file($logoPath)) {
    $pdf->Image($logoPath, 10, 10, 25); // taille réduite
}

/* TITRE */
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(70, 15);
$pdf->Cell(0, 10, 'DEVIS — PRÉVISUALISATION', 0, 1);
$pdf->Ln(15);

/* Société */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,pdf_txt($company['nom']),0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,5,pdf_txt($company['adresse']));
$pdf->Ln(6);

/* Infos devis */
$pdf->Cell(0,6,"Date du devis : $dateDevis",0,1);
$pdf->Cell(0,6,"Valable jusqu'au : $dateValidite",0,1);
$pdf->Cell(0,6,"Auteur : ".pdf_txt($auteur),0,1);
$pdf->Ln(6);

/* Commentaires */
if ($commentaires) {
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,6,"Commentaires :",0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,pdf_txt($commentaires),1);
    $pdf->Ln(6);
}

/* Tableau */
$pdf->SetFont('Arial','B',10);
$pdf->Cell(90,7,'Description',1);
$pdf->Cell(20,7,'Qté',1,0,'C');
$pdf->Cell(30,7,'PU HT',1,0,'R');
$pdf->Cell(30,7,'Total HT',1,0,'R');
$pdf->Ln();

$pdf->SetFont('Arial','',10);
foreach ($lines as $l) {
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $pdf->MultiCell(90,6,pdf_txt($l['description']),1);
    $h = $pdf->GetY() - $y;

    $pdf->SetXY($x+90,$y);
    $pdf->Cell(20,$h,$l['quantite'],1,0,'C');
    $pdf->Cell(30,$h,number_format($l['prix_unitaire'],2,',',' '),1,0,'R');
    $pdf->Cell(30,$h,number_format($l['total_ht'],2,',',' '),1,0,'R');
    $pdf->Ln();
}

/* Totaux */
$pdf->Ln(5);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,"Total HT : ".number_format($htRemise,2,',',' ')." XPF",0,1);
$pdf->Cell(0,6,"TVA : ".number_format($montantTVA,2,',',' ')." XPF",0,1);
$pdf->Cell(0,6,"Total TTC : ".number_format($totalTTC,2,',',' ')." XPF",0,1);

/* Sortie */
$pdf->Output('I','preview_devis.pdf');
exit;
