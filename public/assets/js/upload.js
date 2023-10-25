// script.js
const fileInput = document.getElementById('uploaded_files_form_name');
const progressBar = document.getElementById('progress-bar');

fileInput.addEventListener('change', () => {
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    axios.post('/upload', formData, {
        onUploadProgress: (progressEvent) => {
            const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            progressBar.style.width = percentCompleted + '%';
        },
    })
        .then((response) => {
            if (response.data.success) {
                alert('Téléversement réussi.');
            } else {
                alert('Échec du téléversement.');
            }
        })
        .catch((error) => {
            console.error(error);
            alert('Erreur lors du téléversement.');
        });
});
