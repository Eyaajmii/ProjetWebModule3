<?php
/**
 * Fonctions Helper - Module Étudiants
 * Projet ERP Iteam University
 * Parties 3.1 et 3.3
 *

 */

// Chemins et constantes
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/documents/');
define('MAX_FILE_SIZE', 5242880); // 5 MB en octets
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/jpg'
]);

// Types de documents autorisés
define('DOCUMENT_TYPES', [
    'cv' => 'CV',
    'lettre_motivation' => 'Lettre de motivation',
    'photo_identite' => 'Photo d\'identité',
    'copie_diplome' => 'Copie de diplôme',
    'autre' => 'Autre document'
]);

// Statuts des documents
define('DOCUMENT_STATUTS', [
    'en_attente' => 'En attente de validation',
    'valide' => 'Validé',
    'rejete' => 'Rejeté'
]);

// Statuts administratifs des étudiants
define('STATUTS_ETUDIANTS', [
    'actif' => 'Inscrit',
    'diplome' => 'Diplômé',
    'suspendu' => 'Suspendu',
    'retire' => 'Démissionné'
]);

// Situations familiales
define('SITUATIONS_FAMILIALES', [
    'celibataire' => 'Célibataire',
    'marie' => 'Marié(e)',
    'divorce' => 'Divorcé(e)',
    'veuf' => 'Veuf(ve)',
    'autre' => 'Autre'
]);

// Relations pour contacts d'urgence
define('RELATIONS_CONTACT', [
    'pere' => 'Père',
    'mere' => 'Mère',
    'conjoint' => 'Conjoint(e)',
    'frere_soeur' => 'Frère/Sœur',
    'tuteur' => 'Tuteur légal',
    'autre' => 'Autre'
]);

// Types d'établissements scolaires
define('TYPES_ETABLISSEMENTS', [
    'lycee' => 'Lycée',
    'universite' => 'Université',
    'institut' => 'Institut',
    'autre' => 'Autre'
]);

/**
 * Fonction pour obtenir la connexion BD (compatible avec config.php existant)
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Fonction pour sécuriser les sorties HTML
 */
function echapper($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction pour formater une date
 */
function formaterDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Fonction pour formater la taille d'un fichier
 */
function formaterTaille($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' octets';
}

/**
 * Fonction pour obtenir le badge HTML d'un statut
 */
function getBadgeStatut($statut, $type = 'document') {
    $badges = [
        'document' => [
            'en_attente' => '<span class="badge badge-warning">En attente</span>',
            'valide' => '<span class="badge badge-success">Validé</span>',
            'rejete' => '<span class="badge badge-danger">Rejeté</span>'
        ],
        'etudiant' => [
            'actif' => '<span class="badge badge-success">Inscrit</span>',
            'diplome' => '<span class="badge badge-primary">Diplômé</span>',
            'suspendu' => '<span class="badge badge-warning">Suspendu</span>',
            'retire' => '<span class="badge badge-danger">Démissionné</span>'
        ]
    ];

    return $badges[$type][$statut] ?? '<span class="badge badge-secondary">' . echapper($statut) . '</span>';
}

/**
 * Fonction pour générer un nom de fichier unique et sécurisé
 */
function genererNomFichier($nomOriginal, $etudiantId) {
    $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
    $nomBase = pathinfo($nomOriginal, PATHINFO_FILENAME);
    $nomSecurise = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomBase);
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    return "{$etudiantId}_{$timestamp}_{$random}_{$nomSecurise}.{$extension}";
}

/**
 * Fonction pour vérifier si l'utilisateur est connecté
 */
function verifierConnexion() {
    if (!isset($_SESSION['utilisateur_id'])) {
        header('Location: ../../authentification/connexion.php');
        exit();
    }
}

/**
 * Fonction pour vérifier les permissions (étudiant ou admin)
 */
function verifierPermission($etudiantId, $requireAdmin = false) {
    $role = $_SESSION['role'] ?? '';
    $userId = $_SESSION['utilisateur_id'] ?? 0;

    // Si admin requis
    if ($requireAdmin && $role !== 'administrateur') {
        return false;
    }

    // Si étudiant: vérifier qu'il accède bien à ses propres données
    if ($role === 'etudiant') {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM etudiants WHERE utilisateur_id = ? AND id = ?");
        $stmt->execute([$userId, $etudiantId]);

        if (!$stmt->fetch()) {
            return false;
        }
    }

    return true;
}

/**
 * Fonction pour envoyer une réponse JSON
 */
function envoyerJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Fonction pour valider un fichier uploadé
 */
function validerFichier($file) {
    $errors = [];

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'upload du fichier (code: {$file['error']})";
        return $errors;
    }

    // Vérifier la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        $tailleMB = round(MAX_FILE_SIZE / 1048576, 2);
        $tailleUpload = round($file['size'] / 1048576, 2);
        $errors[] = "Fichier trop volumineux ({$tailleUpload} MB). Maximum autorisé : {$tailleMB} MB";
    }

    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = "Extension non autorisée (.{$extension}). Formats acceptés : " . implode(', ', ALLOWED_EXTENSIONS);
    }

    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors[] = "Type de fichier non autorisé ({$mimeType}). Types acceptés : PDF, JPG, PNG";
    }

    return $errors;
}

/**
 * Fonction pour créer le répertoire d'upload d'un étudiant
 */
function creerRepertoireEtudiant($etudiantId) {
    $dir = UPLOAD_DIR . $etudiantId . '/';

    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return false;
        }

        // Créer un fichier .htaccess pour sécuriser
        $htaccess = $dir . '.htaccess';
        file_put_contents($htaccess, "Options -Indexes\nphp_flag engine off");
    }

    return $dir;
}

/**
 * Fonction pour logger les erreurs
 */
function loggerErreur($message, $contexte = []) {
    $logDir = dirname(__DIR__) . '/logs/';

    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($contexte)) {
        $log .= ' - Contexte: ' . json_encode($contexte, JSON_UNESCAPED_UNICODE);
    }
    error_log($log . PHP_EOL, 3, $logDir . 'erreurs_' . date('Y-m') . '.log');
}

/**
 * Fonction pour obtenir l'ID de l'étudiant connecté
 */
function getEtudiantIdConnecte() {
    if (!isset($_SESSION['utilisateur_id'])) {
        return null;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM etudiants WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $result = $stmt->fetch();

    return $result ? $result['id'] : null;
}

/**
 * Fonction pour nettoyer les données POST
 */
function nettoyerInput($data) {
    if (is_array($data)) {
        return array_map('nettoyerInput', $data);
    }
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

/**
 * Fonction pour valider un email
 */
function validerEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Fonction pour valider un numéro de téléphone tunisien
 */
function validerTelephone($tel) {
    // Format tunisien: +216 XX XXX XXX ou 00216 XX XXX XXX ou XX XXX XXX
    $tel = preg_replace('/\s+/', '', $tel);
    return preg_match('/^(\+216|00216|0)?[2-9]\d{7}$/', $tel);
}

/**
 * Fonction pour créer une notification
 */
function creerNotification($utilisateur_id, $titre, $message, $type = 'information', $url_action = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO notifications (utilisateur_id, titre, message, type, url_action)
        VALUES (?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([$utilisateur_id, $titre, $message, $type, $url_action]);
        return true;
    } catch (PDOException $e) {
        loggerErreur("Erreur création notification", ['error' => $e->getMessage()]);
        return false;
    }
}
