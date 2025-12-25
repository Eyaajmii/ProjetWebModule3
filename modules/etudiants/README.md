# Module 3 - Gestion des Étudiants
## Parties 3.1 et 3.3

**Responsables:** JLASSI MARIEM, AJMI Eya
**Projet:** ERP Iteam University
**Version:** 1.0
**Date:** Décembre 2024

---

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Partie 3.1 - Fiche étudiante complète](#partie-31---fiche-étudiante-complète)
3. [Partie 3.3 - Téléchargement de documents](#partie-33---téléchargement-de-documents)
4. [Structure de la base de données](#structure-de-la-base-de-données)
5. [Architecture des fichiers](#architecture-des-fichiers)
6. [Installation et configuration](#installation-et-configuration)
7. [Utilisation](#utilisation)
8. [Conformité au cahier des charges](#conformité-au-cahier-des-charges)

---

## 🎯 Vue d'ensemble

Ce module implémente les fonctionnalités **3.1 (Fiche étudiante complète)** et **3.3 (Téléchargement de documents)** du cahier des charges du projet ERP Iteam University.

### Fonctionnalités principales

#### Partie 3.1 - Fiche étudiante complète
- ✅ Informations personnelles (identité, coordonnées, situation familiale)
- ✅ Informations académiques (filière, année, groupe)
- ✅ Historique scolaire (établissements précédents, diplômes)
- ✅ Contacts d'urgence multiples avec relations
- ✅ Statut administratif (inscrit, démissionné, diplômé)

#### Partie 3.3 - Téléchargement de documents
- ✅ Upload de documents (CV, lettre de motivation, photo, copies de diplômes)
- ✅ Validation des formats (PDF, JPG, PNG)
- ✅ Limite de taille (max 5MB par document)
- ✅ Gestion des statuts (en attente, validé, rejeté)
- ✅ Commentaires de l'administration
- ✅ Système de versioning des documents

---

## 📝 Partie 3.1 - Fiche étudiante complète

### Fonctionnalités implémentées

#### 1. Informations personnelles
```php
Fichier: profil_etudiant.php
```

**Champs disponibles :**
- Numéro étudiant (automatique, readonly)
- Email universitaire (readonly)
- Prénom et Nom
- Date de naissance et lieu de naissance
- Sexe
- Nationalité
- CIN / Passeport
- **Situation familiale** (célibataire, marié, divorcé, veuf)
- Téléphone
- Adresse complète

#### 2. Informations académiques

**Champs disponibles :**
- Programme / Filière
- Année courante
- Groupe
- Date d'admission
- Statut administratif avec badge visuel

#### 3. Historique scolaire

**Table dédiée :** `historique_scolaire`

**Fonctionnalités :**
- Ajout d'établissements précédents
- Types : Lycée, Université, Institut, Autre
- Informations : Diplôme obtenu, année, mention, ville, pays
- Modification et suppression d'entrées
- Affichage en tableau trié par année

#### 4. Contacts d'urgence

**Table dédiée :** `contacts_urgence`

**Fonctionnalités :**
- Contacts multiples avec relations (père, mère, conjoint, etc.)
- Téléphones principal et secondaire
- Email et adresse
- Définition d'un contact principal
- Affichage en cartes visuelles

### APIs disponibles

| API | Méthode | Description |
|-----|---------|-------------|
| `api/mettre_a_jour_profil.php` | POST | Mise à jour des informations personnelles et académiques |
| `api/gerer_historique_scolaire.php` | POST/GET/DELETE | CRUD de l'historique scolaire |
| `api/gerer_contacts_urgence.php` | POST/GET/DELETE | CRUD des contacts d'urgence |

---

## 📤 Partie 3.3 - Téléchargement de documents

### Fonctionnalités implémentées

#### 1. Upload de documents

**Types de documents autorisés :**
- CV
- Lettre de motivation
- Photo d'identité
- Copie de diplôme
- Autre

**Validations :**
- ✅ Formats : PDF, JPG, PNG uniquement
- ✅ Taille max : 5 MB par document
- ✅ Validation du type MIME
- ✅ Nom de fichier sécurisé

#### 2. Système de versioning

- Chaque nouveau document du même type incrémente la version
- Historique complet des versions
- Possibilité de ré-uploader après rejet

#### 3. Workflow de validation

**Statuts disponibles :**
- 🟡 **En attente** : Document uploadé, en attente de validation
- ✅ **Validé** : Approuvé par l'administration
- ❌ **Rejeté** : Refusé avec commentaires obligatoires

#### 4. Interface administrateur

- Tableau de tous les documents par type
- Bouton de validation/rejet
- Champ de commentaires (obligatoire pour rejet)
- Traçabilité : qui a validé et quand

### APIs disponibles

| API | Méthode | Description |
|-----|---------|-------------|
| `api/uploader_document.php` | POST | Upload d'un nouveau document |
| `api/valider_document.php` | POST | Validation/rejet par admin |
| `api/telecharger_document.php` | GET | Téléchargement/visualisation |
| `api/supprimer_document.php` | DELETE | Suppression (documents non validés uniquement) |

---

## 🗄️ Structure de la base de données

### Tables créées/modifiées

#### 1. Table `etudiants` (modifications)

```sql
ALTER TABLE etudiants
ADD COLUMN situation_familiale ENUM('celibataire', 'marie', 'divorce', 'veuf', 'autre'),
ADD COLUMN sexe ENUM('M', 'F', 'autre');
```

#### 2. Table `historique_scolaire` (nouvelle)

```sql
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
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
);
```

#### 3. Table `contacts_urgence` (nouvelle)

```sql
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
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
);
```

#### 4. Table `documents_etudiants` (modifications)

```sql
ALTER TABLE documents_etudiants
MODIFY COLUMN type_document ENUM('cv', 'lettre_motivation', 'photo_identite', 'copie_diplome', 'autre') NOT NULL,
MODIFY COLUMN statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
ADD COLUMN type_mime VARCHAR(100),
ADD COLUMN extension VARCHAR(10),
ADD COLUMN date_validation DATETIME,
ADD COLUMN valide_par INT,
ADD COLUMN version INT DEFAULT 1,
ADD CONSTRAINT fk_documents_valide_par FOREIGN KEY (valide_par) REFERENCES utilisateurs(id),
ADD CONSTRAINT chk_taille_max CHECK (taille_fichier <= 5242880);
```

### Script d'installation

```bash
mysql -u root -p iteam_university < database/schema_module3_partie_3.1_3.3.sql
```

---

## 📁 Architecture des fichiers

```
modules/etudiants/
├── profil_etudiant.php          # 3.1 Fiche étudiante complète
├── documents.php                 # 3.3 Gestion des documents
├── historique_academique.php     # 3.2 (autre équipe)
├── liste_etudiants.php
├── presence.php                  # 3.4 (autre équipe)
│
├── api/
│   ├── mettre_a_jour_profil.php         # 3.1 API mise à jour profil
│   ├── gerer_historique_scolaire.php    # 3.1 API historique
│   ├── gerer_contacts_urgence.php       # 3.1 API contacts
│   ├── uploader_document.php            # 3.3 API upload
│   ├── valider_document.php             # 3.3 API validation
│   ├── telecharger_document.php         # 3.3 API téléchargement
│   └── supprimer_document.php           # 3.3 API suppression
│
├── includes/
│   ├── config.php                       # Config BD existante
│   └── fonctions_helpers.php            # Fonctions utilitaires
│
├── css/
│   ├── design_system.css                # Design System complet
│   ├── etudiants.css                    # Styles communs module
│   ├── fiche_etudiant.css               # 3.1 Styles spécifiques
│   └── documents.css                    # 3.3 Styles spécifiques
│
├── js/
│   ├── validation_profil.js             # 3.1 Validation formulaire
│   ├── gestion_profil.js                # 3.1 Gestion onglets/modal
│   ├── upload_manager.js                # 3.3 Gestion upload
│   └── validation_documents.js          # 3.3 Validation admin
│
├── database/
│   └── schema_module3_partie_3.1_3.3.sql  # Script SQL complet
│
├── uploads/
│   └── documents/
│       └── [etudiant_id]/               # Dossiers par étudiant
│
└── README.md                            # Cette documentation
```

---

## ⚙️ Installation et configuration

### Prérequis

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- Extensions PHP : PDO, MySQLi, GD, FileInfo

### Étapes d'installation

#### 1. Configuration de la base de données

```bash
# Exécuter le script SQL
cd modules/etudiants/database
mysql -u root -p iteam_university < schema_module3_partie_3.1_3.3.sql
```

#### 2. Configuration des permissions

```bash
# Créer le dossier d'upload et donner les permissions
mkdir -p uploads/documents
chmod 755 uploads/documents
```

#### 3. Vérifier la configuration

Fichier: `includes/config.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'iteam_university');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### 4. Tester les fonctionnalités

1. Créer un utilisateur test avec le rôle "etudiant"
2. Se connecter et accéder à `profil_etudiant.php`
3. Tester l'upload de documents sur `documents.php`

---

## 🚀 Utilisation

### Pour les étudiants

#### Gérer son profil
1. Accéder à "Fiche étudiante"
2. Cliquer sur "Mode édition"
3. Modifier les informations
4. Cliquer sur "Enregistrer les modifications"

#### Ajouter un historique scolaire
1. Onglet "Historique scolaire"
2. Cliquer sur "+ Ajouter un établissement"
3. Remplir le formulaire
4. Valider

#### Ajouter un contact d'urgence
1. Onglet "Contacts d'urgence"
2. Cliquer sur "+ Ajouter un contact"
3. Remplir les informations
4. Cocher "Contact principal" si nécessaire
5. Valider

#### Uploader un document
1. Accéder à "Gestion des documents"
2. Sélectionner le type de document
3. Glisser-déposer ou cliquer pour sélectionner le fichier
4. Cliquer sur "Télécharger le document"

### Pour les administrateurs

#### Valider/Rejeter un document
1. Accéder à la page documents d'un étudiant
2. Cliquer sur l'icône ✔️ à côté du document
3. Choisir "Valider" ou "Rejeter"
4. Ajouter un commentaire (obligatoire pour rejet)
5. Enregistrer

---

## ✅ Conformité au cahier des charges

### Partie 3.1 - Fiche étudiante

| Exigence | Statut | Implémentation |
|----------|--------|----------------|
| Informations personnelles | ✅ | Toutes les informations requises + situation familiale |
| Informations académiques | ✅ | Filière, année, groupe, statut |
| Historique scolaire | ✅ | Table dédiée avec CRUD complet |
| Contacts d'urgence avec relations | ✅ | Table dédiée, contacts multiples, relations |
| Statut administratif | ✅ | ENUM avec badges visuels |

### Partie 3.3 - Téléchargement de documents

| Exigence | Statut | Implémentation |
|----------|--------|----------------|
| Types de documents | ✅ | CV, lettre, photo, diplômes (ENUM) |
| Validation formats | ✅ | PDF, JPG, PNG (type MIME + extension) |
| Limite 5MB | ✅ | Validation client + serveur + contrainte BD |
| Statuts | ✅ | en_attente, valide, rejete (terminologie cahier) |
| Commentaires admin | ✅ | Champ notes_administration + traçabilité |

### Design System

| Élément | Conformité |
|---------|-----------|
| Couleurs | ✅ Palette officielle respectée |
| Typographie | ✅ Inter + Roboto |
| Composants UI | ✅ Cartes, boutons, formulaires, tableaux |
| Header/Footer | ✅ Templates conformes |
| Responsive | ✅ Mobile-friendly |

---

## 📊 Améliorations apportées

### Par rapport au cahier des charges initial

1. **Système de versioning** : Gestion des versions de documents (non demandé mais utile)
2. **Traçabilité complète** : Qui a validé, quand, avec quels commentaires
3. **Validation stricte** : Type MIME + extension + taille (sécurité renforcée)
4. **Interface drag & drop** : Expérience utilisateur améliorée
5. **Mode édition** : Activation/désactivation des champs pour éviter modifications accidentelles
6. **Notifications** : Système de notifications intégré
7. **Historique des modifications** : Champs date_creation et date_mise_a_jour

---

## 🔒 Sécurité

### Mesures implémentées

- ✅ Validation côté client ET serveur
- ✅ Protection contre les injections SQL (prepared statements)
- ✅ Protection XSS (htmlspecialchars)
- ✅ Noms de fichiers sécurisés (hash + timestamp)
- ✅ Vérification des permissions à chaque action
- ✅ Dossiers uploads protégés (.htaccess)
- ✅ Validation stricte des types MIME
- ✅ Limitation de taille de fichiers

---

## 👥 Auteurs
**JLASSI MARIEM** 
**AJMI Eya** 


**Projet:** ERP Iteam University


---


**Version:** 1.0
**Dernière mise à jour:** Décembre 2024
