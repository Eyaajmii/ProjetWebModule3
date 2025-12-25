/**
 * JavaScript - Validation des documents par l'admin
 * Partie 3.3 - Téléchargement de documents
 *

 */

document.addEventListener('DOMContentLoaded', function() {
    initValidationDocuments();
});

/**
 * Initialiser la validation des documents (pour administrateurs)
 */
function initValidationDocuments() {
    const formValidation = document.getElementById('formValidation');

    if (!formValidation) return;

    formValidation.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const statut = formData.get('statut');
        const commentaires = formData.get('commentaires');

        // Validation
        if (!statut) {
            alert('Veuillez sélectionner une décision');
            return;
        }

        if (statut === 'rejete' && !commentaires.trim()) {
            alert('Un commentaire est obligatoire pour rejeter un document');
            return;
        }

        // Soumettre
        validerDocument(formData);
    });

    // Rendre les commentaires obligatoires si rejet
    const statutValidation = document.getElementById('statutValidation');
    const commentairesValidation = document.getElementById('commentairesValidation');

    if (statutValidation && commentairesValidation) {
        statutValidation.addEventListener('change', function() {
            if (this.value === 'rejete') {
                commentairesValidation.required = true;
                commentairesValidation.placeholder = 'Commentaire obligatoire en cas de rejet';
                commentairesValidation.parentElement.querySelector('.form-label').innerHTML = 'Commentaires *';
            } else {
                commentairesValidation.required = false;
                commentairesValidation.placeholder = 'Ajoutez des commentaires (optionnel)';
                commentairesValidation.parentElement.querySelector('.form-label').innerHTML = 'Commentaires';
            }
        });
    }
}

/**
 * Ouvrir le modal de validation
 */
function ouvrirModalValidation(documentId) {
    const modal = document.getElementById('modalValidation');
    const documentIdInput = document.getElementById('documentIdValidation');

    if (!modal || !documentIdInput) return;

    documentIdInput.value = documentId;

    // Réinitialiser le formulaire
    document.getElementById('formValidation').reset();

    modal.style.display = 'flex';
}

/**
 * Fermer le modal de validation
 */
function fermerModalValidation() {
    const modal = document.getElementById('modalValidation');

    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Valider ou rejeter un document
 */
function validerDocument(formData) {
    const modal = document.getElementById('modalValidation');
    const submitBtn = document.querySelector('#formValidation button[type="submit"]');

    // Désactiver le bouton
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Traitement...';

    fetch('api/valider_document.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Document traité avec succès');
            modal.style.display = 'none';
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enregistrer';
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Enregistrer';
    });
}

/**
 * Fermer le modal en cliquant à l'extérieur
 */
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalValidation');

    if (modal && e.target === modal) {
        fermerModalValidation();
    }
});

/**
 * Fermer le modal avec la touche Échap
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fermerModalValidation();
    }
});
