<?php
include "includes/config.php";
function est_connecte()
{
    return isset($_SESSION['user_id']);
}
function rediriger_si_non_connecte()
{
    if (!est_connecte()) {
        header('Location: connexion.php');
        exit();
    }
}
function GetHistorique($etudiant_id)
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //$sql = "SELECT * FROM parcours_academiques WHERE etudiant_id = ? ORDER BY annee_academique, semestre ";
        $sql = "SELECT p.annee_academique, p.semestre, p.note, p.statut,
        c.nom_cours AS cours, p.credits_ects
        FROM parcours_academiques p
        JOIN cours c ON p.cours_id = c.id
        WHERE p.etudiant_id = ?
        ORDER BY p.annee_academique, p.semestre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
