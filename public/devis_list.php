<?php
require_once __DIR__ . '/../includes/db.php';

$rows = $pdo->query("SELECT * FROM quotes ORDER BY date_devis DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>

<h1>Devis</h1>
<a href="devis_create.php">➕ Nouveau devis</a>

<table border="1" cellpadding="6">
<tr>
<th>N°</th>
<th>Client</th>
<th>Date</th>
<th>HT</th>
<th>TTC</th>
<th>Statut</th>
</tr>

<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['numero_devis'] ?></td>
<td><?= $r['nom_client'] ?></td>
<td><?= $r['date_devis'] ?></td>
<td><?= $r['total_ht'] ?></td>
<td><?= $r['total_ttc'] ?></td>
<td><?= $r['statut'] ?></td>
<td>
  <a href="devis_delete.php?id=<?= $r['id'] ?>"
     onclick="return confirm('Supprimer définitivement ce devis ?')">
     🗑 Supprimer
  </a>
</td>

</tr>
<?php endforeach; ?>

</table>

</body>
</html>
