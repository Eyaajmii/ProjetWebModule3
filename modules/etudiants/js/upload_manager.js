/**
 * JavaScript - Gestionnaire d'upload de documents
 * Partie 3.3 - Téléchargement de documents
 *
 * @author AJMI Eya, JLASSI MARIEM
 * @version 1.0
 */

// Constantes
const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
const MAX_SIZE = 5 * 1024 * 1024; // 5MB

document.addEventListener('DOMContentLoaded', function() {
    initUploadZone();
    initUploadForm();
});

/**
 * Initialiser la zone d'upload (drag & drop)
 */
function initUploadZone() {
    const uploadZone = document.getElementById('uploadZone');
    const fichierInput = document.getElementById('fichierInput');

    if (!uploadZone || !fichierInput) return;

    // Drag & Drop
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fichierInput.files = files;
            handleFileSelect(files[0]);
        }
    });

    // Changement de fichier via input
    fichierInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    // Bouton de suppression
    const btnRemoveFile = document.getElementById('btnRemoveFile');
    if (btnRemoveFile) {
        btnRemoveFile.addEventListener('click', function() {
            resetFileInput();
        });
    }
}

/**
 * Gérer la sélection d'un fichier
 */
function handleFileSelect(file) {
    // Valider le fichier
    const errors = validateFile(file);

    if (errors.length > 0) {
        // Afficher le message d'erreur AVANT de reset
        afficherMessageUpload(errors.join('<br>'), 'error');

        // Reset après un délai pour que l'utilisateur voie le message
        setTimeout(() => {
            resetFileInput();
        }, 100);
        return;
    }

    // Afficher la prévisualisation
    afficherPreview(file);

    // Masquer le message d'erreur (fichier valide)
    const uploadMessage = document.getElementById('uploadMessage');
    uploadMessage.innerHTML = '';
    uploadMessage.className = 'upload-message';
}

/**
 * Valider un fichier
 */
function validateFile(file) {
    const errors = [];

    // Vérifier le type
    if (!ALLOWED_TYPES.includes(file.type)) {
        errors.push(`Format non autorisé (${file.type}). Formats acceptés : PDF, JPG, PNG`);
    }

    // Vérifier l'extension
    const extension = file.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXTENSIONS.includes(extension)) {
        errors.push(`Extension non autorisée (.${extension}). Extensions acceptées : ${ALLOWED_EXTENSIONS.join(', ')}`);
    }

    // Vérifier la taille
    if (file.size > MAX_SIZE) {
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        const maxMB = (MAX_SIZE / 1024 / 1024).toFixed(2);
        errors.push(`Fichier trop volumineux (${sizeMB} MB). Maximum autorisé : ${maxMB} MB`);
    }

    return errors;
}

/**
 * Afficher la prévisualisation du fichier
 */
function afficherPreview(file) {
    const preview = document.getElementById('filePreview');
    const previewIcon = document.getElementById('previewIcon');
    const previewName = document.getElementById('previewName');
    const previewSize = document.getElementById('previewSize');

    // Déterminer l'icône
    const extension = file.name.split('.').pop().toLowerCase();
    let icon = '📄';

    if (extension === 'pdf') {
        icon = '📄';
    } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
        icon = '🖼️';
    }

    previewIcon.textContent = icon;
    previewName.textContent = file.name;
    previewSize.textContent = formaterTaille(file.size);

    preview.style.display = 'flex';
}

/**
 * Réinitialiser l'input de fichier
 */
function resetFileInput(clearMessage = false) {
    const fichierInput = document.getElementById('fichierInput');
    const preview = document.getElementById('filePreview');

    fichierInput.value = '';
    preview.style.display = 'none';

    // Réinitialiser le message seulement si demandé
    if (clearMessage) {
        document.getElementById('uploadMessage').innerHTML = '';
        document.getElementById('uploadMessage').className = 'upload-message';
    }
}

/**
 * Initialiser le formulaire d'upload
 */
function initUploadForm() {
    const form = document.getElementById('formUploadDocument');

    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const fichierInput = document.getElementById('fichierInput');
        const typeDocument = document.getElementById('typeDocument');

        // Validation
        if (!fichierInput.files.length) {
            afficherMessageUpload('Veuillez sélectionner un fichier', 'error');
            return;
        }

        if (!typeDocument.value) {
            afficherMessageUpload('Veuillez sélectionner un type de document', 'error');
            typeDocument.focus();
            return;
        }

        // Valider le fichier
        const errors = validateFile(fichierInput.files[0]);
        if (errors.length > 0) {
            afficherMessageUpload(errors.join('<br>'), 'error');
            return;
        }

        // Soumettre le formulaire
        uploadDocument(new FormData(form));
    });

    // Réinitialiser le formulaire
    form.addEventListener('reset', function() {
        resetFileInput(true); // Effacer aussi le message lors d'un reset manuel
        document.getElementById('typeDocument').value = '';
    });
}

/**
 * Uploader le document via AJAX
 */
function uploadDocument(formData) {
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const btnUpload = document.getElementById('btnUpload');

    // Désactiver le bouton
    btnUpload.disabled = true;
    btnUpload.innerHTML = '<span class="spinner"></span> Téléchargement...';

    // Afficher la barre de progression
    uploadProgress.style.display = 'block';
    progressBar.style.width = '0%';

    // Créer une requête XMLHttpRequest pour suivre la progression
    const xhr = new XMLHttpRequest();

    // Progression de l'upload
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
        }
    });

    // Réponse du serveur
    xhr.addEventListener('load', function() {
        console.log('Response status:', xhr.status);
        console.log('Response text:', xhr.responseText);

        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                console.log('Parsed data:', data);

                if (data.success) {
                    // Utiliser le message retourné par l'API
                    afficherMessageUpload(data.message || 'Document téléchargé avec succès !', 'success');

                    // Réinitialiser le formulaire
                    document.getElementById('formUploadDocument').reset();
                    resetFileInput(true); // Effacer aussi le message (succès)

                    // Recharger la page après 2 secondes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    afficherMessageUpload(data.message || 'Une erreur est survenue', 'error');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.log('Raw response:', xhr.responseText);
                afficherMessageUpload('Erreur lors du traitement de la réponse: ' + e.message, 'error');
            }
        } else {
            afficherMessageUpload(`Erreur de connexion au serveur (Status: ${xhr.status})`, 'error');
        }

        // Réactiver le bouton
        btnUpload.disabled = false;
        btnUpload.textContent = 'Télécharger le document';

        // Masquer la barre de progression
        setTimeout(() => {
            uploadProgress.style.display = 'none';
        }, 1000);
    });

    // Erreur réseau
    xhr.addEventListener('error', function() {
        afficherMessageUpload('Erreur de connexion au serveur', 'error');
        btnUpload.disabled = false;
        btnUpload.textContent = 'Télécharger le document';
        uploadProgress.style.display = 'none';
    });

    // Envoyer la requête
    xhr.open('POST', 'api/uploader_document.php');
    xhr.send(formData);
}

/**
 * Afficher un message d'upload
 */
function afficherMessageUpload(message, type) {
    const uploadMessage = document.getElementById('uploadMessage');

    uploadMessage.innerHTML = message;
    uploadMessage.className = 'upload-message ' + type;
    uploadMessage.style.display = 'block';

    // Masquer après 5 secondes si succès
    if (type === 'success') {
        setTimeout(() => {
            uploadMessage.style.display = 'none';
        }, 5000);
    }
}

/**
 * Formater la taille d'un fichier
 */
function formaterTaille(bytes) {
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return bytes + ' octets';
}

/**
 * Supprimer un document
 */
function supprimerDocument(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
        return;
    }

    fetch(`api/supprimer_document.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Document supprimé avec succès');
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
