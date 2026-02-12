<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devis</title>
<link rel="stylesheet" href="../assets/app.css">
</head>
<body>

<header class="topbar">
  <div class="title">Création devis</div>
  <div class="controls">
    <button onclick="location.href='index.php'">← Retour</button>
  </div>
</header>

<form id="form">
  <section class="panel">
    <label>Client <input name="nom_client" required></label>
    <label>Réf client <input name="ref_client"></label>
    <label>Date devis <input type="date" name="date_devis" required></label>
    <label>Date validité <input type="date" name="date_validite" required></label>
  </section>

  <section class="panel">
    <textarea name="devis_json" rows="12" placeholder="Lignes du devis (JSON ou texte libre)"></textarea>
  </section>

  <section class="panel">
    <label>HT <input name="montant_ht"></label>
    <label>Remise % <input name="remise"></label>
    <label>HT remisé <input name="prix_remise_ht"></label>
    <label>TVA % <input name="taux_tva"></label>
    <label>TVA <input name="montant_tva"></label>
    <label>TTC <input name="montant_ttc"></label>
  </section>

  <button class="primary">💾 Enregistrer</button>
</form>

<script>
document.getElementById("form").addEventListener("submit", async e => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  const res = await fetch("/compta-suivi/api/quotes_save.php", {
    method:"POST",
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(data)
  });
  const js = await res.json();
  if(js.ok) location.href="index.php";
  else alert(js.error);
});
</script>
</body>
</html>
