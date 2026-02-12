<?php
require_once __DIR__.'/../includes/db.php';
$data = json_decode(file_get_contents("php://input"), true);

$numero = "D".date('Ymd')."-".rand(100,999);

$stmt = $pdo->prepare("
  INSERT INTO quotes
  (numero_devis, nom_client, ref_client, date_devis, date_validite,
   montant_ht, remise, prix_remise_ht, taux_tva, montant_tva, montant_ttc, devis_json)
  VALUES
  (:num,:client,:ref,:dd,:dv,:ht,:rem,:htr,:tva,:mtva,:ttc,:json)
");

$stmt->execute([
  ':num'=>$numero,
  ':client'=>$data['nom_client'],
  ':client'=>$data['societe'],
  ':ref'=>$data['ref_client'],
  ':dd'=>$data['date_devis'],
  ':dv'=>$data['date_validite'],
  ':ht'=>$data['montant_ht'],
  ':rem'=>$data['remise'],
  ':htr'=>$data['prix_remise_ht'],
  ':tva'=>$data['taux_tva'],
  ':mtva'=>$data['montant_tva'],
  ':ttc'=>$data['montant_ttc'],
  ':json'=>$data['devis_json'],
]);

echo json_encode(['ok'=>true]);
