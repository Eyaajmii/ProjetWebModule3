<?php
/**
 * API - Gestion de l'historique scolaire
 * Partie 3.1 - Fiche étudiante (historique des établissements précédents)
 *
 * Actions: ajouter, modifier, supprimer, obtenir
 *
 
 */

require_once '../includes/config.php';
require_once '../includes/fonctions_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier la connexion
if (!isset($_SESSION['utilisateur_id'])) {
    envoyerJSON(['success' => false, 'message' => 'Non authentifié'], 401);
}

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'ajouter':
            ajouterHistorique();
            break;

        case 'modifier':
            modifierHistorique();
            break;

        case 'supprimer':
            supprimerHistorique();
            break;

        case 'obtenir':
            obtenirHistorique();
            break;

        default:
            envoyerJSON(['success' => false, 'message' => 'Action non spécifiée'], 400);
    }
} catch (Exception $e) {
    loggerErreur("Erreur gestion historique scolaire", [
        'action' => $action,
        'error' => $e->getMessage()
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Une erreur est survenue'
    ], 500);
}

/**
 * Ajouter un établissement à l'historique
 */
function ajouterHistorique() {
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }

    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Validation des champs
    $etablissement = nettoyerInput($_POST['etablissement'] ?? '');
    $typeEtablissement = $_POST['type_etablissement'] ?? '';
    $diplomeObtenu = nettoyerInput($_POST['diplome_obtenu'] ?? '');
    $anneeObtention = intval($_POST['annee_obtention'] ?? 0);
    $mention = nettoyerInput($_POST['mention'] ?? '');
    $pays = nettoyerInput($_POST['pays'] ?? 'Tunisie');
    $ville = nettoyerInput($_POST['ville'] ?? '');
    $description = nettoyerInput($_POST['description'] ?? '');

    if (empty($etablissement) || empty($typeEtablissement)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Établissement et type sont requis'
        ], 400);
    }

    // Insertion
    $stmt = $db->prepare("
        INSERT INTO historique_scolaire
        (etudiant_id, etablissement, type_etablissement, diplome_obtenu, annee_obtention, mention, pays, ville, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $etudiantId,
        $etablissement,
        $typeEtablissement,
        $diplomeObtenu,
        $anneeObtention > 0 ? $anneeObtention : null,
        $mention,
        $pays,
        $ville,
        $description
    ]);

    envoyerJSON([
        'success' => true,
        'message' => 'Établissement ajouté à l\'historique',
        'data' => ['id' => $db->lastInsertId()]
    ]);
}

/**
 * Modifier un établissement de l'historique
 */
function modifierHistorique() {
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }

    $id = intval($_POST['id'] ?? 0);
    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$id || !$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Vérifier que l'historique appartient bien à cet étudiant
    $stmt = $db->prepare("SELECT id FROM historique_scolaire WHERE id = ? AND etudiant_id = ?");
    $stmt->execute([$id, $etudiantId]);

    if (!$stmt->fetch()) {
        envoyerJSON(['success' => false, 'message' => 'Historique non trouvé'], 404);
    }

    // Préparer les données
    $donneesMAJ = [];
    $valeursMAJ = [];

    $champsAutorises = [
        'etablissement', 'type_etablissement', 'diplome_obtenu',
        'annee_obtention', 'mention', 'pays', 'ville', 'description'
    ];

    foreach ($champsAutorises as $champ) {
        if (isset($_POST[$champ])) {
            $donneesMAJ[] = "$champ = ?";
            $valeur = in_array($champ, ['annee_obtention']) ? intval($_POST[$champ]) : nettoyerInput($_POST[$champ]);
            $valeursMAJ[] = $valeur;
        }
    }

    if (empty($donneesMAJ)) {
        envoyerJSON(['success' => false, 'message' => 'Aucune donnée à mettre à jour'], 400);
    }

    $valeursMAJ[] = $id;

    $sql = "UPDATE historique_scolaire SET " . implode(', ', $donneesMAJ) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($valeursMAJ);

    envoyerJSON([
        'success' => true,
        'message' => 'Historique mis à jour'
    ]);
}

/**
 * Supprimer un établissement de l'historique
 */
function supprimerHistorique() {
    global $db;

    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

    if (!$id) {
        envoyerJSON(['success' => false, 'message' => 'ID manquant'], 400);
    }

    // Récupérer l'étudiant_id pour vérifier les permissions
    $stmt = $db->prepare("SELECT etudiant_id FROM historique_scolaire WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if (!$result) {
        envoyerJSON(['success' => false, 'message' => 'Historique non trouvé'], 404);
    }

    if (!verifierPermission($result['etudiant_id'])) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Suppression
    $stmt = $db->prepare("DELETE FROM historique_scolaire WHERE id = ?");
    $stmt->execute([$id]);

    envoyerJSON([
        'success' => true,
        'message' => 'Établissement supprimé de l\'historique'
    ]);
}

/**
 * Obtenir l'historique scolaire d'un étudiant
 */
function obtenirHistorique() {
    global $db;

    $etudiantId = intval($_GET['etudiant_id'] ?? 0);

    if (!$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    $stmt = $db->prepare("
        SELECT * FROM historique_scolaire
        WHERE etudiant_id = ?
        ORDER BY annee_obtention DESC, id DESC
    ");
    $stmt->execute([$etudiantId]);
    $historique = $stmt->fetchAll();

    envoyerJSON([
        'success' => true,
        'data' => $historique
    ]);
}
