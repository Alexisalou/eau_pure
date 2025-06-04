<?php
// Démarre la session pour permettre l'utilisation de variables de session
session_start();

// Gère le token CSRF (anti-falsification de requête) si inexistant
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token sécurisé
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Formulaire de Connexion</title>
    <!-- Feuille de style pour la page de connexion -->
    <link rel="stylesheet" href="connexion.css?v=123">
</head>
<body>
    <!-- Conteneur principal du formulaire de connexion -->
    <div class="form-container">
        <form id="loginForm">
            <!-- Champ caché pour définir l'action à effectuer -->
            <input type="hidden" name="action" value="login">
            <!-- Token CSRF pour sécuriser la requête -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <label for="numero_de_telephone">Numéro de téléphone :</label>
            <input type="text" id="numero_de_telephone" name="numero_de_telephone" required pattern="[0-9]{10}" placeholder="Numéro de téléphone">

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required placeholder="Mot de passe">

            <input type="submit" value="Se connecter" class="connect-btn">

            <!-- Bouton d'accès à la gestion des utilisateurs (admin) -->
            <button type="button" class="gestion-btn nouvelle-classe" onclick="ouvrirPopup('popupAdmin')">Gestion des utilisateurs</button>
        </form>
    </div>

    <!-- Overlay sombre pour les popups -->
    <div id="overlay" class="overlay" onclick="fermerPopupTous()"></div>

    <!-- Popup de saisie du mot de passe administrateur -->
    <div id="popupAdmin" class="popup">
        <form id="adminForm">
            <span class="close" onclick="fermerPopup('popupAdmin')">&times;</span>
            <input type="hidden" name="action" value="admin">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <label for="mdp_admin">Mot de passe administrateur :</label>
            <input type="password" id="mdp_admin" name="mdp_admin" required placeholder="Mot de passe">
            <div class="button-container">
                <button type="submit">Valider</button>
                <button type="button" onclick="fermerPopup('popupAdmin')">Annuler</button>
            </div>
        </form>
    </div>

    <!-- Popup principale de gestion des utilisateurs -->
    <div id="popupGestion" class="modal-overlay">
        <div class="modal">
            <button class="close-button" onclick="fermerPopup('popupGestion')">&times;</button>
            <div class="modal-header">Gestion des utilisateurs</div>
            <div class="modal-buttons">
                <button type="button" onclick="ouvrirFormPopup('ajout')">Ajouter un utilisateur</button>
                <button type="button" onclick="ouvrirFormPopup('modifier')">Modifier un utilisateur</button>
                <button type="button" onclick="ouvrirFormPopup('supprimer')">Supprimer un utilisateur</button>
                <button type="button" class="cancel" onclick="fermerPopup('popupGestion')">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Popup pour ajout d'utilisateur -->
    <div id="popupAjout" class="modal-overlay">
        <div class="modal">
            <button class="close-button" onclick="fermerPopup('popupAjout')">&times;</button>
            <div class="modal-header">Ajouter un utilisateur</div>
            <form id="ajoutForm">
                <input type="hidden" name="action" value="ajout">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <label for="numero">Numéro de téléphone :</label>
                    <input type="text" id="numero" name="numero" required pattern="[0-9]{10}" placeholder="Numéro de téléphone">

                    <label for="conf_numero">Confirmer le numéro :</label>
                    <input type="text" id="conf_numero" name="conf_numero" required pattern="[0-9]{10}" placeholder="Confirmer le numéro">

                    <label for="passwordAjout">Mot de passe :</label>
                    <input type="password" id="passwordAjout" name="password" required placeholder="Mot de passe">

                    <label for="conf_password">Confirmer le mot de passe :</label>
                    <input type="password" id="conf_password" name="conf_password" required placeholder="Confirmer le mot de passe">
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="validate" onclick="fermerPopup('popupAjout')">Ajouter</button>
                    <button type="button" class="cancel" onclick="fermerPopup('popupAjout')">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup pour modification de mot de passe -->
    <div id="popupModifier" class="modal-overlay">
        <div class="modal">
            <button class="close-button" onclick="fermerPopup('popupModifier')">&times;</button>
            <div class="modal-header">Modifier le mot de passe</div>
            <form id="modifierForm">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <label for="num_modif">Numéro de téléphone :</label>
                    <input type="text" id="num_modif" name="numero" required pattern="[0-9]{10}" placeholder="Numéro de téléphone">

                    <label for="ancien_password">Mot de passe actuel :</label>
                    <input type="password" id="ancien_password" name="ancien_password" required placeholder="Mot de passe actuel">

                    <label for="new_password">Nouveau mot de passe :</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="Nouveau mot de passe">

                    <label for="conf_new_password">Confirmer le nouveau mot de passe :</label>
                    <input type="password" id="conf_new_password" name="conf_new_password" required placeholder="Confirmer le nouveau mot de passe">
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="validate" onclick="fermerPopup('popupModifier')">Modifier</button>
                    <button type="button" class="cancel" onclick="fermerPopup('popupModifier')">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup pour suppression d'utilisateur -->
    <div id="popupSupprimer" class="modal-overlay">
        <div class="modal">
            <button class="close-button" onclick="fermerPopup('popupSupprimer')">&times;</button>
            <div class="modal-header">Supprimer un utilisateur</div>
            <form id="supprimerForm">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <label for="num_supp">Numéro de téléphone :</label>
                    <input type="text" id="num_supp" name="numero" required pattern="[0-9]{10}" placeholder="Numéro de téléphone">

                    <label for="conf_num_supp">Confirmer le numéro :</label>
                    <input type="text" id="conf_num_supp" name="conf_numero" required pattern="[0-9]{10}" placeholder="Confirmer le numéro">

                    <label for="password_supp">Mot de passe actuel :</label>
                    <input type="password" id="password_supp" name="password" required placeholder="Mot de passe actuel">

                    <label for="conf_password_supp">Confirmer le mot de passe :</label>
                    <input type="password" id="conf_password_supp" name="conf_password" required placeholder="Confirmer le mot de passe">
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="validate" onclick="fermerPopup('popupSupprimer')">Supprimer</button>
                    <button type="button" class="cancel" onclick="fermerPopup('popupSupprimer')">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup de notification/alerte -->
    <div id="alertPopup" class="message-success">
        <span class="close-btn" onclick="fermerPopup('alertPopup')">&times;</span>
        <div id="alertMessage">Ceci est un message d'alerte.</div>
        <button type="button" class="ok-btn" onclick="fermerPopup('alertPopup')">OK</button>
    </div>

<script>
    // Ferme tous les popups lorsque la page se charge
    window.onload = function() {
        fermerPopupTous();
    };

    // Ajout des écouteurs d'événements sur chaque formulaire pour intercepter la soumission
    // Empêche le rechargement classique de la page et appelle handleFormSubmit

    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Empêche le comportement par défaut
        handleFormSubmit(this, 'données_physico_chimiques.php'); // Redirige vers une autre page si succès
    });

    document.getElementById('adminForm').addEventListener('submit', function(event) {
        event.preventDefault();
        handleFormSubmit(this, null, 'popupGestion'); // Ouvre un popup spécifique si succès
    });

    // Formulaires liés à l'ajout, la modification et la suppression d’utilisateurs ou d’éléments
    ['ajoutForm', 'modifierForm', 'supprimerForm'].forEach(id => {
        document.getElementById(id).addEventListener('submit', function(event) {
            event.preventDefault();
            handleFormSubmit(this); // Traitement standard, pas de redirection ou popup spécial
        });
    });

    /**
     * Fonction de gestion centralisée de la soumission des formulaires
     * Envoie les données en AJAX à `connexion.php`
     *
     * @param {HTMLFormElement} form - Formulaire HTML soumis
     * @param {string|null} redirectUrl - URL de redirection après succès (facultatif)
     * @param {string|null} popupId - ID du popup à afficher si nécessaire (facultatif)
     */
    function handleFormSubmit(form, redirectUrl = null, popupId = null) {
        var formData = new FormData(form); // Récupère tous les champs du formulaire
        var xhr = new XMLHttpRequest(); // Création de la requête AJAX
        xhr.open("POST", "connexion.php", true); // Envoi vers le script PHP
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText); // Analyse de la réponse JSON
                if (response.success) {
                    // Si succès, effectuer une redirection, ouvrir un popup ou afficher un message
                    if (redirectUrl) {
                        window.location.href = redirectUrl; // Redirige vers une autre page
                    } else if (popupId) {
                        fermerPopup(form.closest('.popup').id); // Ferme l'ancien popup
                        ouvrirPopup(popupId); // Ouvre le nouveau
                    } else {
                        // Affiche une alerte selon le type d'action réalisée
                        var action = form.querySelector('input[name="action"]').value;
                        var message = "L'utilisateur a bien été ";
                        switch(action) {
                            case 'ajout': message += "ajouté."; break;
                            case 'modifier': message += "modifié."; break;
                            case 'supprimer': message += "supprimé."; break;
                            default: message = response.message || "Opération réussie.";
                        }
                        ouvrirAlertPopup(message); // Affiche une alerte personnalisée
                    }
                } else {
                    // En cas d’erreur : afficher une alerte avec le message d'erreur
                    ouvrirAlertPopup(response.message || "Une erreur est survenue.");
                }
            }
        };
        xhr.send(formData); // Envoie des données du formulaire
    }

    // Affiche une popup d’alerte personnalisée avec un message
    function ouvrirAlertPopup(message) {
        document.getElementById('alertMessage').textContent = message;
        document.getElementById('alertPopup').style.display = 'flex';
    }

    // Ferme un popup spécifique (identifié par son ID)
    function fermerPopup(popupId) {
        document.body.classList.remove('flou'); // Supprime l'effet de flou de fond
        document.getElementById(popupId).style.display = "none"; // Cache le popup
        document.getElementById("overlay").style.display = "none"; // Cache le fond gris
    }

    // Ouvre un popup spécifique (identifié par son ID)
    function ouvrirPopup(popupId) {
        document.body.classList.add('flou'); // Ajoute un effet de flou à l’arrière-plan
        document.getElementById("overlay").style.display = "block"; // Affiche l'overlay
        document.getElementById(popupId).style.display = "flex"; // Affiche le popup ciblé
    }

    // Ferme tous les popups présents sur la page (générique)
    function fermerPopupTous() {
        const popups = document.querySelectorAll('.popup, .modal-overlay'); // Sélectionne tous les éléments popup
        popups.forEach(function(popup) {
            popup.style.display = 'none'; // Les rend invisibles
        });
        document.getElementById("overlay").style.display = "none"; // Cache le fond gris global
        document.body.classList.remove('flou'); // Supprime l'effet de flou général
    }

    // Ouvre dynamiquement un formulaire (ajout, modification, suppression)
    // Exemple : `ouvrirFormPopup("ajout")` ouvre "popupAjout"
    function ouvrirFormPopup(type) {
        fermerPopupTous(); // Ferme tout ce qui est déjà ouvert
        document.getElementById("overlay").style.display = "block";
        document.getElementById("popup" + type.charAt(0).toUpperCase() + type.slice(1)).style.display = "flex";
    }
</script>
</body>
</html>

