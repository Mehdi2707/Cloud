import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    next(event) {
        event.preventDefault();

        let button = this.element;
        let oldFolderName = this.element.dataset.folder;
        let parentCard = this.element.closest(".card");
        let cardTitle = parentCard.querySelector(".card-title");

        let formData = new FormData();
        formData.append('oldFolderName', oldFolderName);

        if (button.textContent === "Confirmer") {
            let newFolderName = parentCard.querySelector("input").value;
            formData.append('newFolderName', newFolderName);

            fetch('/renameFolder', {
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

                    // Mettre à jour le texte du titre avec le nouveau nom de dossier
                    cardTitle.textContent = data.newFolderName;

                    // Rétablir le bouton à son état initial
                    button.textContent = "Renommer";

                    let inputElement = parentCard.querySelector("input");
                    if (inputElement) {
                        inputElement.remove();
                    }

                    // Mettre à jour les liens avec le nouveau nom de dossier
                    parentCard.querySelectorAll("a").forEach(a => {
                        let href = a.getAttribute("href").replace(oldFolderName, newFolderName);
                        a.setAttribute("href", href);

                        if (a.dataset.folder === oldFolderName) {
                            a.dataset.folder = newFolderName;
                        }
                    });
                })
                .catch(error => {
                    console.error(error);
                    alert(error.message);
                });
        } else {
            // Changer le texte du bouton pour "Confirmer"
            button.textContent = "Confirmer";

            // Créer un input avec la valeur actuelle du titre
            let inputElement = document.createElement("input");
            inputElement.setAttribute("type", "text");
            inputElement.value = cardTitle.textContent;

            // Remplacer le texte du titre par l'input
            cardTitle.textContent = '';
            cardTitle.appendChild(inputElement);
        }
    }
}
