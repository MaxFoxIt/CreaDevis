<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Devis</title>

<link href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator.min.css" rel="stylesheet">
<script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>

<link rel="stylesheet" href="../assets/app.css">
</head>
<body>

<header class="topbar">
  <div class="title">Devis</div>
  <div class="controls">
    <button onclick="location.href='edit.php'">➕ Nouveau devis</button>
    <button onclick="location.href='../index.php'">← Retour factures</button>
  </div>
</header>

<div id="table"></div>

<script>
new Tabulator("#table", {
  layout:"fitColumns",
  ajaxURL:"/compta-suivi/api/quotes_list.php",

  columns:[
    {title:"N° Devis", field:"numero_devis"},
    {title:"Client", field:"nom_client"},
    {title:"Date", field:"date_devis"},
    {title:"Validité", field:"date_validite"},
    {title:"HT remisé", field:"prix_remise_ht", hozAlign:"right"},
    {title:"Statut", field:"statut"},
    {
      title:"Actions",
      formatter: () =>
        `<button class="rowbtn edit">✏️</button>
         <button class="rowbtn pdf">📄</button>`
      ,
      cellClick:(e, cell)=>{
        const d = cell.getRow().getData();
        if(e.target.classList.contains("edit")) location.href=`edit.php?id=${d.id}`;
        if(e.target.classList.contains("pdf")) window.open(`/compta-suivi/api/quotes_pdf.php?id=${d.id}`);
      }
    }
  ]
});
</script>
</body>
</html>
