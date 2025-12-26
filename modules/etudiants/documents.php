<?php

/**
 * Téléchargement et gestion des documents - Partie 3.3
 * Module Gestion des Étudiants
 * Projet ERP Iteam University
 *
 * Fonctionnalités:
 * - Types de documents : CV, lettre de motivation, photo, copies de diplômes
 * - Validation des formats (PDF, JPG, PNG)
 * - Limite de taille (max 5MB par document)
 * - Statut des documents (en attente, validé, rejeté)
 * - Commentaires de l'administration sur les documents
 *
 
 */

// Configuration et imports
require_once 'includes/config.php';
require_once 'includes/fonctions_helpers.php';

// Vérifier la connexion
verifierConnexion();
include 'includes/header.php';

// Récupérer l'ID de l'étudiant
$etudiantId = isset($_GET['id']) ? intval($_GET['id']) : getEtudiantIdConnecte();

if (!$etudiantId) {
    header('Location: liste_etudiants.php');
    exit();
}

// Vérifier les permissions
$role = $_SESSION['role'] ?? '';
$estAdmin = ($role === 'administrateur');
$peutUploader = $estAdmin || (getEtudiantIdConnecte() === $etudiantId);

if (!verifierPermission($etudiantId)) {
    die("<div class='alert alert-danger'>Accès refusé. Vous ne pouvez consulter que vos propres documents.</div>");
}

// Récupérer les informations de l'étudiant
$db = getDB();
$stmt = $db->prepare("
    SELECT e.*, u.prenom, u.nom
    FROM etudiants e
    INNER JOIN utilisateurs u ON e.utilisateur_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$etudiantId]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    die("<div class='alert alert-danger'>Étudiant non trouvé.</div>");
}

// Récupérer tous les documents de l'étudiant
$stmt = $db->prepare("
    SELECT
        d.*,
        u.prenom AS valideur_prenom,
        u.nom AS valideur_nom
    FROM documents_etudiants d
    LEFT JOIN utilisateurs u ON d.valide_par = u.id
    WHERE d.etudiant_id = ?
    ORDER BY d.type_document, d.version DESC, d.date_upload DESC
");
$stmt->execute([$etudiantId]);
$documents = $stmt->fetchAll();

// Grouper les documents par type
$documentsParType = [];
foreach ($documents as $doc) {
    $type = $doc['type_document'];
    if (!isset($documentsParType[$type])) {
        $documentsParType[$type] = [];
    }
    $documentsParType[$type][] = $doc;
}

// Messages de succès/erreur
$message = '';
$messageType = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$pageTitle = "Documents - " . echapper($etudiant['prenom'] . ' ' . $etudiant['nom']);
?>

<div class="page-header">
    <div>
        <h2>Gestion des Documents</h2>
        <p class="page-subtitle">
            Étudiant: <?php echo echapper($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>
            - N° <?php echo echapper($etudiant['numero_etudiant']); ?>
        </p>
    </div>
    <a href="profil_etudiant.php?id=<?php echo $etudiantId; ?>" class="btn-secondary">
        ← Retour au profil
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
        <?php echo echapper($message); ?>
        <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- Informations importantes -->
<div class="alert alert-info">
    <strong>Formats acceptés:</strong> PDF, JPG, PNG<br>
    <strong>Taille maximale:</strong> 5 MB par document<br>
    <strong>Types de documents requis:</strong> CV, Lettre de motivation, Photo d'identité, Copies de diplômes
</div>

<!-- Zone d'upload -->
<?php if ($peutUploader): ?>
    <div class="card upload-card">
        <div class="card-header">
            <h3 class="card-title">Télécharger un nouveau document</h3>
        </div>
        <div class="card-body">
            <form id="formUploadDocument" enctype="multipart/form-data">
                <input type="hidden" name="etudiant_id" value="<?php echo $etudiantId; ?>">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Type de document *</label>
                        <select name="type_document" id="typeDocument" class="form-control" required>
                            <option value="">-- Sélectionner un type --</option>
                            <?php foreach (DOCUMENT_TYPES as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo echapper($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fichier *</label>
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" name="fichier" id="fichierInput" accept=".pdf,.jpg,.jpeg,.png" required>
                            <label for="fichierInput" class="upload-label">
                                <span class="upload-icon">📁</span>
                                <span class="upload-text">Cliquez ou glissez un fichier ici</span>
                                <span class="upload-hint">PDF, JPG, PNG - Max 5 MB</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Prévisualisation du fichier -->
                <div id="filePreview" class="file-preview" style="display: none;">
                    <div class="file-preview-icon" id="previewIcon">📄</div>
                    <div class="file-preview-info">
                        <div class="file-preview-name" id="previewName"></div>
                        <div class="file-preview-size" id="previewSize"></div>
                    </div>
                    <button type="button" class="btn-danger btn-sm" id="btnRemoveFile">Supprimer</button>
                </div>

                <!-- Barre de progression -->
                <div id="uploadProgress" class="upload-progress" style="display: none;">
                    <div class="upload-progress-bar" id="progressBar"></div>
                </div>

                <div id="uploadMessage" class="upload-message"></div>

                <div class="card-footer">
                    <button type="submit" class="btn-primary" id="btnUpload">
                        Télécharger le document
                    </button>
                    <button type="reset" class="btn-secondary">
                        Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Liste des documents par type -->
<div class="documents-section">
    <h3>Documents téléchargés</h3>

    <?php if (empty($documents)): ?>
        <div class="card">
            <div class="card-body">
                <p class="text-muted text-center">Aucun document téléchargé pour le moment.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach (DOCUMENT_TYPES as $typeKey => $typeLabel): ?>
            <?php if (isset($documentsParType[$typeKey])): ?>
                <div class="card document-type-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php echo echapper($typeLabel); ?>
                            <span class="badge badge-info"><?php echo count($documentsParType[$typeKey]); ?> document(s)</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Nom du fichier</th>
                                        <th>Taille</th>
                                        <th>Date d'envoi</th>
                                        <th>Statut</th>
                                        <?php if ($estAdmin): ?>
                                            <th>Validé par</th>
                                        <?php endif; ?>
                                        <th>Commentaires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documentsParType[$typeKey] as $doc): ?>
                                        <tr class="document-row document-<?php echo $doc['statut']; ?>">
                                            <td>
                                                <span class="version-badge">v<?php echo $doc['version']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo echapper($doc['nom_fichier']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo echapper($doc['extension']); ?></small>
                                            </td>
                                            <td><?php echo formaterTaille($doc['taille_fichier']); ?></td>
                                            <td><?php echo formaterDate($doc['date_upload'], 'd/m/Y H:i'); ?></td>
                                            <td>
                                                <?php echo getBadgeStatut($doc['statut'], 'document'); ?>
                                                <?php if ($doc['date_validation']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Le <?php echo formaterDate($doc['date_validation'], 'd/m/Y'); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($estAdmin): ?>
                                                <td>
                                                    <?php if ($doc['valideur_prenom']): ?>
                                                        <?php echo echapper($doc['valideur_prenom'] . ' ' . $doc['valideur_nom']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if ($doc['notes_administration']): ?>
                                                    <div class="comment-bubble">
                                                        <?php echo echapper($doc['notes_administration']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="api/telecharger_document.php?id=<?php echo $doc['id']; ?>"
                                                        class="action-btn view"
                                                        title="Télécharger"
                                                        target="_blank">
                                                        👁️
                                                    </a>
                                                    <?php if ($peutUploader && $doc['statut'] !== 'valide'): ?>
                                                        <button type="button"
                                                            class="action-btn delete"
                                                            onclick="supprimerDocument(<?php echo $doc['id']; ?>)"
                                                            title="Supprimer">
                                                            🗑️
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($estAdmin && $doc['statut'] === 'en_attente'): ?>
                                                        <button type="button"
                                                            class="action-btn edit"
                                                            onclick="ouvrirModalValidation(<?php echo $doc['id']; ?>)"
                                                            title="Valider/Rejeter">
                                                            ✔️
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Statistiques -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Récapitulatif</h3>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <?php
            $stats = [
                'total' => count($documents),
                'en_attente' => 0,
                'valide' => 0,
                'rejete' => 0
            ];

            foreach ($documents as $doc) {
                $stats[$doc['statut']]++;
            }
            ?>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total documents</div>
            </div>
            <div class="stat-item stat-success">
                <div class="stat-value"><?php echo $stats['valide']; ?></div>
                <div class="stat-label">Validés</div>
            </div>
            <div class="stat-item stat-warning">
                <div class="stat-value"><?php echo $stats['en_attente']; ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-item stat-danger">
                <div class="stat-value"><?php echo $stats['rejete']; ?></div>
                <div class="stat-label">Rejetés</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de validation (pour admin) -->
<?php if ($estAdmin): ?>
    <div id="modalValidation" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Validation du document</h3>
                <button type="button" class="close" onclick="fermerModalValidation()">&times;</button>
            </div>
            <form id="formValidation">
                <div class="modal-body">
                    <input type="hidden" id="documentIdValidation" name="document_id">

                    <div class="form-group">
                        <label class="form-label">Décision *</label>
                        <select name="statut" id="statutValidation" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="valide">Valider le document</option>
                            <option value="rejete">Rejeter le document</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Commentaires</label>
                        <textarea name="commentaires" id="commentairesValidation" class="form-control" rows="4"
                            placeholder="Ajoutez des commentaires (obligatoire en cas de rejet)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                    <button type="button" class="btn-secondary" onclick="fermerModalValidation()">Annuler</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- 
    <footer class="university-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>Iteam University</h4>
                <p>Plateforme ERP universitaire</p>
                <p>&copy; 2024 Tous droits réservés</p>
            </div>
        </div>
    </footer>
 -->
<script src="js/upload_manager.js"></script>
<script src="js/validation_documents.js"></script>