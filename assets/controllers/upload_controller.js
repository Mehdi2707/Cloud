import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    next(event) {
        event.preventDefault();

        var files = this.element.files;
        var folder = this.element.dataset.folder;
        var maxFileSize = 2147483648; // Taille maximale autorisée en octets (2 Go).
        var csrfToken = document.querySelector('input[name="csrf-token"]').value;

        for (var i = 0; i < files.length; i++) {
            if (files[i].size > maxFileSize) {
                $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-danger');
                $('#progress-upload').width('100%');
                $('#progress-upload').text('La taille maximale autorisée est de 2 Go.');
                return;
            }
        }

        var formData = new FormData();

        formData.append('csrf-token', csrfToken);

        if(folder)
            formData.append('folderName', folder);

        for (var i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/upload');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                $('#progress-upload').removeClass('bg-success bg-danger').addClass('progress-bar-striped progress-bar-animated');
                var percent = Math.round((e.loaded / e.total) * 100);

                if (percent === 100) {
                    percent = 99;
                }

                $('#progress-upload').width(percent + '%');
                $('#progress-upload').text(percent + '%');
                $('#progress-upload').attr('aria-valuenow', percent);
            }
        });

        xhr.onload = function() {
            var data = JSON.parse(xhr.responseText);
            if (data.success === false) {
                $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-danger');
                $('#progress-upload').width('100%');
                $('#progress-upload').text(data.message);
                return;
            }

            $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-success');
            $('#progress-upload').width('100%');
            $('#progress-upload').text('Terminé');

            data.forEach(function(file)
            {
                if (file.originalFilename.length > 20)
                    file.originalFilename = file.originalFilename.slice(0, 20) + '...';

                let query = "";

                if(folder)
                    query = "?folder=" + folder;

                $('.file-list').append(
                    '<div class="card m-2" style="width: 16rem;">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title">' + file.originalFilename + '</h5>' +
                    '<a href="/view/' + file.newFilename + query + '" class="btn btn-outline-dark btn-sm" style="margin-right: 5px;">Voir</a>' +
                    '<a href="/download/' + file.newFilename + query + '" class="btn btn-dark btn-sm" style="margin-right: 5px;">Télécharger</a>' +
                    '<a href="/delete/' + file.newFilename + query + '" class="btn btn-danger btn-sm">Supprimer</a>' +
                    '</div>' +
                    '</div>'
                );
            });
        };

        xhr.onerror = function() {
            $('#progress-upload').removeClass('progress-bar-striped progress-bar-animated').addClass('bg-danger');
            $('#progress-upload').width('100%');
            $('#progress-upload').text(xhr.statusText);
        };

        xhr.send(formData);
    }
}
