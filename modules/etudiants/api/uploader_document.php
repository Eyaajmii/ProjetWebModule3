<?php
/**
 * API - Upload de documents étudiants
 * Partie 3.3 - Téléchargement de documents
 *
 * Fonctionnalités:
 * - Upload de fichiers (PDF, JPG, PNG max 5MB)
 * - Validation stricte des formats et tailles
 * - Gestion des versions de documents
 * - Sécurisation des noms de fichiers
 
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

    // Récupérer l'ID de l'étudiant
    $etudiantId = intval($_POST['etudiant_id'] ?? 0);

    if (!$etudiantId) {
        envoyerJSON(['success' => false, 'message' => 'ID étudiant manquant'], 400);
    }

    // Vérifier les permissions
    if (!verifierPermission($etudiantId)) {
        envoyerJSON(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // Vérifier qu'un fichier a été uploadé
    if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] === UPLOAD_ERR_NO_FILE) {
        envoyerJSON(['success' => false, 'message' => 'Aucun fichier sélectionné'], 400);
    }

    $fichier = $_FILES['fichier'];

    // Valider le fichier
    $erreurs = validerFichier($fichier);

    if (!empty($erreurs)) {
        // Utiliser le premier message d'erreur comme message principal
        $messagePrincipal = $erreurs[0];

        envoyerJSON([
            'success' => false,
            'message' => $messagePrincipal,
            'errors' => $erreurs
        ], 400);
    }

    // Récupérer le type de document
    $typeDocument = $_POST['type_document'] ?? '';

    if (empty($typeDocument) || !array_key_exists($typeDocument, DOCUMENT_TYPES)) {
        envoyerJSON(['success' => false, 'message' => 'Type de document invalide'], 400);
    }

    // Obtenir les informations du fichier
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $typeMime = finfo_file($finfo, $fichier['tmp_name']);
    finfo_close($finfo);

    // Déterminer la version du document
    $stmt = $db->prepare("
        SELECT MAX(version) as max_version
        FROM documents_etudiants
        WHERE etudiant_id = ? AND type_document = ?
    ");
    $stmt->execute([$etudiantId, $typeDocument]);
    $result = $stmt->fetch();
    $version = ($result && $result['max_version']) ? $result['max_version'] + 1 : 1;

    // Créer le répertoire de l'étudiant si nécessaire
    $repertoireEtudiant = creerRepertoireEtudiant($etudiantId);

    if (!$repertoireEtudiant) {
        envoyerJSON([
            'success' => false,
            'message' => 'Impossible de créer le répertoire de destination'
        ], 500);
    }

    // Générer un nom de fichier sécurisé
    $nomFichier = genererNomFichier($fichier['name'], $etudiantId);
    $cheminComplet = $repertoireEtudiant . $nomFichier;
    $cheminRelatif = 'uploads/documents/' . $etudiantId . '/' . $nomFichier;

    // Déplacer le fichier uploadé
    if (!move_uploaded_file($fichier['tmp_name'], $cheminComplet)) {
        envoyerJSON([
            'success' => false,
            'message' => 'Erreur lors du téléchargement du fichier'
        ], 500);
    }

    // Déterminer le statut du document selon le rôle
    $role = $_SESSION['role'] ?? '';
    $utilisateurId = $_SESSION['utilisateur_id'];

    if ($role === 'administrateur') {
        // Si c'est un admin qui upload, le document est directement validé
        $statut = 'valide';
        $dateValidation = date('Y-m-d H:i:s');
        $validePar = $utilisateurId;
    } else {
        // Si c'est un étudiant, en attente de validation
        $statut = 'en_attente';
        $dateValidation = null;
        $validePar = null;
    }

    // Insérer les informations dans la base de données
    $db->beginTransaction();

    try {
        $stmt = $db->prepare("
            INSERT INTO documents_etudiants
            (etudiant_id, version, type_document, nom_fichier, chemin_fichier, type_mime, extension, taille_fichier, statut, date_validation, valide_par)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $etudiantId,
            $version,
            $typeDocument,
            $fichier['name'],
            $cheminRelatif,
            $typeMime,
            '.' . $extension,
            $fichier['size'],
            $statut,
            $dateValidation,
            $validePar
        ]);

        $documentId = $db->lastInsertId();

        // Créer une notification selon le rôle
        if ($role === 'administrateur') {
            // Si admin upload, notifier l'étudiant que son document est validé
            $stmt = $db->prepare("
                SELECT utilisateur_id
                FROM etudiants
                WHERE id = ?
            ");
            $stmt->execute([$etudiantId]);
            $etudiantUser = $stmt->fetch();

            if ($etudiantUser) {
                $typeLabel = DOCUMENT_TYPES[$typeDocument] ?? $typeDocument;
                creerNotification(
                    $etudiantUser['utilisateur_id'],
                    'Document ajouté et validé',
                    "Un administrateur a téléchargé et validé votre document : {$typeLabel}.",
                    'succes',
                    "documents.php?id={$etudiantId}"
                );
            }
        } else {
            // Si étudiant upload, notifier l'administrateur
            $stmt = $db->prepare("
                SELECT u.id
                FROM utilisateurs u
                WHERE u.role = 'administrateur'
                LIMIT 1
            ");
            $stmt->execute();
            $admin = $stmt->fetch();

            if ($admin) {
                // Récupérer le nom de l'étudiant
                $stmt = $db->prepare("
                    SELECT u.prenom, u.nom
                    FROM etudiants e
                    INNER JOIN utilisateurs u ON e.utilisateur_id = u.id
                    WHERE e.id = ?
                ");
                $stmt->execute([$etudiantId]);
                $etudiant = $stmt->fetch();

                $nomComplet = $etudiant['prenom'] . ' ' . $etudiant['nom'];
                $typeLabel = DOCUMENT_TYPES[$typeDocument] ?? $typeDocument;

                creerNotification(
                    $admin['id'],
                    'Nouveau document à valider',
                    "L'étudiant {$nomComplet} a téléchargé un document ({$typeLabel}) en attente de validation.",
                    'information',
                    "documents.php?id={$etudiantId}"
                );
            }
        }

        $db->commit();

        // Message de succès selon le rôle
        $message = ($role === 'administrateur')
            ? 'Document téléchargé et validé avec succès'
            : 'Document téléchargé avec succès. En attente de validation.';

        envoyerJSON([
            'success' => true,
            'message' => $message,
            'data' => [
                'document_id' => $documentId,
                'nom_fichier' => $fichier['name'],
                'type_document' => $typeDocument,
                'version' => $version,
                'taille' => formaterTaille($fichier['size']),
                'statut' => $statut
            ]
        ]);

    } catch (PDOException $e) {
        $db->rollBack();

        // Supprimer le fichier uploadé en cas d'erreur BD
        if (file_exists($cheminComplet)) {
            unlink($cheminComplet);
        }

        throw $e;
    }

} catch (Exception $e) {
    loggerErreur("Erreur upload document", [
        'error' => $e->getMessage(),
        'etudiant_id' => $etudiantId ?? null
    ]);

    envoyerJSON([
        'success' => false,
        'message' => 'Une erreur est survenue lors du téléchargement'
    ], 500);
}
