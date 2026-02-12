<?php
function get_all_invoices() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM invoices ORDER BY date_facturation DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
