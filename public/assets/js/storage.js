var $storageMax = $('#storageMax');
var $storageValueBadge = $('#storageValueBadge');
var $storageValueText = $('#storageValueText');
var $submitButton = $('#submitButton');
var $username = $('#dataUsername').text();

// Utilisez l'événement "input" au lieu de "change"
$storageMax.on('input', function() {
    var newValue = $storageMax.val();
    $storageValueBadge.text(newValue);
    $storageValueText.text(newValue);
    $submitButton.show();
});

// Gestionnaire de clic sur le bouton
$submitButton.on('click', function() {
    // Récupérez la nouvelle valeur
    var newValue = $storageMax.val();

    // Vous pouvez maintenant effectuer une requête AJAX pour envoyer la valeur au serveur
    $.ajax({
        type: 'POST', // Ou 'GET' selon votre configuration
        url: '/admin/user/storage', // Remplacez par l'URL de votre route de traitement
        data: { storageValue: newValue, username: $username }, // Les données que vous voulez envoyer
        success: function(response) {
            // Traitement en cas de succès
            alert(response.message);
            $submitButton.hide();
        },
        error: function() {
            // Traitement en cas d'erreur
            alert('Une erreur est survenue lors de l\'envoi de la valeur.');
        }
    });
});