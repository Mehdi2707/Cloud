import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    submit(event) {
        event.preventDefault();

        let folderName = this.element.querySelector("#folder_form_name").value;
        let formData = new FormData();
        formData.append('folderName', folderName);

        fetch('/folder', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.message);
                }

                let input = this.element.querySelector('input');
                input.classList.remove("is-invalid");
                input.classList.add("is-valid");
                input.removeAttribute("aria-describedby");
                $('.valid-feedback').text("Dossier créé.");

                $('.file-list').append(
                    '<div class="card m-2" style="width: 17rem;">' +
                    '<div class="card-body">' +
                    '<h5 class="card-title">' + data.folderName + '</h5>' +
                    '<a href="/viewFolder/' + data.folderName + '" class="btn btn-outline-dark btn-sm" style="margin-right: 5px;">Ouvrir</a>' +
                    '<a data-controller="rename-folder" data-action="click->rename-folder#next" href="#" data-folder="' + data.folderName + '" class="btn btn-dark btn-sm" style="margin-right: 5px;">Renommer</a>' +
                    '<a href="/deleteFolder/' + data.folderName + '" class="btn btn-danger btn-sm">Supprimer</a>' +
                    '</div>' +
                    '</div>'
                );
            })
            .catch(error => {
                let input = this.element.querySelector('input');
                input.classList.remove("is-valid");
                input.classList.add("is-invalid");
                input.setAttribute("aria-describedby", "invalid");
                $('.invalid-feedback').text(error.message);
            });
    }
}
