<?php
include __DIR__ . '/../includes/config.php';
require_once __DIR__ . "/../dompdf/vendor/autoload.php";

use Dompdf\Dompdf;

$etudiant_id = intval($_GET['id'] ?? 0);
$annee_selectionnee = intval($_GET['annee'] ?? 0);

if (!$etudiant_id || !$annee_selectionnee) die("Paramètres manquants");

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT e.*, u.nom, u.prenom FROM etudiants e JOIN utilisateurs u ON e.utilisateur_id = u.id
    WHERE e.id = ?");
    $stmt->execute([$etudiant_id]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$etudiant) die("Étudiant non trouvé.");

    $stmt = $pdo->prepare("
        SELECT pa.semestre, pa.note, pa.credits_ects, pa.statut, c.nom_cours AS cours
        FROM parcours_academiques pa
        JOIN cours c ON pa.cours_id = c.id
        WHERE pa.etudiant_id = ? AND pa.annee_academique = ?
        ORDER BY pa.semestre, c.nom_cours
    ");
    $stmt->execute([$etudiant_id, $annee_selectionnee]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$notes) die("Aucun cours trouvé pour cette année.");
    $creditparanne = 0;
    $creditparsemestre = [];
    $moyenneSemestre = [];
    foreach ($notes as $n) {
        $sem = $n['semestre'];
        $credit = ($n['statut'] === 'valide') ? $n['credits_ects'] : 0;
        $creditparanne += $credit;
        if (!isset($creditparsemestre[$sem])) $creditparsemestre[$sem] = 0;
        $creditparsemestre[$sem] += $credit;

        if (!isset($moyenneSemestre[$sem])) $moyenneSemestre[$sem] = ['somme' => 0, 'nb' => 0];
        $moyenneSemestre[$sem]['somme'] += $n['note'];
        $moyenneSemestre[$sem]['nb']++;
    }

    foreach ($moyenneSemestre as $sem => $data) $moyenneSemestre[$sem] = round($data['somme'] / $data['nb'], 2);
    $moyenneAnnee = count($notes) ? round(array_sum(array_column($notes, 'note')) / count($notes), 2) : 0;
    $html = "<h1>Relevé de notes - $annee_selectionnee</h1>";
    $html .= "<p><strong>Nom :</strong> {$etudiant['nom']}</p>";
    $html .= "<p><strong>Prénom :</strong> {$etudiant['prenom']}</p>";
    $html .= "<p><strong>Numéro étudiant :</strong> {$etudiant['numero_etudiant']}</p>";
    $html .= "<p><strong>Programme :</strong> {$etudiant['programme']}</p>";
    $html .= "<table border='1' cellpadding='5' cellspacing='0'>
                <tr>
                    <th>Semestre</th>
                    <th>Cours</th>
                    <th>Note</th>
                    <th>Crédit</th>
                    <th>Statut</th>
                    <th>Total ECTS semestre</th>
                    <th>Moyenne semestrielle</th>
                </tr>";

    $currentSemestre = null;
    foreach ($notes as $n) {
        $sem = $n['semestre'];
        $rowspanSem = count(array_filter($notes, fn($x) => $x['semestre'] == $sem));
        $afficheSem = ($currentSemestre !== $sem);
        if ($afficheSem) $currentSemestre = $sem;

        $html .= "<tr>";
        if ($afficheSem) $html .= "<td rowspan='$rowspanSem'>$sem</td>";
        $html .= "<td>{$n['cours']}</td>";
        $html .= "<td>{$n['note']}</td>";
        $html .= "<td>" . (($n['statut'] === 'valide') ? $n['credits_ects'] : 0) . "</td>";
        $html .= "<td>{$n['statut']}</td>";
        if ($afficheSem) {
            $html .= "<td rowspan='$rowspanSem'>{$creditparsemestre[$sem]}</td>";
            $html .= "<td rowspan='$rowspanSem'>{$moyenneSemestre[$sem]}</td>";
        }
        $html .= "</tr>";
    }

    $html .= "<tr>
                <td colspan='5'><strong>Total ECTS année</strong></td>
                <td colspan='2'><strong>$creditparanne</strong></td>
              </tr>
              <tr>
                <td colspan='5'><strong>Moyenne annuelle</strong></td>
                <td colspan='2'><strong>$moyenneAnnee</strong></td>
              </tr></table>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("releve_notes_{$annee_selectionnee}.pdf", ["Attachment" => false]);
    exit;
} catch (PDOException $e) {
    die("Erreur PDO : " . $e->getMessage());
}
?>
