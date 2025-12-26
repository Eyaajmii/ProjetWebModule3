<?php
require_once __DIR__ .  '/config.php';
require_once __DIR__ .  '/fonctions_helpers.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTeam University</title>
    <link rel="stylesheet" href="./css/main.css">
    <link rel="stylesheet" href="./css/design_system.css">
    <link rel="stylesheet" href="./css/etudiants.css">
    <link rel="stylesheet" href="./css/documents.css">
</head>

<body>
    <div class="dashboard-container">
        <header class="university-header">
            <a href="dashboard.php" class="logo-link">
                <img src="./css/iteam-logo.png" alt="Iteam University" class="logo">
                <span class="university-name">iTeam University</span>
            </a>
            <div class="user-section">
                <span><?php echo echapper($_SESSION['prenom'] ?? 'Utilisateur'); ?></span>
                <a href="../authentification/deconnexion.php">Déconnexion</a>
            </div>
        </header>
        <aside class="sidebar">
            <div class="nav-vertical">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'etudiant') : ?>
                    <a href="index.php" class="nav-vertical-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><!--Index.php est dans le module 2-->
                        <span>🏠</span> Tableau de bord
                    </a>
                    <a href="profil_etudiant.php" class="nav-vertical-item <?php echo ($current_page == 'profil_etudiant.php') ? 'active' : ''; ?>">
                        <span>👤</span> Fiche Étudiant
                    </a>
                    <a href="rapports/rapport_presence.php" class="nav-vertical-item <?php echo ($current_page == 'rapports/rapport_presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Attestation
                    </a>
                    <a href="historique_academique.php" class="nav-vertical-item <?php echo ($current_page == 'historique_academique.php') ? 'active' : ''; ?>">
                        <span>📚</span> Historique académique
                    </a>
                    <a href="documents.php" class="nav-vertical-item <?php echo ($current_page == 'documents.php') ? 'active' : ''; ?>">
                        <span>📄</span> Gérer les documents
                    </a>
                    <a href="presence.php" class="nav-vertical-item <?php echo ($current_page == 'presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Assuidité
                    </a>
                    <a href="#" onclick="window.print()" class="nav-vertical-item">
                        <span>🖨️</span> Imprimer la fiche
                    </a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'professeur') : ?>
                    <a href="dashboard.php" class="nav-vertical-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <span>🏠</span> Tableau de bord
                    </a>
                    <a href="presence.php" class="nav-vertical-item <?php echo ($current_page == 'presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Faire les appels
                    </a>
                <?php else : ?>
                    <a href="dashboard.php" class="nav-vertical-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <span>🏠</span> Tableau de bord
                    </a>
                    <a href="presence.php" class="nav-vertical-item <?php echo ($current_page == 'presence.php') ? 'active' : ''; ?>">
                        <span>📄</span> Assuidité
                    </a>
                <?php endif; ?>
            </div>
        </aside>
        <main class="main-content">
            <div class="container">