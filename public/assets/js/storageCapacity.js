var $editButton = $('#edit-storage');

// Gestionnaire de clic sur le bouton
$editButton.on('click', function() {
    // Récupérez la nouvelle valeur
    var newValue = $('#storageValue').val();

    // Vous pouvez maintenant effectuer une requête AJAX pour envoyer la valeur au serveur
    $.ajax({
        type: 'POST', // Ou 'GET' selon votre configuration
        url: '/admin/storage/modify', // Remplacez par l'URL de votre route de traitement
        data: { storageValue: newValue }, // Les données que vous voulez envoyer
        success: function(response) {
            // Traitement en cas de succès
            alert(response.message);
            $editButton.text('Modifier');
        },
        error: function() {
            // Traitement en cas d'erreur
            alert('Une erreur est survenue lors de l\'envoi de la valeur.');
        }
    });
});