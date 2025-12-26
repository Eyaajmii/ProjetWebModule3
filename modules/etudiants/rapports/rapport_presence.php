<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions_helpers.php';
require_once __DIR__ . '/../dompdf/vendor/autoload.php';
use Dompdf\Dompdf;

verifierConnexion();
$current_page = basename($_SERVER['PHP_SELF']);
if ($_SESSION['role'] !== 'etudiant') {
    die("Accès refusé");
}
function estEligibleAttestation($etudiant_id) {
    $db = getDB();
    $sql = "SELECT annee_courante FROM etudiants WHERE id = ? AND statut = 'actif'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$etudiant_id]);
    return $stmt->fetch();
}

function getDonneesAttestation($etudiant_id) {
    $db = getDB();
    $sql = "
        SELECT 
            e.numero_etudiant,
            u.prenom,
            u.nom,
            e.programme,
            e.annee_courante
        FROM etudiants e
        JOIN utilisateurs u ON u.id = e.utilisateur_id
        WHERE e.id = ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$etudiant_id]);
    return $stmt->fetch();
}

function genererNumeroAttestation() {
    return 'ATT-' . date('Y') . '-' . str_pad(rand(1,999999), 6, '0', STR_PAD_LEFT);
}

$etudiant_id = getEtudiantIdConnecte();
$eligibilite = estEligibleAttestation($etudiant_id);

if (!$eligibilite) {
    die("Vous n'êtes pas inscrit pour l'année en cours.");
}
$data = getDonneesAttestation($etudiant_id);
$numero = genererNumeroAttestation();
$dateHeure = date('d/m/Y à H:i');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telecharger'])) {

    $html = "
    <html lang='fr'>
    <meta charset='UTF-8'>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { text-align:center; }
    </style>

    <h1>ATTESTATION DE PRÉSENCE</h1>

    <p><strong>Numéro :</strong> $numero</p>

    <p>
        Nous soussignés, <strong>ITEAM University</strong>, attestons que :
    </p>

    <p>
        <strong>Nom :</strong> {$data['prenom']} {$data['nom']}<br>
        <strong>Numéro étudiant :</strong> {$data['numero_etudiant']}<br>
        <strong>Programme :</strong> {$data['programme']}<br>
        <strong>Année :</strong> {$data['annee_courante']}
    </p>

    <p>
        est régulièrement inscrit(e) et assiste aux cours durant l'année universitaire en cours.
    </p>

    <p>Fait à Tunis, le $dateHeure</p>

    <p><strong>Service de la scolarité</strong></p>
    </html>
    ";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("attestation_presence_$numero.pdf", ["Attachment" => true]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTeam University</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/design_system.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="university-header">
            <a href="dashboard.php" class="logo-link">
                <img src="../css/iteam-logo.png" alt="Iteam University" class="logo">
                <span class="university-name">iTeam University</span>
            </a>
            <div class="user-section">
                <span><?php echo echapper($_SESSION['prenom'] ?? 'Utilisateur'); ?></span>
                <a href="../authentification/deconnexion.php">Déconnexion</a>
            </div>
        </header>
        <aside class="sidebar">
            <div class="nav-vertical">
                
                    <a href="index.php" class="nav-vertical-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><!--Index.php est dans le module 2-->
                        <span>🏠</span> Tableau de bord
                    </a>
                    <a href="../profil_etudiant.php" class="nav-vertical-item <?php echo ($current_page == '../profil_etudiant.php') ? 'active' : ''; ?>">
                        <span>👤</span> Fiche Étudiant
                    </a>
                    <a href="rapport_presence.php" class="nav-vertical-item <?php echo ($current_page == 'rapport_presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Attestation
                    </a>
                    <a href="../historique_academique.php" class="nav-vertical-item <?php echo ($current_page == '../historique_academique.php') ? 'active' : ''; ?>">
                        <span>📚</span> Historique académique
                    </a>
                    <a href="../documents.php" class="nav-vertical-item <?php echo ($current_page == '../documents.php') ? 'active' : ''; ?>">
                        <span>📄</span> Gérer les documents
                    </a>
                    <a href="../presence.php" class="nav-vertical-item <?php echo ($current_page == '../presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Assuidité
                    </a>
                    <a href="#" onclick="window.print()" class="nav-vertical-item">
                        <span>🖨️</span> Imprimer la fiche
                    </a>
            </div>
        </aside>
        <main class="main-content">
<div class="container">

    <h2> Attestation de présence</h2>

    <p class="alert alert-success">
        Vous êtes bien inscrit pour l’année universitaire <strong><?= $data['annee_courante'] ?></strong>
    </p>

    <div class="card">
        <p><strong>Nom :</strong> <?= $data['prenom'] ?> <?= $data['nom'] ?></p>
        <p><strong>Numéro étudiant :</strong> <?= $data['numero_etudiant'] ?></p>
        <p><strong>Programme :</strong> <?= $data['programme'] ?></p>
        <p><strong>Année universitaire :</strong> <?= $data['annee_courante'] ?></p>
    </div>

    <form method="POST">
        <div class="btn">
        <button type="submit" name="telecharger" class="btn-primary">
            Télécharger l’attestation PDF
        </button>
        </div>
    </form>

</div>
