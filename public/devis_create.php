<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<a href="quotes_list.php">📄 Devis</a>
<title>Nouveau devis</title>

<style>
body { font-family: Arial, sans-serif; }
h2 { margin-top: 30px; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; table-layout: fixed; }
td, th { border: 1px solid #ccc; padding: 6px; vertical-align: top; }
input, select, textarea { width: 100%; box-sizing: border-box; }
textarea { resize: none; }
.panel { border:1px solid #ddd; padding:15px; margin-bottom:20px; }
.totaux span { font-weight: bold; }
</style>

</head>
<body>

<?php
require_once __DIR__ . '/../includes/db.php';
$companies = require __DIR__ . '/../includes/companies.php';


$clients = $pdo->query("
  SELECT id, nom_client
  FROM clients
  ORDER BY nom_client
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Nouveau devis</h1>

<form method="post" action="devis_save.php">
<div class="panel">
  <h2>Société émettrice</h2>

  <select name="company_key" required>
    <?php foreach ($companies as $key => $c): ?>
      <option value="<?= $key ?>">
        <?= htmlspecialchars($c['label']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<!-- CLIENT -->
<div class="panel">
  <h2>Client</h2>

  <select name="client_id" required>
    <option value="">— Sélectionner un client —</option>
    <?php foreach($clients as $c): ?>
      <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom_client']) ?></option>
    <?php endforeach; ?>
  </select>

  <p style="font-size:12px;color:#666">
    Client absent ? Ajoute-le via l’outil Clients.
  </p>
</div>

<!-- INFOS DEVIS -->
<div class="panel">
  <h2>Informations du devis</h2>

  <label>Auteur du devis</label>
<input name="auteur" value="Maxime Terrassin" required>

  <label>Date du devis</label>
  <input type="date" name="date_devis" id="date_devis" required>

  <label>Date de validité</label>
  <input type="date" name="date_validite" id="date_validite" readonly>
</div>

<!-- COMMENTAIRES -->
<div class="panel">
  <h2>Commentaires / instructions spéciales</h2>
  <textarea name="commentaires" rows="4">Merci de bien vouloir nous retourner ce devis signé avec la mention « Bon pour accord ».</textarea>
</div>

<!-- CONDITIONS -->
<div class="panel">
  <h2>Conditions financières</h2>

  <label>TVA</label>
  <select id="tva" name="tva" onchange="recalc()">
    <option value="0">0 %</option>
    <option value="13" selected>13 %</option>
    <option value="16">16 %</option>
  </select>

 <label>Remise (%)</label>
<input type="number" id="remise" value="0" onchange="toggleRemise(); recalc()">

<div id="raisonRemiseBox" style="display:none">
  <label>Raison de la remise</label>
  <input name="raison_remise">
</div>


<label style="display:flex; align-items:center; gap:8px;">
  <input type="checkbox" id="acompte" checked onchange="recalc()">
  Acompte de 40 % à la commande
</label>

</div>

<!-- LIGNES -->
<div class="panel">
  <h2>Lignes du devis</h2>

<table id="lines" style="table-layout:fixed; width:100%;">
    <thead>
      <tr>
        <th style="width:40%">Description</th>
<th style="width:20%">Quantité (unité)</th>
<th style="width:20%">PU HT</th>
<th style="width:15%">Total HT</th>
<th style="width:5%"></th>

      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <button type="button" onclick="addLine()">➕ Ajouter une ligne</button>
</div>

<!-- RÉCAP -->
<div class="panel totaux">
  <h2>Récapitulatif</h2>

  Sous-total HT : <span id="st_ht">0.00</span> XPF<br>
  Montant remise : <span id="mt_remise">0.00</span> XPF<br>
  HT après remise : <span id="ht_remise">0.00</span> XPF<br>
  TVA : <span id="mt_tva">0.00</span> XPF<br>
  <strong>Total TTC : <span id="ttc">0.00</span> XPF</strong><br>
  Acompte : <span id="acompte_val">0.00</span> XPF
</div>

<!-- HIDDEN -->
<input type="hidden" name="total_ht">
<input type="hidden" name="total_ttc">

<button type="submit" name="action" value="save">
  💾 Enregistrer (sans PDF)
</button>

<button type="submit" name="action" value="pdf"
        style="background:#1e88e5;color:#fff;padding:10px 14px;border-radius:4px;">
  📄 Enregistrer & générer PDF
</button>

<button type="submit"
        formaction="devis_preview.php"
        formmethod="post"
        formtarget="_blank"
        style="margin-left:10px;">
  👁️ Prévisualiser le PDF
</button>



</form>

<script>

function addLine(){
  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>
      <textarea name="desc[]" rows="2"
  style="width:100%; resize:none;"
  oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>

    </td>
<td>
  <div style="display:flex; gap:4px; align-items:center;">
    <input name="qty[]" type="number" step="0.01"
           value="1" oninput="recalc()" style="width:60%">
    <select name="unit[]" style="width:40%">
      <option value="heure">h</option>
      <option value="jour">j</option>
      <option value="unité" selected>u</option>
    </select>
  </div>
</td>


    <td>
      <input name="pu[]" type="number" step="0.01" value="0" oninput="recalc()">
    </td>
    <td class="line_total">0.00</td>
    <td>
      <button type="button" onclick="this.closest('tr').remove(); recalc()">❌</button>
    </td>
  `;
  document.querySelector("#lines tbody").appendChild(tr);
  recalc();
}





function toggleRemise(){
  const r = Number(document.getElementById("remise").value || 0);
  document.getElementById("raisonRemiseBox").style.display = r > 0 ? "block" : "none";
}

function recalc(){
  let ht = 0;

  document.querySelectorAll("#lines tbody tr").forEach(tr=>{
    const qty = Number(tr.children[1].querySelector("input").value || 0);
    const pu  = Number(tr.children[2].querySelector("input").value || 0);
    const total = qty * pu;
    tr.querySelector(".line_total").innerText = total.toFixed(2);
    ht += total;
  });

  const remise = Number(document.getElementById("remise").value || 0);
  const tva = Number(document.getElementById("tva").value || 0);
  const hasAcompte = document.getElementById("acompte").checked;

  const montantRemise = ht * remise / 100;
  const htRemise = ht - montantRemise;
  const montantTva = htRemise * tva / 100;
  const ttc = htRemise + montantTva;
  const acompte = hasAcompte ? ttc * 0.4 : 0;

  document.getElementById("st_ht").innerText = ht.toFixed(2);
  document.getElementById("mt_remise").innerText = montantRemise.toFixed(2);
  document.getElementById("ht_remise").innerText = htRemise.toFixed(2);
  document.getElementById("mt_tva").innerText = montantTva.toFixed(2);
  document.getElementById("ttc").innerText = ttc.toFixed(2);
  document.getElementById("acompte_val").innerText = acompte.toFixed(2);

  document.querySelector('[name="total_ht"]').value = ht.toFixed(2);
  document.querySelector('[name="total_ttc"]').value = ttc.toFixed(2);
}

document.getElementById("date_devis").addEventListener("change", e => {
  const d = new Date(e.target.value);
  d.setMonth(d.getMonth() + 1);
  document.getElementById("date_validite").value = d.toISOString().split("T")[0];
});

document.addEventListener("DOMContentLoaded", () => {
  addLine(); // 🔥 ligne par défaut
});
</script>

</body>
</html>
