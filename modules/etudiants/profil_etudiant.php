<?php
/**
 * Fiche étudiante complète - Partie 3.1
 * Module Gestion des Étudiants
 * Projet ERP Iteam University
 *
 * Fonctionnalités:
 * - Informations personnelles (identité, coordonnées, situation familiale)
 * - Informations académiques (filière, année, groupe)
 * - Historique scolaire (établissements précédents, diplômes)
 * - Contacts d'urgence avec relations
 * - Statut administratif (inscrit, démissionné, diplômé)

 */

// Configuration et imports
require_once 'includes/config.php';
require_once 'includes/fonctions_helpers.php';

// Vérifier la connexion
verifierConnexion();

// Récupérer l'ID de l'étudiant
$etudiantId = isset($_GET['id']) ? intval($_GET['id']) : getEtudiantIdConnecte();

if (!$etudiantId) {
    header('Location: liste_etudiants.php');
    exit();
}

// Vérifier les permissions
$role = $_SESSION['role'] ?? '';
$peutModifier = ($role === 'administrateur') || (getEtudiantIdConnecte() === $etudiantId);

if (!verifierPermission($etudiantId)) {
    die("<div class='alert alert-danger'>Accès refusé. Vous ne pouvez consulter que votre propre profil.</div>");
}

// Récupérer les données de l'étudiant
$db = getDB();

// Informations principales
$stmt = $db->prepare("
    SELECT
        e.*,
        u.email,
        u.prenom,
        u.nom,
        u.photo_profil,
        u.telephone,
        u.adresse AS adresse_utilisateur,
        u.derniere_connexion
    FROM etudiants e
    INNER JOIN utilisateurs u ON e.utilisateur_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$etudiantId]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    die("<div class='alert alert-danger'>Étudiant non trouvé.</div>");
}

// Historique scolaire
$stmt = $db->prepare("
    SELECT * FROM historique_scolaire
    WHERE etudiant_id = ?
    ORDER BY annee_obtention DESC
");
$stmt->execute([$etudiantId]);
$historiqueScolaire = $stmt->fetchAll();

// Contacts d'urgence
$stmt = $db->prepare("
    SELECT * FROM contacts_urgence
    WHERE etudiant_id = ?
    ORDER BY est_contact_principal DESC, id ASC
");
$stmt->execute([$etudiantId]);
$contactsUrgence = $stmt->fetchAll();

// Messages de succès/erreur
$message = '';
$messageType = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$pageTitle = "Fiche Étudiant - " . echapper($etudiant['prenom'] . ' ' . $etudiant['nom']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/design_system.css">
    <link rel="stylesheet" href="css/etudiants.css">
    <link rel="stylesheet" href="css/fiche_etudiant.css">
</head>
<body>
    <!-- Header (à inclure depuis le template général) -->
    <header class="university-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../tableau_bord/index.php" class="logo-link">
                    <span class="university-name">Iteam University</span>
                </a>
            </div>
            <nav class="main-navigation">
                <a href="../tableau_bord/index.php">Tableau de bord</a>
                <?php if ($role === 'administrateur'): ?>
                    <a href="liste_etudiants.php">Liste des étudiants</a>
                <?php endif; ?>
            </nav>
            <div class="user-section">
                <span class="user-name"><?php echo echapper($_SESSION['prenom'] ?? 'Utilisateur'); ?></span>
                <a href="../authentification/deconnexion.php" class="btn-secondary btn-sm">Déconnexion</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Fiche Étudiante Complète</h1>
            <div class="header-actions">
                <a href="historique_academique.php?id=<?php echo $etudiantId; ?>" class="btn-primary">
                    Voir l'historique académique complet
                </a>
                <a href="documents.php?id=<?php echo $etudiantId; ?>" class="btn-primary">
                    Gérer les documents
                </a>
                <?php if ($peutModifier): ?>
                    <button type="button" id="btnModeEdition" class="btn-primary">
                        Mode édition
                    </button>
                <?php endif; ?>
                <button type="button" onclick="window.print()" class="btn-secondary">
                    Imprimer la fiche
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
                <?php echo echapper($message); ?>
                <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Navigation par onglets -->
        <nav class="nav-tabs">
            <button class="nav-tab active" data-tab="infos-personnelles">Informations personnelles</button>
            <button class="nav-tab" data-tab="infos-academiques">Informations académiques</button>
            <button class="nav-tab" data-tab="historique-scolaire">Historique scolaire</button>
            <button class="nav-tab" data-tab="contacts-urgence">Contacts d'urgence</button>
        </nav>

        <form id="formProfilEtudiant" method="POST" action="api/mettre_a_jour_profil.php" class="profile-form">
            <input type="hidden" name="etudiant_id" value="<?php echo $etudiantId; ?>">

            <!-- Onglet 1: Informations personnelles -->
            <div id="tab-infos-personnelles" class="tab-pane active">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Identité et coordonnées</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Numéro étudiant</label>
                                <input type="text" class="form-control" value="<?php echo echapper($etudiant['numero_etudiant']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email universitaire</label>
                                <input type="email" class="form-control" value="<?php echo echapper($etudiant['email']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="prenom" class="form-control editable" value="<?php echo echapper($etudiant['prenom']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="nom" class="form-control editable" value="<?php echo echapper($etudiant['nom']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Date de naissance *</label>
                                <input type="date" name="date_naissance" class="form-control editable" value="<?php echo echapper($etudiant['date_naissance']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Lieu de naissance</label>
                                <input type="text" name="lieu_naissance" class="form-control editable" value="<?php echo echapper($etudiant['lieu_naissance']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Sexe *</label>
                                <select name="sexe" class="form-control editable" required disabled>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="M" <?php echo ($etudiant['sexe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                                    <option value="F" <?php echo ($etudiant['sexe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                                    <option value="autre" <?php echo ($etudiant['sexe'] === 'autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nationalité *</label>
                                <input type="text" name="nationalite" class="form-control editable" value="<?php echo echapper($etudiant['nationalite']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">CIN / Passeport *</label>
                                <input type="text" name="cin_passeport" class="form-control editable" value="<?php echo echapper($etudiant['cin_passeport']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Situation familiale</label>
                                <select name="situation_familiale" class="form-control editable" disabled>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach (SITUATIONS_FAMILIALES as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($etudiant['situation_familiale'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo echapper($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group form-group-full">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="telephone" class="form-control editable" value="<?php echo echapper($etudiant['telephone']); ?>" readonly>
                            </div>

                            <div class="form-group form-group-full">
                                <label class="form-label">Adresse complète</label>
                                <textarea name="adresse" class="form-control editable" rows="2" readonly><?php echo echapper($etudiant['adresse'] ?? $etudiant['adresse_utilisateur']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet 2: Informations académiques -->
            <div id="tab-infos-academiques" class="tab-pane">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Parcours universitaire</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Programme / Filière *</label>
                                <input type="text" name="programme" class="form-control editable" value="<?php echo echapper($etudiant['programme']); ?>" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Année courante *</label>
                                <input type="number" name="annee_courante" class="form-control editable" value="<?php echo echapper($etudiant['annee_courante']); ?>" min="1" max="5" required readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Groupe</label>
                                <input type="text" name="groupe" class="form-control editable" value="<?php echo echapper($etudiant['groupe']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Date d'admission</label>
                                <input type="date" name="date_admission" class="form-control editable" value="<?php echo echapper($etudiant['date_admission']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Statut administratif</label>
                                <select name="statut" class="form-control editable" disabled>
                                    <?php foreach (STATUTS_ETUDIANTS as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($etudiant['statut'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo echapper($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Badge statut</label>
                                <div class="statut-display">
                                    <?php echo getBadgeStatut($etudiant['statut'], 'etudiant'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onglet 3: Historique scolaire -->
            <div id="tab-historique-scolaire" class="tab-pane">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Établissements précédents et diplômes</h3>
                        <?php if ($peutModifier): ?>
                            <button type="button" id="btnAjouterHistorique" class="btn-primary btn-sm">
                                + Ajouter un établissement
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historiqueScolaire)): ?>
                            <p class="text-muted">Aucun historique scolaire enregistré.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Établissement</th>
                                            <th>Type</th>
                                            <th>Diplôme obtenu</th>
                                            <th>Année</th>
                                            <th>Mention</th>
                                            <th>Ville / Pays</th>
                                            <?php if ($peutModifier): ?>
                                                <th>Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historiqueScolaire as $hist): ?>
                                            <tr>
                                                <td><?php echo echapper($hist['etablissement']); ?></td>
                                                <td><?php echo echapper(TYPES_ETABLISSEMENTS[$hist['type_etablissement']] ?? $hist['type_etablissement']); ?></td>
                                                <td><?php echo echapper($hist['diplome_obtenu']); ?></td>
                                                <td><?php echo echapper($hist['annee_obtention']); ?></td>
                                                <td><?php echo echapper($hist['mention'] ?? '-'); ?></td>
                                                <td><?php echo echapper($hist['ville'] . ', ' . $hist['pays']); ?></td>
                                                <?php if ($peutModifier): ?>
                                                    <td>
                                                        <div class="table-actions">
                                                            <button type="button" class="action-btn edit" onclick="modifierHistorique(<?php echo $hist['id']; ?>)" title="Modifier">
                                                                ✏️
                                                            </button>
                                                            <button type="button" class="action-btn delete" onclick="supprimerHistorique(<?php echo $hist['id']; ?>)" title="Supprimer">
                                                                🗑️
                                                            </button>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Onglet 4: Contacts d'urgence -->
            <div id="tab-contacts-urgence" class="tab-pane">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Personnes à contacter en cas d'urgence</h3>
                        <?php if ($peutModifier): ?>
                            <button type="button" id="btnAjouterContact" class="btn-primary btn-sm">
                                + Ajouter un contact
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($contactsUrgence)): ?>
                            <p class="text-muted">Aucun contact d'urgence enregistré.</p>
                        <?php else: ?>
                            <div class="contacts-grid">
                                <?php foreach ($contactsUrgence as $contact): ?>
                                    <div class="contact-card <?php echo $contact['est_contact_principal'] ? 'contact-principal' : ''; ?>">
                                        <?php if ($contact['est_contact_principal']): ?>
                                            <span class="badge badge-primary">Contact principal</span>
                                        <?php endif; ?>
                                        <h4><?php echo echapper($contact['nom_complet']); ?></h4>
                                        <p class="contact-relation">
                                            <strong>Relation:</strong>
                                            <?php echo echapper(RELATIONS_CONTACT[$contact['relation']] ?? $contact['relation']); ?>
                                        </p>
                                        <p class="contact-info">
                                            <strong>Téléphone:</strong> <?php echo echapper($contact['telephone_principal']); ?>
                                            <?php if ($contact['telephone_secondaire']): ?>
                                                <br><small>Secondaire: <?php echo echapper($contact['telephone_secondaire']); ?></small>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($contact['email']): ?>
                                            <p class="contact-info">
                                                <strong>Email:</strong> <?php echo echapper($contact['email']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($contact['adresse']): ?>
                                            <p class="contact-info">
                                                <strong>Adresse:</strong> <?php echo echapper($contact['adresse']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($peutModifier): ?>
                                            <div class="contact-actions">
                                                <button type="button" class="btn-secondary btn-sm" onclick="modifierContact(<?php echo $contact['id']; ?>)">
                                                    Modifier
                                                </button>
                                                <button type="button" class="btn-danger btn-sm" onclick="supprimerContact(<?php echo $contact['id']; ?>)">
                                                    Supprimer
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($peutModifier): ?>
                <div class="form-actions" id="formActions" style="display: none;">
                    <button type="submit" class="btn-success btn-lg">
                        Enregistrer les modifications
                    </button>
                    <button type="button" class="btn-secondary btn-lg" id="btnAnnuler">
                        Annuler
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Footer -->
    <footer class="university-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4>Iteam University</h4>
                <p>Plateforme ERP universitaire</p>
                <p>&copy; 2024 Tous droits réservés</p>
            </div>
        </div>
    </footer>

    <script src="js/validation_profil.js"></script>
    <script src="js/gestion_profil.js"></script>
</body>
</html>
