<?php
include "includes/config.php";
/*La partie de professeur*/
function GetSessionEnCoursProf($profId)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT sc.id AS session_id, c.nom_cours, sc.date_session, sc.heure_debut, sc.heure_fin FROM sessions_cours sc
            JOIN professeurs p ON sc.professeur_id = p.id
            JOIN cours c ON sc.cours_id = c.id
            WHERE p.utilisateur_id = ?
            AND sc.date_session = CURDATE()
            AND CURTIME() BETWEEN sc.heure_debut AND sc.heure_fin
            LIMIT 1
            ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/* Récupère les étudiants inscrits à un cours pour une session donnée*/
function GetEtudiantCours($sessionId)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT e.id AS etudiant_id, u.nom, u.prenom
            FROM inscriptions_cours ic
            JOIN etudiants e ON ic.etudiant_id = e.id
            JOIN utilisateurs u ON e.utilisateur_id = u.id
            WHERE ic.cours_id = (
                SELECT cours_id FROM sessions_cours WHERE id = ?
            )
              AND ic.statut = 'confirmee'
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}
function enregistrerAbsences($sessionId, $date, $statuts, $profId)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );

        // 1. Supprimer l'appel existant
        $stmt = $pdo->prepare(
            "DELETE FROM presence_etudiants WHERE session_cours_id = ?"
        );
        $stmt->execute([$sessionId]);

        // 2. Réinsérer les nouvelles présences
        $stmt = $pdo->prepare("
            INSERT INTO presence_etudiants
            (etudiant_id, session_cours_id, date, statut, enregistre_par)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($statuts as $etudiantId => $statut) {
            $stmt->execute([
                $etudiantId,
                $sessionId,
                $date,
                $statut,
                $profId
            ]);
        }

        return true;
    } catch (PDOException $e) {
        return false;
    }
}
function appelDejaFait($sessionId)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM presence_etudiants WHERE session_cours_id = ?"
        );
        $stmt->execute([$sessionId]);

        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
function GetPresencesSession($sessionId)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT etudiant_id, statut
            FROM presence_etudiants
            WHERE session_cours_id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId]);

        // Retourne: [etudiant_id => statut]
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        return [];
    }
}
/*********************************** */
/**La partie de l'etudaint */
function getAbsencesEtudiant($etudiant_id)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT 
                pe.id AS presence_id,
                pe.date,
                pe.statut,
                pe.justification,
                pe.fichier_justification,
                c.nom_cours,
                sc.type_session
            FROM presence_etudiants pe
            JOIN sessions_cours sc ON pe.session_cours_id = sc.id
            JOIN cours c ON sc.cours_id = c.id
            WHERE pe.etudiant_id = ?
            AND pe.statut IN ('absent', 'justifie')
            ORDER BY pe.date DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erreur BD : " . $e->getMessage());
    }
}
function ajouterJustificationAbsence($presence_id, $texte = null, $fichier = null)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Si fichier fourni, statut = justifie, sinon reste absent (administration validera)
        $statut = $fichier ? 'justifie' : 'absent';

        $sql = "
            UPDATE presence_etudiants
            SET 
                justification = ?,
                fichier_justification = ?,
                statut = ?
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $texte,
            $fichier,
            $statut,
            $presence_id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}
function getStatAbsMatiere($etudiant_id)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT 
                c.nom_cours AS matiere,
                COUNT(p.id) AS total_seances,
                SUM(p.statut = 'absent') AS total_absences,
                SUM(p.statut = 'justifie') AS total_justifiees,
                ROUND(
                    (SUM(p.statut = 'absent') / COUNT(p.id)) * 100,
                    2
                ) AS taux_absence,
                CASE
                    WHEN SUM(p.statut = 'absent') >= 4 THEN 'ALERTE'
                    ELSE 'OK'
                END AS etat_alerte
            FROM presence_etudiants p
            JOIN sessions_cours s ON p.session_cours_id = s.id
            JOIN cours c ON s.cours_id = c.id
            WHERE p.etudiant_id = ?
            GROUP BY c.id, c.nom_cours
            ORDER BY taux_absence DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}
