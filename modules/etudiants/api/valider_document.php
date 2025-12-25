<?php
/**
 * API - Validation/Rejet de documents par l'administrateur
 * Partie 3.3 - Téléchargement de documents

 */

require_once '../includes/config.php';
require_once '../includes/fonctions_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier la connexion
if (!isset($_SESSION['utilisateur_id'])) {
    envoyerJSON(['success' => false, 'message' => 'Non authentifié'], 401);
}

// Vérifier que l'utilisateur est administrateur
if ($_SESSION['role'] !== 'administrateur') {
    envoyerJSON(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    envoyerJSON(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

try {
    $db = getDB();

    // Récupérer les paramètres
    $documentId = intval($_POST['document_id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    $commentaires = nettoyerInput($_POST['commentaires'] ?? '');

    // Validation
    if (!$documentId) {
        envoyerJSON(['success' => false, 'message' => 'ID document manquant'], 400);
    }

    if (!in_array($statut, ['valide', 'rejete'])) {
        envoyerJSON(['success' => false, 'message' => 'Statut invalide'], 400);
    }

    // Si rejet, commentaire obligatoire
    if ($statut === 'rejete' && empty($commentaires)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Un commentaire est obligatoire pour rejeter un document'
        ], 400);
    }

    // Vérifier que le document existe et est en attente
    $stmt = $db->prepare("
        SELECT d.*, e.utilisateur_id
        FROM documents_etudiants d
        INNER JOIN etudiants e ON d.etudiant_id = e.id
        WHERE d.id = ?
    ");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        envoyerJSON(['success' => false, 'message' => 'Document non trouvé'], 404);
    }

    if ($document['statut'] !== 'en_attente') {
        envoyerJSON([
            'success' => false,
            'message' => 'Ce document a déjà été traité'
        ], 400);
    }

    // Mettre à jour le document
    $stmt = $db->prepare("
        UPDATE documents_etudiants
        SET statut = ?,
            notes_administration = ?,
            date_validation = NOW(),
            valide_par = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $statut,
        $commentaires,
        $_SESSION['utilisateur_id'],
        $documentId
    ]);

    // Créer une notification pour l'étudiant
    $typeLabel = DOCUMENT_TYPES[$document['type_document']] ?? 'Document';
    $messageNotif = $statut === 'valide'
        ? "Votre {$typeLabel} a été validé par l'administration."
        : "Votre {$typeLabel} a été rejeté. Raison : {$commentaires}";

    creerNotification(
        $document['utilisateur_id'],
        'Document ' . ($statut === 'valide' ? 'validé' : 'rejeté'),
        $messageNotif,
        $statut === 'valide' ? 'succes' : 'avertissement',
        'documents.php'
    );

    envoyerJSON([
        'success' => true,
        'message' => 'Document ' . ($statut === 'valide' ? 'validé' : 'rejeté') . ' avec succès',
        'data' => [
            'document_id' => $documentId,
            'statut' => $statut
        ]
    ]);

} catch (Exception $e) {
    loggerErreur("Erreur validation document", [
        'error' => $e->getMessage(),
        'document_id' => $documentId ?? null
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Une erreur est survenue'
    ], 500);
}
