<?php
require_once __DIR__ . '/../includes/db.php';

$quotes = $pdo->query("
    SELECT
        q.id,
        q.numero_devis,
        q.nom_client,
        q.ref_client,
        q.date_devis,
        q.date_validite,
        q.statut,
        q.montant_ht,
        q.prix_remise_ht,
        q.montant_ttc
    FROM quotes q
    ORDER BY q.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

function money_xpf($v): string {
    $n = (float)($v ?? 0);
    return number_format($n, 0, ',', ' ') . " XPF";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des devis</title>
<style>
body { font-family: Arial, sans-serif; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; vertical-align: top; }
th { background:#f5f5f5; }
.actions a { margin-right:8px; text-decoration:none; }
.badge {
  padding:3px 8px; border-radius:4px; font-size:12px; color:#fff; display:inline-block;
}
.BROUILLON { background:#6c757d; }
.ENVOYE { background:#0d6efd; }
.ACCEPTE { background:#198754; }
.REFUSE { background:#dc3545; }
</style>
</head>
<body>

<h1>📄 Liste des devis</h1>

<p>
  <a href="devis_create.php">➕ Nouveau devis</a>
</p>

<table>
<thead>
<tr>
  <th>N° devis</th>
  <th>Client</th>
  <th>Date devis</th>
  <th>Validité</th>
  <th>Statut</th>
  <th>HT brut</th>
  <th>HT remisé</th>
  <th>TTC</th>
  <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($quotes as $q): ?>
<tr>
  <td><?= htmlspecialchars($q['numero_devis'] ?? '') ?></td>
  <td>
    <?= htmlspecialchars($q['nom_client'] ?? '') ?><br>
    <small><?= htmlspecialchars($q['ref_client'] ?? '') ?></small>
  </td>
  <td><?= htmlspecialchars($q['date_devis'] ?? '') ?></td>
  <td><?= htmlspecialchars($q['date_validite'] ?? '') ?></td>
  <td>
    <?php $st = $q['statut'] ?? 'BROUILLON'; ?>
    <span class="badge <?= htmlspecialchars($st) ?>"><?= htmlspecialchars($st) ?></span>
  </td>
  <td><?= money_xpf($q['montant_ht'] ?? 0) ?></td>
  <td><?= money_xpf($q['prix_remise_ht'] ?? 0) ?></td>
  <td><?= money_xpf($q['montant_ttc'] ?? 0) ?></td>
  <td class="actions">
    <a href="quotes_pdf.php?id=<?= (int)$q['id'] ?>" target="_blank">👁 PDF</a>
    <a href="quote_delete.php?id=<?= (int)$q['id'] ?>"
       onclick="return confirm('Supprimer ce devis ?')">🗑</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
