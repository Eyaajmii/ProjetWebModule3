-- ============================================================================
-- ITEAM UNIVERSITY ERP - BASE DE DONNÉES FUSIONNÉE
-- Fusion des travaux: Votre équipe (Module 3.1 & 3.3) + Collègue
-- ============================================================================
-- Ce script crée une base de données complète et cohérente
-- Compatible phpMyAdmin (MySQL / MariaDB)
-- ============================================================================

DROP DATABASE IF EXISTS iteam_university;
CREATE DATABASE iteam_university
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE iteam_university;

-- ============================================================================
-- TABLE 1: UTILISATEURS (Base commune)
-- ============================================================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    role ENUM('etudiant', 'professeur', 'administrateur') NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    photo_profil VARCHAR(255),
    telephone VARCHAR(20),
    adresse TEXT,
    est_actif BOOLEAN DEFAULT TRUE,
    derniere_connexion DATETIME,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 2: ÉTUDIANTS (Fusion des deux versions)
-- ============================================================================
CREATE TABLE etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNIQUE NOT NULL,
    numero_etudiant VARCHAR(20) UNIQUE NOT NULL,

    -- Informations personnelles (Partie 3.1)
    date_naissance DATE,
    sexe ENUM('M', 'F', 'autre'),
    lieu_naissance VARCHAR(100),
    nationalite VARCHAR(50),
    situation_familiale ENUM('celibataire', 'marie', 'divorce', 'veuf', 'autre') DEFAULT 'celibataire',
    cin_passeport VARCHAR(50),
    adresse TEXT,
    telephone_urgence VARCHAR(20),
    contact_urgence VARCHAR(100),

    -- Informations académiques
    annee_courante INT,
    programme VARCHAR(100),
    groupe VARCHAR(50),
    date_admission DATE,
    statut ENUM('actif', 'diplome', 'suspendu', 'retire') DEFAULT 'actif',

    CONSTRAINT fk_etudiant_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,

    INDEX idx_numero_etudiant (numero_etudiant),
    INDEX idx_statut (statut),
    INDEX idx_programme (programme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 3: HISTORIQUE SCOLAIRE (Partie 3.1 - Votre équipe)
-- ============================================================================
CREATE TABLE historique_scolaire (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    etablissement VARCHAR(255) NOT NULL,
    type_etablissement ENUM('lycee', 'universite', 'institut', 'autre') NOT NULL,
    diplome_obtenu VARCHAR(100),
    annee_obtention YEAR,
    mention VARCHAR(50),
    pays VARCHAR(50) DEFAULT 'Tunisie',
    ville VARCHAR(100),
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_historique_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,

    INDEX idx_etudiant_historique (etudiant_id),
    INDEX idx_annee_obtention (annee_obtention)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 4: CONTACTS D'URGENCE (Partie 3.1 - Votre équipe)
-- ============================================================================
CREATE TABLE contacts_urgence (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    nom_complet VARCHAR(100) NOT NULL,
    relation ENUM('pere', 'mere', 'conjoint', 'frere_soeur', 'tuteur', 'autre') NOT NULL,
    telephone_principal VARCHAR(20) NOT NULL,
    telephone_secondaire VARCHAR(20),
    email VARCHAR(100),
    adresse TEXT,
    est_contact_principal BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_contact_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,

    INDEX idx_etudiant_contact (etudiant_id),
    INDEX idx_contact_principal (est_contact_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 5: PROFESSEURS (Collègue)
-- ============================================================================
CREATE TABLE professeurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNIQUE NOT NULL,
    numero_employe VARCHAR(20) UNIQUE,
    grade VARCHAR(50),
    departement VARCHAR(100),
    statut ENUM('actif', 'conge', 'retraite') DEFAULT 'actif',

    CONSTRAINT fk_prof_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,

    INDEX idx_numero_employe (numero_employe),
    INDEX idx_departement (departement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 6: COURS (Collègue)
-- ============================================================================
CREATE TABLE cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_cours VARCHAR(20) UNIQUE NOT NULL,
    nom_cours VARCHAR(100) NOT NULL,
    departement VARCHAR(100),
    credits_ects INT,
    semestre INT,

    INDEX idx_code_cours (code_cours),
    INDEX idx_departement (departement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 7: PARCOURS ACADÉMIQUES (Partie 3.2 - Autre équipe + Collègue)
-- ============================================================================
CREATE TABLE parcours_academiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    annee_academique YEAR,
    semestre INT,
    cours_id INT NOT NULL,
    note DECIMAL(4,2),
    credits_ects INT,
    statut ENUM('en_cours', 'valide', 'echoue', 'en_redoublement'),

    CONSTRAINT fk_parcours_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    CONSTRAINT fk_parcours_cours
        FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,

    INDEX idx_etudiant_parcours (etudiant_id),
    INDEX idx_cours (cours_id),
    INDEX idx_annee_sem (annee_academique, semestre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 8: INSCRIPTIONS AUX COURS (Collègue)
-- ============================================================================
CREATE TABLE inscriptions_cours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    cours_id INT NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'rejetee') DEFAULT 'en_attente',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inscription_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    CONSTRAINT fk_inscription_cours
        FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,

    INDEX idx_etudiant_inscr (etudiant_id),
    INDEX idx_cours_inscr (cours_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 9: NOTES ÉTUDIANTS (Collègue)
-- ============================================================================
CREATE TABLE notes_etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    cours_id INT NOT NULL,
    note DECIMAL(5,2),
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_note_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    CONSTRAINT fk_note_cours
        FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE,

    INDEX idx_etudiant_note (etudiant_id),
    INDEX idx_cours_note (cours_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 10: DOCUMENTS ÉTUDIANTS (Partie 3.3 - Votre équipe + Collègue fusionné)
-- ============================================================================
CREATE TABLE documents_etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    version INT DEFAULT 1,

    -- Types de documents selon cahier des charges (Partie 3.3)
    type_document ENUM('cv', 'lettre_motivation', 'photo_identite', 'copie_diplome', 'autre') NOT NULL,

    nom_fichier VARCHAR(255) NOT NULL,
    type_mime VARCHAR(100),
    extension VARCHAR(10),
    chemin_fichier VARCHAR(255) NOT NULL,
    taille_fichier INT NOT NULL,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Statuts selon cahier des charges (valide, en_attente, rejete)
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',

    -- Traçabilité de validation (Partie 3.3)
    date_validation DATETIME,
    valide_par INT,
    notes_administration TEXT,

    CONSTRAINT fk_document_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    CONSTRAINT fk_document_validateur
        FOREIGN KEY (valide_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,

    -- Contrainte de taille maximale 5MB
    CONSTRAINT chk_taille_max CHECK (taille_fichier <= 5242880),

    INDEX idx_etudiant_documents (etudiant_id),
    INDEX idx_statut_doc (statut),
    INDEX idx_type_document (type_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 11: PRÉSENCE ÉTUDIANTS (Partie 3.4 - Autre équipe + Collègue)
-- ============================================================================
CREATE TABLE presence_etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    session_cours_id INT,
    date DATE NOT NULL,
    statut ENUM('present', 'absent', 'justifie') DEFAULT 'absent',
    justification TEXT,
    fichier_justification VARCHAR(255),
    enregistre_par INT,
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_presence_etudiant
        FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    CONSTRAINT fk_presence_enregistreur
        FOREIGN KEY (enregistre_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,

    INDEX idx_etudiant_presence (etudiant_id),
    INDEX idx_date_presence (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 12: MESSAGERIE (Collègue)
-- ============================================================================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id INT NOT NULL,
    destinataire_id INT,
    contenu TEXT,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_lu BOOLEAN DEFAULT FALSE,

    CONSTRAINT fk_message_expediteur
        FOREIGN KEY (expediteur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_destinataire
        FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,

    INDEX idx_expediteur (expediteur_id),
    INDEX idx_destinataire (destinataire_id),
    INDEX idx_date_envoi (date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE 13: NOTIFICATIONS (Votre équipe)
-- ============================================================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('information', 'avertissement', 'succes', 'erreur') DEFAULT 'information',
    est_lu BOOLEAN DEFAULT FALSE,
    url_action VARCHAR(255),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notification_utilisateur
        FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,

    INDEX idx_utilisateur_notif (utilisateur_id),
    INDEX idx_est_lu (est_lu),
    INDEX idx_date_notif (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DONNÉES DE TEST
-- ============================================================================

-- Utilisateurs (fusionné)
INSERT INTO utilisateurs (email, mot_de_passe_hash, role, prenom, nom, telephone, adresse) VALUES
('admin@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrateur', 'Admin', 'System', '+216 70 123 456', 'Tunis, Tunisie'),
('prof1@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professeur', 'Ali', 'Ben Salah', '+216 71 222 333', 'Tunis'),
('prof2@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professeur', 'Nadia', 'Khelifi', '+216 72 444 555', 'Ariana'),
('etudiant.test@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant', 'Ahmed', 'Ben Ali', '+216 20 123 456', 'Tunis'),
('sara.trabelsi@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant', 'Sara', 'Trabelsi', '+216 22 112 233', 'Sfax'),
('youssef.mansouri@iteam.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant', 'Youssef', 'Mansouri', '+216 99 887 766', 'Sousse');

-- Professeurs
INSERT INTO professeurs (utilisateur_id, numero_employe, grade, departement, statut) VALUES
(2, 'EMP001', 'Professeur', 'Informatique', 'actif'),
(3, 'EMP002', 'Maître de conférences', 'Réseaux', 'actif');

-- Étudiants (avec tous les champs fusionnés)
INSERT INTO etudiants (
    utilisateur_id, numero_etudiant, date_naissance, sexe, lieu_naissance,
    nationalite, situation_familiale, cin_passeport, adresse,
    telephone_urgence, contact_urgence, annee_courante, programme,
    groupe, date_admission, statut
) VALUES
(4, 'ETU2024001', '2002-05-15', 'M', 'Tunis', 'Tunisienne', 'celibataire', '12345678',
 'Tunis Centre', '+216 20 111 222', 'Mohamed Ben Ali (père)', 3, 'Licence Informatique',
 'Groupe A', '2021-09-01', 'actif'),
(5, 'ETU2024002', '2002-05-12', 'F', 'Sfax', 'Tunisienne', 'celibataire', 'AA123456',
 'Sfax Centre', '+216 22 112 233', 'Père', 2, 'DSI', 'G1', '2023-09-15', 'actif'),
(6, 'ETU2024003', '2001-11-03', 'M', 'Sousse', 'Tunisienne', 'celibataire', 'BB654321',
 'Sousse Ville', '+216 99 887 766', 'Mère', 2, 'DSI', 'G1', '2023-09-15', 'actif');

-- Historique scolaire
INSERT INTO historique_scolaire (etudiant_id, etablissement, type_etablissement, diplome_obtenu, annee_obtention, mention, ville) VALUES
(1, 'Lycée Pilote Bourguiba', 'lycee', 'Baccalauréat Sciences Mathématiques', 2020, 'Bien', 'Tunis'),
(1, 'Institut Préparatoire aux Études d\'Ingénieurs', 'institut', 'Préparatoire MP', 2021, 'Assez Bien', 'Monastir'),
(2, 'Lycée Tahar Sfar', 'lycee', 'Baccalauréat Informatique', 2021, 'Très Bien', 'Sfax');

-- Contacts d'urgence
INSERT INTO contacts_urgence (etudiant_id, nom_complet, relation, telephone_principal, email, est_contact_principal) VALUES
(1, 'Mohamed Ben Ali', 'pere', '+216 20 111 222', 'mohamed.benali@email.com', TRUE),
(1, 'Fatma Ben Ali', 'mere', '+216 22 333 444', 'fatma.benali@email.com', FALSE),
(2, 'Ali Trabelsi', 'pere', '+216 22 112 200', 'ali.trabelsi@email.com', TRUE);

-- Cours
INSERT INTO cours (code_cours, nom_cours, departement, credits_ects, semestre) VALUES
('WEB101', 'Développement Web', 'Informatique', 6, 1),
('JAVA201', 'Programmation Java', 'Informatique', 6, 2),
('BDD301', 'Bases de Données', 'Informatique', 6, 3);

-- Parcours académiques
INSERT INTO parcours_academiques (etudiant_id, annee_academique, semestre, cours_id, note, credits_ects, statut) VALUES
(1, 2024, 1, 1, 14.50, 6, 'valide'),
(1, 2024, 2, 2, 13.00, 6, 'valide'),
(2, 2024, 1, 1, 15.50, 6, 'valide'),
(3, 2024, 1, 1, 9.50, 6, 'echoue');

-- Inscriptions aux cours
INSERT INTO inscriptions_cours (etudiant_id, cours_id, statut) VALUES
(1, 1, 'confirmee'),
(1, 2, 'confirmee'),
(2, 1, 'confirmee'),
(3, 1, 'en_attente');

-- Notes étudiants
INSERT INTO notes_etudiants (etudiant_id, cours_id, note) VALUES
(1, 1, 14.50),
(1, 2, 13.00),
(2, 1, 15.50),
(3, 1, 12.75);

-- Documents étudiants (exemples de test)
INSERT INTO documents_etudiants (
    etudiant_id, version, type_document, nom_fichier,
    chemin_fichier, type_mime, extension, taille_fichier, statut
) VALUES
(1, 1, 'cv', 'cv_ahmed.pdf', 'uploads/documents/1/cv_ahmed.pdf',
 'application/pdf', '.pdf', 450000, 'valide'),
(2, 1, 'photo_identite', 'photo_sara.jpg', 'uploads/documents/2/photo_sara.jpg',
 'image/jpeg', '.jpg', 120000, 'en_attente');

-- Présence (exemples)
INSERT INTO presence_etudiants (etudiant_id, session_cours_id, date, statut, enregistre_par) VALUES
(1, 1, '2024-12-20', 'present', 2),
(2, 1, '2024-12-20', 'present', 2),
(3, 1, '2024-12-20', 'absent', 2);

-- Messages
INSERT INTO messages (expediteur_id, destinataire_id, contenu) VALUES
(4, 2, 'Bonjour professeur, question sur le cours de Web'),
(2, 4, 'Bonjour, le cours commence à 10h demain');

-- ============================================================================
-- VÉRIFICATIONS
-- ============================================================================

SELECT 'Base de données créée avec succès!' AS Message;
SELECT COUNT(*) AS 'Nombre de tables' FROM information_schema.tables WHERE table_schema = 'iteam_university';

-- ============================================================================
-- INFORMATIONS DE CONNEXION
-- ============================================================================

SELECT '========================================' AS '';
SELECT 'COMPTES DE TEST DISPONIBLES' AS '';
SELECT '========================================' AS '';
SELECT 'Admin: admin@iteam.edu' AS '';
SELECT 'Professeur: prof1@iteam.edu' AS '';
SELECT 'Étudiant: etudiant.test@iteam.edu' AS '';
SELECT 'Mot de passe (tous): password' AS '';
SELECT '========================================' AS '';

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
