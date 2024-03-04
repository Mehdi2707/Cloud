$('#uploaded_files_form_name').change(function ()
{
    var files = $(this)[0].files;
    var maxFileSize = 2147483648; // Taille maximale autorisée en octets (2 Go).

    for (var i = 0; i < files.length; i++) {
        if (files[i].size > maxFileSize) {
            alert('Le fichier ' + files[i].name + ' est trop volumineux. La taille maximale autorisée est de 2 Go.');
            return;
        }
    }

    var formData = new FormData();

    for (var i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    $.ajax(
    {
        url: '/upload',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function ()
        {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (e)
            {
                if (e.lengthComputable)
                {
                    $('#progress-upload').removeClass('bg-success bg-danger').addClass('progress-bar-striped progress-bar-animated');
                    var percent = Math.round((e.loaded / e.total) * 100);

                    if (percent === 100)
                        percent = 99;

                    $('#progress-upload').width(percent + '%');
                    $('#progress-upload').text(percent + '%');
                    $('#progress-upload').attr('aria-valuenow', percent);
                }
            });
            return xhr;
        },
        error: function (data)
        {
            alert(data.responseJSON.message);
            $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-danger');
            $('#progress-upload').width('100%');
            $('#progress-upload').text(data.responseJSON.message);
        },
        success: function (data)
        {
            $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-success');
            $('#progress-upload').width('100%');
            $('#progress-upload').text('Terminé');

            data.forEach(function(file)
            {
                if (file.originalFilename.length > 20)
                    file.originalFilename = file.originalFilename.slice(0, 20) + '...';

                $('.file-list').append(
                    '<div class="card m-2" style="width: 16rem;">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title">' + file.originalFilename + '</h5>' +
                    '<a href="/view/' + file.newFilename + '" class="btn btn-outline-dark btn-sm" style="margin-right: 5px;">Voir</a>' +
                    '<a href="/download/' + file.newFilename + '" class="btn btn-dark btn-sm" style="margin-right: 5px;">Télécharger</a>' +
                    '<a href="/delete/' + file.newFilename + '" class="btn btn-danger btn-sm">Supprimer</a>' +
                    '</div>' +
                    '</div>'
                );
            });
        }
    });
});