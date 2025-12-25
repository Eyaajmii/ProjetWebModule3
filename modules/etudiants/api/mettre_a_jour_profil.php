<?php
/**
 * API - Mise à jour du profil étudiant
 * Partie 3.1 - Fiche étudiante complète
 *
 
 */

require_once '../includes/config.php';
require_once '../includes/fonctions_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier la connexion
if (!isset($_SESSION['utilisateur_id'])) {
    envoyerJSON(['success' => false, 'message' => 'Non authentifié'], 401);
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

try {
    $db = getDB();

    // Récupérer et valider les données
    $etudiantId = isset($_POST['etudiant_id']) ? intval($_POST['etudiant_id']) : 0;

    if (!$etudiantId) {
        envoyerJSON(['success' => false, 'message' => 'ID étudiant manquant'], 400);
    }

    // Vérifier les permissions
    if (!verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Récupérer l'utilisateur_id lié à l'étudiant
    $stmt = $db->prepare("SELECT utilisateur_id FROM etudiants WHERE id = ?");
    $stmt->execute([$etudiantId]);
    $result = $stmt->fetch();

    if (!$result) {
        envoyerJSON(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
    }

    $utilisateurId = $result['utilisateur_id'];

    // Préparer les données à mettre à jour
    $donneesUtilisateur = [];
    $donneesEtudiant = [];

    // Données de la table utilisateurs
    if (isset($_POST['prenom'])) {
        $donneesUtilisateur['prenom'] = nettoyerInput($_POST['prenom']);
    }
    if (isset($_POST['nom'])) {
        $donneesUtilisateur['nom'] = nettoyerInput($_POST['nom']);
    }
    if (isset($_POST['telephone'])) {
        $telephone = nettoyerInput($_POST['telephone']);
        if (!empty($telephone) && !validerTelephone($telephone)) {
            envoyerJSON(['success' => false, 'message' => 'Format de téléphone invalide'], 400);
        }
        $donneesUtilisateur['telephone'] = $telephone;
    }
    if (isset($_POST['adresse'])) {
        $donneesUtilisateur['adresse'] = nettoyerInput($_POST['adresse']);
    }

    // Données de la table etudiants
    if (isset($_POST['date_naissance'])) {
        $donneesEtudiant['date_naissance'] = $_POST['date_naissance'];
    }
    if (isset($_POST['lieu_naissance'])) {
        $donneesEtudiant['lieu_naissance'] = nettoyerInput($_POST['lieu_naissance']);
    }
    if (isset($_POST['sexe'])) {
        $donneesEtudiant['sexe'] = $_POST['sexe'];
    }
    if (isset($_POST['nationalite'])) {
        $donneesEtudiant['nationalite'] = nettoyerInput($_POST['nationalite']);
    }
    if (isset($_POST['cin_passeport'])) {
        $donneesEtudiant['cin_passeport'] = nettoyerInput($_POST['cin_passeport']);
    }
    if (isset($_POST['situation_familiale'])) {
        $donneesEtudiant['situation_familiale'] = $_POST['situation_familiale'];
    }
    if (isset($_POST['programme'])) {
        $donneesEtudiant['programme'] = nettoyerInput($_POST['programme']);
    }
    if (isset($_POST['annee_courante'])) {
        $donneesEtudiant['annee_courante'] = intval($_POST['annee_courante']);
    }
    if (isset($_POST['groupe'])) {
        $donneesEtudiant['groupe'] = nettoyerInput($_POST['groupe']);
    }
    if (isset($_POST['date_admission'])) {
        $donneesEtudiant['date_admission'] = $_POST['date_admission'];
    }

    // Seul l'admin peut changer le statut
    if (isset($_POST['statut']) && $_SESSION['role'] === 'administrateur') {
        $donneesEtudiant['statut'] = $_POST['statut'];
    }

    // Démarrer une transaction
    $db->beginTransaction();

    // Mettre à jour la table utilisateurs
    if (!empty($donneesUtilisateur)) {
        $champsUtilisateur = [];
        $valeursUtilisateur = [];

        foreach ($donneesUtilisateur as $champ => $valeur) {
            $champsUtilisateur[] = "$champ = ?";
            $valeursUtilisateur[] = $valeur;
        }

        $valeursUtilisateur[] = $utilisateurId;

        $sqlUtilisateur = "UPDATE utilisateurs SET " . implode(', ', $champsUtilisateur) . " WHERE id = ?";
        $stmtUtilisateur = $db->prepare($sqlUtilisateur);
        $stmtUtilisateur->execute($valeursUtilisateur);
    }

    // Mettre à jour la table etudiants
    if (!empty($donneesEtudiant)) {
        $champsEtudiant = [];
        $valeursEtudiant = [];

        foreach ($donneesEtudiant as $champ => $valeur) {
            $champsEtudiant[] = "$champ = ?";
            $valeursEtudiant[] = $valeur;
        }

        $valeursEtudiant[] = $etudiantId;

        $sqlEtudiant = "UPDATE etudiants SET " . implode(', ', $champsEtudiant) . " WHERE id = ?";
        $stmtEtudiant = $db->prepare($sqlEtudiant);
        $stmtEtudiant->execute($valeursEtudiant);
    }

    // Valider la transaction
    $db->commit();

    // Créer une notification
    creerNotification(
        $utilisateurId,
        'Profil mis à jour',
        'Vos informations personnelles ont été mises à jour avec succès.',
        'succes',
        'profil_etudiant.php?id=' . $etudiantId
    );

    envoyerJSON([
        'success' => true,
        'message' => 'Profil mis à jour avec succès',
        'data' => [
            'etudiant_id' => $etudiantId
        ]
    ]);

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    loggerErreur("Erreur mise à jour profil", [
        'error' => $e->getMessage(),
        'etudiant_id' => $etudiantId ?? null
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du profil'
    ], 500);
}
