<?php
/**
 * API - Suppression de documents
 * Partie 3.3 - Téléchargement de documents
 *
 
 */

require_once '../includes/config.php';
require_once '../includes/fonctions_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier la connexion
if (!isset($_SESSION['utilisateur_id'])) {
    envoyerJSON(['success' => false, 'message' => 'Non authentifié'], 401);
}

try {
    $db = getDB();

    $documentId = intval($_GET['id'] ?? $_POST['id'] ?? 0);

    if (!$documentId) {
        envoyerJSON(['success' => false, 'message' => 'ID document manquant'], 400);
    }

    // Récupérer les informations du document
    $stmt = $db->prepare("
        SELECT d.*, e.id as etudiant_id
        FROM documents_etudiants d
        INNER JOIN etudiants e ON d.etudiant_id = e.id
        WHERE d.id = ?
    ");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        envoyerJSON(['success' => false, 'message' => 'Document non trouvé'], 404);
    }

    // Vérifier les permissions
    if (!verifierPermission($document['etudiant_id'])) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Les étudiants ne peuvent supprimer que les documents non validés
    if ($_SESSION['role'] !== 'administrateur' && $document['statut'] === 'valide') {
        envoyerJSON([
            'success' => false,
            'message' => 'Vous ne pouvez pas supprimer un document validé'
        ], 403);
    }

    $db->beginTransaction();

    try {
        // Supprimer le fichier physique
        $cheminFichier = dirname(__DIR__) . '/' . $document['chemin_fichier'];

        if (file_exists($cheminFichier)) {
            if (!unlink($cheminFichier)) {
                throw new Exception("Impossible de supprimer le fichier");
            }
        }

        // Supprimer l'enregistrement de la base de données
        $stmt = $db->prepare("DELETE FROM documents_etudiants WHERE id = ?");
        $stmt->execute([$documentId]);

        $db->commit();

        envoyerJSON([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    loggerErreur("Erreur suppression document", [
        'error' => $e->getMessage(),
        'document_id' => $documentId ?? null
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la suppression'
    ], 500);
}
