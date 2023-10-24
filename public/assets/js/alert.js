let linksOn = document.querySelectorAll("[data-alert-on]");
let linksOff = document.querySelectorAll("[data-alert-off]");

for(let link of linksOn)
{
    link.addEventListener("click", function(e){
        e.preventDefault();
        if(confirm("Êtes-vous sûr d'autoriser l'accès à la plateforme pour cet utilisateur ?"))
            window.location.href = this.getAttribute("href");
    })
}

for(let link of linksOff)
{
    link.addEventListener("click", function(e){
        e.preventDefault();
        if(confirm("Êtes-vous sûr de supprimer l'accès à la plateforme pour cet utilisateur ?"))
            window.location.href = this.getAttribute("href");
    })
}