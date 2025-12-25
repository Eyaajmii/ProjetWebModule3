<?php
/**
 * Page de connexion simple pour TESTS UNIQUEMENT
 * À utiliser pour tester les fonctionnalités du module étudiants
 */

require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: profil_etudiant.php?id=' . ($_SESSION['etudiant_id'] ?? 1));
    exit();
}

$erreur = '';

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Pour les tests, on accepte le mot de passe "password"
        // En production, il faut vérifier avec password_verify()
        if ($user && ($password === 'password' || password_verify($password, $user['mot_de_passe_hash']))) {
            // Créer la session
            $_SESSION['utilisateur_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['nom'] = $user['nom'];

            // Si c'est un étudiant, récupérer son ID étudiant
            if ($user['role'] === 'etudiant') {
                $stmt = $pdo->prepare("SELECT id FROM etudiants WHERE utilisateur_id = ?");
                $stmt->execute([$user['id']]);
                $etudiant = $stmt->fetch();
                $_SESSION['etudiant_id'] = $etudiant['id'] ?? null;
            }

            // Rediriger selon le rôle
            if ($user['role'] === 'etudiant') {
                header('Location: profil_etudiant.php?id=' . $_SESSION['etudiant_id']);
            } else {
                header('Location: profil_etudiant.php?id=1');
            }
            exit();
        } else {
            $erreur = "Email ou mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur de connexion à la base de données";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Test Module Étudiants</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }

        .test-accounts {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }

        .test-accounts h3 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .account-item {
            background: white;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 13px;
        }

        .account-item strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .account-item code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            color: #667eea;
            font-family: monospace;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎓 Iteam University</h1>
            <p>Module Gestion des Étudiants - Test</p>
        </div>

        <?php if ($erreur): ?>
            <div class="error">
                ⚠️ <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       placeholder="exemple@iteam.edu"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">
                Se connecter
            </button>
        </form>

        <div class="divider">━━━━━━━━━━━━━━━━━━━━</div>

        <div class="test-accounts">
            <h3>📋 Comptes de test disponibles</h3>

            <div class="account-item">
                <strong>👨‍🎓 Étudiant</strong>
                Email: <code>etudiant.test@iteam.edu</code><br>
                Mot de passe: <code>password</code>
            </div>

            <div class="account-item">
                <strong>👨‍💼 Administrateur</strong>
                Email: <code>admin@iteam.edu</code><br>
                Mot de passe: <code>password</code>
            </div>
        </div>
    </div>
</body>
</html>
