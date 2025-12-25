<?php
/**
 * API - Gestion des contacts d'urgence
 * Partie 3.1 - Fiche étudiante (contacts d'urgence avec relations)
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
            ajouterContact();
            break;

        case 'modifier':
            modifierContact();
            break;

        case 'supprimer':
            supprimerContact();
            break;

        case 'obtenir':
            obtenirContacts();
            break;

        case 'definir_principal':
            definirContactPrincipal();
            break;

        default:
            envoyerJSON(['success' => false, 'message' => 'Action non spécifiée'], 400);
    }
} catch (Exception $e) {
    loggerErreur("Erreur gestion contacts d'urgence", [
        'action' => $action,
        'error' => $e->getMessage()
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Une erreur est survenue'
    ], 500);
}

/**
 * Ajouter un contact d'urgence
 */
function ajouterContact() {
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }

    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Validation des champs obligatoires
    $nomComplet = nettoyerInput($_POST['nom_complet'] ?? '');
    $relation = $_POST['relation'] ?? '';
    $telephonePrincipal = nettoyerInput($_POST['telephone_principal'] ?? '');

    if (empty($nomComplet) || empty($relation) || empty($telephonePrincipal)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Nom, relation et téléphone principal sont requis'
        ], 400);
    }

    // Valider le téléphone
    if (!validerTelephone($telephonePrincipal)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Format de téléphone invalide'
        ], 400);
    }

    // Champs optionnels
    $telephoneSecondaire = nettoyerInput($_POST['telephone_secondaire'] ?? '');
    $email = nettoyerInput($_POST['email'] ?? '');
    $adresse = nettoyerInput($_POST['adresse'] ?? '');
    $estPrincipal = isset($_POST['est_contact_principal']) && $_POST['est_contact_principal'] === '1';

    // Valider l'email si fourni
    if (!empty($email) && !validerEmail($email)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Format d\'email invalide'
        ], 400);
    }

    $db->beginTransaction();

    try {
        // Si ce contact est défini comme principal, retirer ce statut des autres
        if ($estPrincipal) {
            $stmt = $db->prepare("
                UPDATE contacts_urgence
                SET est_contact_principal = FALSE
                WHERE etudiant_id = ?
            ");
            $stmt->execute([$etudiantId]);
        }

        // Insertion du nouveau contact
        $stmt = $db->prepare("
            INSERT INTO contacts_urgence
            (etudiant_id, nom_complet, relation, telephone_principal, telephone_secondaire, email, adresse, est_contact_principal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $etudiantId,
            $nomComplet,
            $relation,
            $telephonePrincipal,
            $telephoneSecondaire ?: null,
            $email ?: null,
            $adresse ?: null,
            $estPrincipal
        ]);

        $db->commit();

        envoyerJSON([
            'success' => true,
            'message' => 'Contact d\'urgence ajouté',
            'data' => ['id' => $db->lastInsertId()]
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Modifier un contact d'urgence
 */
function modifierContact() {
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }

    $id = intval($_POST['id'] ?? 0);
    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$id || !$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Vérifier que le contact appartient bien à cet étudiant
    $stmt = $db->prepare("SELECT id FROM contacts_urgence WHERE id = ? AND etudiant_id = ?");
    $stmt->execute([$id, $etudiantId]);

    if (!$stmt->fetch()) {
        envoyerJSON(['success' => false, 'message' => 'Contact non trouvé'], 404);
    }

    $db->beginTransaction();

    try {
        // Gérer le statut principal
        if (isset($_POST['est_contact_principal']) && $_POST['est_contact_principal'] === '1') {
            // Retirer le statut principal des autres contacts
            $stmt = $db->prepare("
                UPDATE contacts_urgence
                SET est_contact_principal = FALSE
                WHERE etudiant_id = ? AND id != ?
            ");
            $stmt->execute([$etudiantId, $id]);
        }

        // Préparer les données de mise à jour
        $donneesMAJ = [];
        $valeursMAJ = [];

        $champsAutorises = [
            'nom_complet', 'relation', 'telephone_principal',
            'telephone_secondaire', 'email', 'adresse', 'est_contact_principal'
        ];

        foreach ($champsAutorises as $champ) {
            if (isset($_POST[$champ])) {
                $donneesMAJ[] = "$champ = ?";

                if ($champ === 'est_contact_principal') {
                    $valeursMAJ[] = $_POST[$champ] === '1' ? 1 : 0;
                } else {
                    $valeur = nettoyerInput($_POST[$champ]);
                    $valeursMAJ[] = $valeur ?: null;
                }
            }
        }

        if (empty($donneesMAJ)) {
            envoyerJSON(['success' => false, 'message' => 'Aucune donnée à mettre à jour'], 400);
        }

        $valeursMAJ[] = $id;

        $sql = "UPDATE contacts_urgence SET " . implode(', ', $donneesMAJ) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($valeursMAJ);

        $db->commit();

        envoyerJSON([
            'success' => true,
            'message' => 'Contact d\'urgence mis à jour'
        ]);
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Supprimer un contact d'urgence
 */
function supprimerContact() {
    global $db;

    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

    if (!$id) {
        envoyerJSON(['success' => false, 'message' => 'ID manquant'], 400);
    }

    // Récupérer l'étudiant_id pour vérifier les permissions
    $stmt = $db->prepare("SELECT etudiant_id FROM contacts_urgence WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if (!$result) {
        envoyerJSON(['success' => false, 'message' => 'Contact non trouvé'], 404);
    }

    if (!verifierPermission($result['etudiant_id'])) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Suppression
    $stmt = $db->prepare("DELETE FROM contacts_urgence WHERE id = ?");
    $stmt->execute([$id]);

    envoyerJSON([
        'success' => true,
        'message' => 'Contact d\'urgence supprimé'
    ]);
}

/**
 * Obtenir les contacts d'urgence d'un étudiant
 */
function obtenirContacts() {
    global $db;

    $etudiantId = intval($_GET['etudiant_id'] ?? 0);

    if (!$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    $stmt = $db->prepare("
        SELECT * FROM contacts_urgence
        WHERE etudiant_id = ?
        ORDER BY est_contact_principal DESC, id ASC
    ");
    $stmt->execute([$etudiantId]);
    $contacts = $stmt->fetchAll();

    envoyerJSON([
        'success' => true,
        'data' => $contacts
    ]);
}

/**
 * Définir un contact comme principal
 */
function definirContactPrincipal() {
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
    }

    $id = intval($_POST['id'] ?? 0);
    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$id || !$etudiantId || !verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    $db->beginTransaction();

    try {
        // Retirer le statut principal de tous les contacts
        $stmt = $db->prepare("
            UPDATE contacts_urgence
            SET est_contact_principal = FALSE
            WHERE etudiant_id = ?
        ");
        $stmt->execute([$etudiantId]);

        // Définir le nouveau contact principal
        $stmt = $db->prepare("
            UPDATE contacts_urgence
            SET est_contact_principal = TRUE
            WHERE id = ? AND etudiant_id = ?
        ");
        $stmt->execute([$id, $etudiantId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Contact non trouvé');
        }

        $db->commit();

        envoyerJSON([
            'success' => true,
            'message' => 'Contact principal défini'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
