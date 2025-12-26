<?php
include "api/ajouter_absence.php";
include "includes/fonctions_helpers.php";
verifierConnexion();
include 'includes/header.php';

$message = "";

if (isset($_SESSION['role']) && $_SESSION['role'] === 'professeur'):

    $session = GetSessionEnCoursProf($_SESSION['utilisateur_id']);
    if (!$session):
        die("Aucune session de cours en cours pour aujourd'hui.");
    endif;

    $sessionId = $session['session_id'];

    // Bloquer après la fin de la séance
    if (date('H:i:s') > $session['heure_fin']):
        echo "<div class='alert alert-danger'>
               La séance est terminée, modification impossible.
              </div>";
        exit;
    endif;

    $etudiants = GetEtudiantCours($sessionId);
    if ($etudiants === false):
        die("Erreur lors de la récupération des étudiants.");
    endif;

    // Vérifier si appel existe AVANT soumission
    $appelExisteAvant = appelDejaFait($sessionId);
    $presences = $appelExisteAvant ? GetPresencesSession($sessionId) : [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST'):
        $date = $_POST['date_presence'];
        $statuts = $_POST['statut'];
        $profId = $_SESSION['utilisateur_id'];

        $ok = enregistrerAbsences($sessionId, $date, $statuts, $profId);

        if ($ok):
            $message = $appelExisteAvant
                ? "<div class='alert alert-info'>Appel modifié avec succès</div>"
                : "<div class='alert alert-success'>Appel enregistré avec succès</div>";

            $presences = GetPresencesSession($sessionId);
            $appelExisteAvant = true;
        else:
            $message = "<div class='alert alert-danger'>Erreur lors de l'enregistrement</div>";
        endif;
    endif;
?>

    <h2>📋 Appel – <?= htmlspecialchars($session['nom_cours']) ?>
        (<?= $session['heure_debut'] ?> - <?= $session['heure_fin'] ?>)
    </h2>

    <?= $message ?>

    <form method="POST">
        <div class="form-group">
            <label>Date de la séance :</label>
            <input type="date"
                name="date_presence"
                class="form-control"
                value="<?= date('Y-m-d') ?>"
                required
                <?= $appelExisteAvant ? 'readonly' : '' ?>>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Présent</th>
                    <th>Absent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etudiants as $e):
                    $statut = $presences[$e['etudiant_id']] ?? 'present';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
                        <td>
                            <input type="radio"
                                name="statut[<?= $e['etudiant_id'] ?>]"
                                value="present"
                                <?= $statut === 'present' ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <input type="radio"
                                name="statut[<?= $e['etudiant_id'] ?>]"
                                value="absent"
                                <?= $statut === 'absent' ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary">
            <?= $appelExisteAvant ? 'Modifier les absences' : 'Enregistrer les absences' ?>
        </button>
    </form>

<?php
    /**Partie etduaint */
elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant'):

    $etudiant_id = $_SESSION['etudiant_id'];
    $absences = getAbsencesEtudiant($etudiant_id);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['presence_id'])) {
        $presence_id = $_POST['presence_id'];
        $texte = $_POST['justification'] ?? null;
        $fichier = null;

        // Upload fichier si présent
        if (isset($_FILES['fichier_justification']) && $_FILES['fichier_justification']['error'] === 0) {
            $upload_dir = 'uploads/justifications/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $fichier = $upload_dir . basename($_FILES['fichier_justification']['name']);
            move_uploaded_file($_FILES['fichier_justification']['tmp_name'], $fichier);
        }
        $ok = ajouterJustificationAbsence($presence_id, $texte, $fichier);

        if ($ok) {
            $absences = getAbsencesEtudiant($etudiant_id);
        } else {
            echo "<div class='alert alert-danger'>Erreur lors de l'ajout de la justification.</div>";
        }
    }
    $stats = getStatAbsMatiere($etudiant_id);
?>
    <div class="container">
        <?php if ($stats): ?>
            <div class="card">
                <h3 style="text-align: center;"> Statistiques d’absences par matière</h3>
                <div class="chart-wrapper">
                    <canvas id="absenceChart"></canvas>

                </div>
                <?php
                $dangerGlobal = array_filter($stats, fn($s) => $s['total_absences'] >= 4);
                ?>

                <?php if (!empty($dangerGlobal)): ?>
                    <div class="alert alert-danger mt-3">
                        ⚠️ Attention : au moins une matière dépasse le seuil d’absences autorisé (≥ 4).
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Aucune statistique disponible.</div>
        <?php endif; ?>



        <h2>Mes absences</h2>

        <?php if (empty($absences)): ?>
            <div class="alert alert-info">Vous n'avez aucune absence enregistrée.</div>
        <?php else: ?>

            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Cours</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody> 
                        <?php foreach ($absences as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['nom_cours']) ?></td>
                                <td><?= $a['date'] ?></td>
                                <td>
                                    <span class="badge <?= $a['statut'] === 'justifie' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $a['statut'] === 'justifie' ? 'Justifié' : 'Absent' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($a['statut'] === 'absent'): ?>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="document.getElementById('justif-<?= $a['presence_id'] ?>').classList.toggle('d-none')">
                                            Justifier
                                        </button>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if ($a['statut'] === 'absent'): ?>
                                <tr id="justif-<?= $a['presence_id'] ?>" class="d-none">
                                    <td colspan="4">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="presence_id" value="<?= $a['presence_id'] ?>">

                                            <div class="form-group">
                                                <textarea name="justification" class="form-control"
                                                    placeholder="Rédigez votre justification" required></textarea>
                                            </div>

                                            <div class="form-group">
                                                <input type="file" name="fichier_justification" class="form-control">
                                            </div>

                                            <button type="submit" class="btn btn-success btn-sm">
                                                Envoyer justification
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
            <div class="table-container">
                <h2> Mes absences justifiées</h2>
                <?php
                $justifiees = array_filter($absences, fn($a) => $a['statut'] === 'justifie');
                ?>
                <?php if (empty($justifiees)): ?>
                    <div class="alert alert-info">Vous n'avez aucune absence justifiée.</div>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cours</th>
                                <th>Date</th>
                                <th>Justification texte</th>
                                <th>Fichier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($justifiees as $j): ?>
                                <tr>
                                    <td><?= htmlspecialchars($j['nom_cours']) ?></td>
                                    <td><?= $j['date'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($j['justification']) ?: '-' ?></td>
                                    <td>
                                        <?php if ($j['fichier_justification']): ?>
                                            <a href="<?= $j['fichier_justification'] ?>" target="_blank">Voir fichier</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const seuil = 4;

    const labels = <?= json_encode(array_column($stats, 'matiere')) ?>;
    const absences = <?= json_encode(array_column($stats, 'total_absences')) ?>;

    const palette = [
        '#0d6efd', '#20c997', '#6f42c1',
        '#fd7e14', '#198754', '#0dcaf0',
        '#6610f2', '#adb5bd'
    ];

    const backgroundColors = absences.map((val, i) => {
        return val >= seuil ? '#dc3545' : palette[i % palette.length];
    });

    const ctx = document.getElementById('absenceChart');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: absences,
                backgroundColor: backgroundColors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) =>
                            `${ctx.label} : ${ctx.raw} absences`
                    }
                }
            }
        }
    });
</script>