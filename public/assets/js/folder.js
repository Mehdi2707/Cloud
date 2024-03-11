$('#folder_form_name_submit').on("click", function ()
{
    var folderName = $('#folder_form_name').val();
    var formData = new FormData();

    formData.append('folderName', folderName);

    $.ajax(
    {
        url: '/folder',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        error: function (data)
        {
            alert(data.responseJSON.message);
        },
        success: function (data)
        {
            $('.file-list').append(
                '<div class="card m-2" style="width: 17rem;">' +
                '<div class="card-body">' +
                '<h5 class="card-title">' + data.folderName + '</h5>' +
                '<a href="/viewFolder/' + data.folderName + '" class="btn btn-outline-dark btn-sm" style="margin-right: 5px;">Ouvrir</a>' +
                '<a href="/renameFolder/' + data.folderName + '" class="btn btn-dark btn-sm" style="margin-right: 5px;">Renommer</a>' +
                '<a href="/deleteFolder/' + data.folderName + '" class="btn btn-danger btn-sm">Supprimer</a>' +
                '</div>' +
                '</div>'
            );
        }
    });
});

$('.renameFolder').on("click", function () {
    var button = $(this);
    var oldFolderName = $(this).data('folder');
    var parentCard = $(this).closest(".card");
    var cardTitle = parentCard.find(".card-title");

    if ($(this).text() === "Confirmer") {
        var newFolderName = parentCard.find("input").val();

        $.ajax({
            url: '/renameFolder',
            method: 'POST',
            data: { oldFolderName: oldFolderName, newFolderName: newFolderName },
            success: function (data) {
                cardTitle.text(data.newFolderName);
                button.text("Renommer");

                var newTitle = $("<h5>").addClass("card-title").text(data.newFolderName);
                parentCard.find("input").replaceWith(newTitle);

                parentCard.find("a").each(function () {
                    var href = $(this).attr("href").replace(oldFolderName, newFolderName);
                    $(this).attr("href", href);

                    if ($(this).data("folder") === oldFolderName) {
                        $(this).data("folder", newFolderName);
                    }
                });
            },
            error: function (error) {
                alert(error.responseJSON.message);
            }
        });
    } else {
        $(this).text("Confirmer");

        var inputElement = $("<input>").attr("type", "text").val(cardTitle.text());
        cardTitle.replaceWith(inputElement);
    }
});