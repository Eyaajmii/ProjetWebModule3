<?php
/**
 * API - Téléchargement/Visualisation de documents
 * Partie 3.3 - Téléchargement de documents
 *
 
 */

require_once '../includes/config.php';
require_once '../includes/fonctions_helpers.php';

// Vérifier la connexion
verifierConnexion();

$documentId = intval($_GET['id'] ?? 0);

if (!$documentId) {
    die("ID document manquant");
}

try {
    $db = getDB();

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
        die("Document non trouvé");
    }

    // Vérifier les permissions
    if (!verifierPermission($document['etudiant_id'])) {
        die("Accès refusé");
    }

    // Construire le chemin complet du fichier
    $cheminFichier = dirname(__DIR__) . '/' . $document['chemin_fichier'];

    if (!file_exists($cheminFichier)) {
        die("Fichier non trouvé sur le serveur");
    }

    // Déterminer le type MIME
    $typeMime = $document['type_mime'] ?: 'application/octet-stream';

    // Headers pour le téléchargement/affichage
    header('Content-Type: ' . $typeMime);
    header('Content-Length: ' . filesize($cheminFichier));

    // Si PDF ou image, afficher dans le navigateur, sinon forcer le téléchargement
    if (in_array($typeMime, ['application/pdf', 'image/jpeg', 'image/png'])) {
        header('Content-Disposition: inline; filename="' . $document['nom_fichier'] . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $document['nom_fichier'] . '"');
    }

    // Désactiver la mise en cache
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');

    // Envoyer le fichier
    readfile($cheminFichier);
    exit();

} catch (Exception $e) {
    loggerErreur("Erreur téléchargement document", [
        'error' => $e->getMessage(),
        'document_id' => $documentId
    ]);

    die("Erreur lors du téléchargement du fichier");
}
