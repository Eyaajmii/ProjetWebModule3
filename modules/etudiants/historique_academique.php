<?php
include "api/obtenir_donnees_academiques.php";
include "includes/fonctions_helpers.php";
verifierConnexion();
include 'includes/header.php';

$etudiant_id = isset($_GET['id']) ? intval($_GET['id']) : getEtudiantIdConnecte();
//$etudiant_id = $_GET['id'];
//$etudiant_id = intval($_GET['id']);//pourrr le tesssst
//$historiqueAcademique = GetParcoursAcademique($_SESSION['etudiant_id']);
$historiqueAcademique = GetParcoursAcademique($etudiant_id);
$historiques = [];
$creditparanne = [];
$creditparsemestre = [];
foreach ($historiqueAcademique as $h) {
    $annee = $h['annee_academique'];
    $semestre = $h['semestre'];
    $historiques[$annee][$semestre][] = $h;
    $credit = ($h['statut'] === 'valide') ? (int)$h['credits_ects'] : 0;
    if (!isset($creditparanne[$annee])) $creditparanne[$annee] = 0;
    $creditparanne[$annee] += $credit;
    if (!isset($creditparsemestre[$annee][$semestre])) $creditparsemestre[$annee][$semestre] = 0;
    $creditparsemestre[$annee][$semestre] += $credit;
}
//Calcule de la moyenne par annee
$moyenneAnnee = [];

foreach ($historiqueAcademique as $h) {
    $annee = $h['annee_academique'];

    if (!isset($moyenneAnnee[$annee])) {
        $moyenneAnnee[$annee] = [
            'somme_notes' => 0,
            'nb_cours' => 0
        ];
    }

    $moyenneAnnee[$annee]['somme_notes'] += $h['note'];
    $moyenneAnnee[$annee]['nb_cours']++;
}
foreach ($moyenneAnnee as $annee => $data) {
    $moyenneAnnee[$annee]['moyenne'] =
        round($data['somme_notes'] / $data['nb_cours'], 2);
}
//Calcule de la moyenne par semestre
$moyenneSemestre = [];

foreach ($historiqueAcademique as $h) {
    $semestre = $h['semestre'];

    if (!isset($moyenneSemestre[$semestre])) {
        $moyenneSemestre[$semestre] = [
            'somme_notes_semester' => 0,
            'nb_cours' => 0
        ];
    }

    $moyenneSemestre[$semestre]['somme_notes_semester'] += $h['note'];
    $moyenneSemestre[$semestre]['nb_cours']++;
}
foreach ($moyenneSemestre as $semestre => $data) {
    $moyenneSemestre[$semestre]['moyenne'] =
        round($data['somme_notes_semester'] / $data['nb_cours'], 2);
}

//chart
$labels = [];
$values = [];

foreach ($historiques as $annee => $semestres) {
    foreach ($semestres as $semestre => $coursSemestre) {
        $sommeNotes = 0;
        $nbCours = count($coursSemestre);

        foreach ($coursSemestre as $c) {
            $sommeNotes += $c['note'];
        }

        $moyenneSemestre = $nbCours ? round($sommeNotes / $nbCours, 2) : 0;
        $labels[] = "Semestre $semestre/$annee";
        $values[] = $moyenneSemestre;
    }
}

?>
<h2>Historique académique</h2>

<div class="table-container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Année</th>
                <th>Semestre</th>
                <th>Cours</th>
                <th>Note</th>
                <th>ECTS acquis</th>
                <th>Statut</th>
                <th>Total ECTS semestre</th>
                <th>Moyenne semestrielle</th>
                <th>Total ECTS année</th>
                <th>Moyenne annuelle</th>
                <th>Relevé</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historiques as $annee => $semestres): ?>
                <?php
                $rowspanAnnee = 0;
                foreach ($semestres as $semestre => $coursSemestre) $rowspanAnnee += count($coursSemestre);
                $anneeAffichee = false;
                ?>
                <?php foreach ($semestres as $semestre => $coursSemestre): ?>
                    <?php
                    $rowspanSemestre = count($coursSemestre);
                    $semestreAffichee = false;
                    $sommeNotesSemestre = array_sum(array_column($coursSemestre, 'note'));
                    $nbCoursSemestre = count($coursSemestre);
                    $moyenneSemestre = $nbCoursSemestre ? round($sommeNotesSemestre / $nbCoursSemestre, 2) : 0;
                    ?>
                    <?php foreach ($coursSemestre as $cours): ?>
                        <tr>
                            <?php if (!$anneeAffichee): ?>
                                <td rowspan="<?= $rowspanAnnee ?>"><?= htmlspecialchars($annee) ?></td>
                                <?php $anneeAffichee = true; ?>
                            <?php endif; ?>

                            <?php if (!$semestreAffichee): ?>
                                <td rowspan="<?= $rowspanSemestre ?>"><?= htmlspecialchars($semestre) ?></td>
                                <?php $semestreAffichee = true; ?>
                            <?php endif; ?>

                            <td><?= htmlspecialchars($cours['cours']) ?></td>
                            <td><?= $cours['note'] ?></td>
                            <td><?= ($cours['statut'] === 'valide') ? $cours['credits_ects'] : 0 ?></td>
                            <td><?= htmlspecialchars($cours['statut']) ?></td>

                            <?php if ($semestreAffichee && !isset($semTotalAffiche[$annee][$semestre])): ?>
                                <td rowspan="<?= $rowspanSemestre ?>"><?= $creditparsemestre[$annee][$semestre] ?></td>
                                <td rowspan="<?= $rowspanSemestre ?>"><?= $moyenneSemestre ?></td>
                                <?php $semTotalAffiche[$annee][$semestre] = true; ?>
                            <?php endif; ?>

                            <?php if ($anneeAffichee && !isset($anneeTotalAffiche[$annee])): ?>
                                <td rowspan="<?= $rowspanAnnee ?>"><?= $creditparanne[$annee] ?></td>
                                <td rowspan="<?= $rowspanAnnee ?>"><?= $moyenneAnnee[$annee]['moyenne'] ?></td>
                                <td rowspan="<?= $rowspanAnnee ?>">
                                    <a href="rapports/releve_notes.php?id=<?= $etudiant_id ?>&annee=<?= $annee ?>" class="btn-download" target="_blank">Télécharger</a>
                                </td>
                                <?php $anneeTotalAffiche[$annee] = true; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<canvas id="progression" style="width: 80%;max-width: 800px;margin: 30px auto;"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('progression').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Moyenne semestrielle',
                data: <?= json_encode($values) ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    suggestedMin: 0,
                    suggestedMax: 20
                }
            }
        }
    });
</script>