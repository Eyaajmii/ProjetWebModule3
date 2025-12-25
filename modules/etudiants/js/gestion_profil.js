/**
 * JavaScript - Gestion du profil étudiant
 * Partie 3.1 - Fiche étudiante (onglets, mode édition, historique, contacts)
 
 */

document.addEventListener('DOMContentLoaded', function() {
    initOnglets();
    initModeEdition();
    initHistoriqueScolaire();
    initContactsUrgence();
});

/**
 * Initialiser la navigation par onglets
 */
function initOnglets() {
    const tabs = document.querySelectorAll('.nav-tab');
    const panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = this.getAttribute('data-tab');

            // Désactiver tous les onglets et panneaux
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            // Activer l'onglet et le panneau sélectionné
            this.classList.add('active');
            document.getElementById('tab-' + targetId).classList.add('active');
        });
    });
}

/**
 * Initialiser le mode édition
 */
function initModeEdition() {
    const btnModeEdition = document.getElementById('btnModeEdition');
    const btnAnnuler = document.getElementById('btnAnnuler');
    const formActions = document.getElementById('formActions');
    const editableFields = document.querySelectorAll('.editable');

    if (!btnModeEdition) return;

    let enEdition = false;

    btnModeEdition.addEventListener('click', function() {
        enEdition = !enEdition;

        if (enEdition) {
            // Activer le mode édition
            this.textContent = 'Annuler l\'édition';
            this.classList.remove('btn-primary');
            this.classList.add('btn-secondary');

            editableFields.forEach(field => {
                if (field.tagName === 'SELECT') {
                    field.disabled = false;
                } else {
                    field.removeAttribute('readonly');
                }
                field.classList.add('editing');
            });

            formActions.style.display = 'flex';
        } else {
            // Désactiver le mode édition
            desactiverModeEdition();
        }
    });

    if (btnAnnuler) {
        btnAnnuler.addEventListener('click', function() {
            if (confirm('Annuler les modifications ?')) {
                window.location.reload();
            }
        });
    }
}

function desactiverModeEdition() {
    const btnModeEdition = document.getElementById('btnModeEdition');
    const formActions = document.getElementById('formActions');
    const editableFields = document.querySelectorAll('.editable');

    btnModeEdition.textContent = 'Mode édition';
    btnModeEdition.classList.remove('btn-secondary');
    btnModeEdition.classList.add('btn-primary');

    editableFields.forEach(field => {
        if (field.tagName === 'SELECT') {
            field.disabled = true;
        } else {
            field.setAttribute('readonly', 'readonly');
        }
        field.classList.remove('editing');
    });

    formActions.style.display = 'none';
}

/**
 * Initialiser la gestion de l'historique scolaire
 */
function initHistoriqueScolaire() {
    const btnAjouterHistorique = document.getElementById('btnAjouterHistorique');

    if (btnAjouterHistorique) {
        btnAjouterHistorique.addEventListener('click', function() {
            afficherModalHistorique();
        });
    }
}

function afficherModalHistorique(historiqueId = null) {
    const modal = creerModal('Ajouter un établissement', `
        <form id="formHistorique">
            <input type="hidden" name="action" value="${historiqueId ? 'modifier' : 'ajouter'}">
            ${historiqueId ? `<input type="hidden" name="id" value="${historiqueId}">` : ''}
            <input type="hidden" name="etudiant_id" value="${document.querySelector('[name="etudiant_id"]').value}">

            <div class="form-group">
                <label class="form-label">Établissement *</label>
                <input type="text" name="etablissement" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Type d'établissement *</label>
                <select name="type_etablissement" class="form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="lycee">Lycée</option>
                    <option value="universite">Université</option>
                    <option value="institut">Institut</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Diplôme obtenu</label>
                <input type="text" name="diplome_obtenu" class="form-control">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Année d'obtention</label>
                    <input type="number" name="annee_obtention" class="form-control" min="1950" max="${new Date().getFullYear()}">
                </div>

                <div class="form-group">
                    <label class="form-label">Mention</label>
                    <input type="text" name="mention" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Pays</label>
                    <input type="text" name="pays" class="form-control" value="Tunisie">
                </div>

                <div class="form-group">
                    <label class="form-label">Ville</label>
                    <input type="text" name="ville" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="fermerModal()">Annuler</button>
            </div>
        </form>
    `);

    document.body.appendChild(modal);

    // Gérer la soumission
    document.getElementById('formHistorique').addEventListener('submit', function(e) {
        e.preventDefault();
        soumettreHistorique(new FormData(this));
    });
}

function soumettreHistorique(formData) {
    fetch('api/gerer_historique_scolaire.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fermerModal();
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    });
}

function modifierHistorique(id) {
    // TODO: Charger les données et afficher le modal pré-rempli
    afficherModalHistorique(id);
}

function supprimerHistorique(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet établissement de l\'historique ?')) {
        return;
    }

    fetch(`api/gerer_historique_scolaire.php?action=supprimer&id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    });
}

/**
 * Initialiser la gestion des contacts d'urgence
 */
function initContactsUrgence() {
    const btnAjouterContact = document.getElementById('btnAjouterContact');

    if (btnAjouterContact) {
        btnAjouterContact.addEventListener('click', function() {
            afficherModalContact();
        });
    }
}

function afficherModalContact(contactId = null) {
    const modal = creerModal('Ajouter un contact d\'urgence', `
        <form id="formContact">
            <input type="hidden" name="action" value="${contactId ? 'modifier' : 'ajouter'}">
            ${contactId ? `<input type="hidden" name="id" value="${contactId}">` : ''}
            <input type="hidden" name="etudiant_id" value="${document.querySelector('[name="etudiant_id"]').value}">

            <div class="form-group">
                <label class="form-label">Nom complet *</label>
                <input type="text" name="nom_complet" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Relation *</label>
                <select name="relation" class="form-control" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="pere">Père</option>
                    <option value="mere">Mère</option>
                    <option value="conjoint">Conjoint(e)</option>
                    <option value="frere_soeur">Frère/Sœur</option>
                    <option value="tuteur">Tuteur légal</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Téléphone principal *</label>
                    <input type="tel" name="telephone_principal" class="form-control" required placeholder="+216 XX XXX XXX">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone secondaire</label>
                    <input type="tel" name="telephone_secondaire" class="form-control" placeholder="+216 XX XXX XXX">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Adresse</label>
                <textarea name="adresse" class="form-control" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="est_contact_principal" value="1" class="form-check-input">
                    <span class="form-check-label">Définir comme contact principal</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="fermerModal()">Annuler</button>
            </div>
        </form>
    `);

    document.body.appendChild(modal);

    // Gérer la soumission
    document.getElementById('formContact').addEventListener('submit', function(e) {
        e.preventDefault();
        soumettreContact(new FormData(this));
    });
}

function soumettreContact(formData) {
    fetch('api/gerer_contacts_urgence.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fermerModal();
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    });
}

function modifierContact(id) {
    // TODO: Charger les données et afficher le modal pré-rempli
    afficherModalContact(id);
}

function supprimerContact(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce contact d\'urgence ?')) {
        return;
    }

    fetch(`api/gerer_contacts_urgence.php?action=supprimer&id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion au serveur');
    });
}

/**
 * Créer un modal générique
 */
function creerModal(titre, contenu) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${titre}</h3>
                <button type="button" class="close" onclick="fermerModal()">&times;</button>
            </div>
            <div class="modal-body">
                ${contenu}
            </div>
        </div>
    `;

    // Fermer en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fermerModal();
        }
    });

    return modal;
}

/**
 * Fermer le modal
 */
function fermerModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}
