/**
 * JavaScript - Validation du profil étudiant
 * Partie 3.1 - Fiche étudiante complète
 *
 
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formProfilEtudiant');

    if (!form) return;

    // Validation en temps réel
    const champsValidation = {
        prenom: validateNonVide,
        nom: validateNonVide,
        date_naissance: validateDate,
        nationalite: validateNonVide,
        cin_passeport: validateNonVide,
        telephone: validateTelephone,
        email: validateEmail
    };

    // Attacher les événements de validation
    Object.keys(champsValidation).forEach(champ => {
        const element = form.querySelector(`[name="${champ}"]`);
        if (element) {
            element.addEventListener('blur', function() {
                const validationFn = champsValidation[champ];
                validationFn(this);
            });

            element.addEventListener('input', function() {
                // Retirer les messages d'erreur pendant la saisie
                this.classList.remove('is-invalid');
                const feedback = this.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            });
        }
    });

    // Validation à la soumission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        let isValid = true;

        // Valider tous les champs
        Object.keys(champsValidation).forEach(champ => {
            const element = form.querySelector(`[name="${champ}"]`);
            if (element && !element.hasAttribute('readonly')) {
                const validationFn = champsValidation[champ];
                if (!validationFn(element)) {
                    isValid = false;
                }
            }
        });

        if (isValid) {
            // Soumettre le formulaire via AJAX
            soumettreFormulaire(form);
        } else {
            afficherMessage('Veuillez corriger les erreurs avant de continuer', 'error');
        }
    });
});

/**
 * Valider un champ non vide
 */
function validateNonVide(element) {
    const valeur = element.value.trim();

    if (valeur === '') {
        afficherErreurChamp(element, 'Ce champ est requis');
        return false;
    }

    afficherSuccesChamp(element);
    return true;
}

/**
 * Valider une date
 */
function validateDate(element) {
    const valeur = element.value;

    if (!valeur) {
        afficherErreurChamp(element, 'Ce champ est requis');
        return false;
    }

    const date = new Date(valeur);
    const aujourdhui = new Date();

    // Vérifier que la date est dans le passé
    if (date >= aujourdhui) {
        afficherErreurChamp(element, 'La date doit être dans le passé');
        return false;
    }

    // Vérifier un âge minimum (par exemple 16 ans)
    const ageMinimum = new Date();
    ageMinimum.setFullYear(aujourdhui.getFullYear() - 16);

    if (date > ageMinimum) {
        afficherErreurChamp(element, 'Âge minimum requis : 16 ans');
        return false;
    }

    afficherSuccesChamp(element);
    return true;
}

/**
 * Valider un numéro de téléphone
 */
function validateTelephone(element) {
    const valeur = element.value.trim();

    if (valeur === '') {
        // Téléphone optionnel
        element.classList.remove('is-invalid', 'is-valid');
        return true;
    }

    // Format tunisien: +216 XX XXX XXX ou 00216 XX XXX XXX ou XX XXX XXX
    const regex = /^(\+216|00216|0)?[2-9]\d{7}$/;
    const telSansEspaces = valeur.replace(/\s+/g, '');

    if (!regex.test(telSansEspaces)) {
        afficherErreurChamp(element, 'Format de téléphone invalide (ex: +216 20 123 456)');
        return false;
    }

    afficherSuccesChamp(element);
    return true;
}

/**
 * Valider un email
 */
function validateEmail(element) {
    const valeur = element.value.trim();

    if (valeur === '') {
        // Email optionnel
        element.classList.remove('is-invalid', 'is-valid');
        return true;
    }

    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regex.test(valeur)) {
        afficherErreurChamp(element, 'Format d\'email invalide');
        return false;
    }

    afficherSuccesChamp(element);
    return true;
}

/**
 * Afficher une erreur sur un champ
 */
function afficherErreurChamp(element, message) {
    element.classList.remove('is-valid');
    element.classList.add('is-invalid');

    let feedback = element.parentElement.querySelector('.invalid-feedback');

    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        element.parentElement.appendChild(feedback);
    }

    feedback.textContent = message;
    feedback.style.display = 'block';
}

/**
 * Afficher un succès sur un champ
 */
function afficherSuccesChamp(element) {
    element.classList.remove('is-invalid');
    element.classList.add('is-valid');

    const feedback = element.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.style.display = 'none';
    }
}

/**
 * Soumettre le formulaire via AJAX
 */
function soumettreFormulaire(form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');

    // Désactiver le bouton de soumission
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Enregistrement...';

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            afficherMessage(data.message || 'Profil mis à jour avec succès', 'success');

            // Recharger la page après 1 seconde
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            afficherMessage(data.message || 'Une erreur est survenue', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enregistrer les modifications';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        afficherMessage('Erreur de connexion au serveur', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Enregistrer les modifications';
    });
}

/**
 * Afficher un message global
 */
function afficherMessage(message, type) {
    // Créer l'élément de message
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const messageHTML = `
        <div class="alert ${alertClass} alert-dismissible fade-in">
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    `;

    // Insérer au début du container
    const container = document.querySelector('.container');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = messageHTML;

    container.insertBefore(tempDiv.firstElementChild, container.firstChild);

    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);

    // Scroller vers le haut
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
