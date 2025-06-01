let monBoutonMagique = document.getElementById("boutonMagique");
let maZoneMessage = document.getElementById("zoneMessage");

if (monBoutonMagique && maZoneMessage) {
    monBoutonMagique.addEventListener('click', function() {
        maZoneMessage.textContent = "Vous avez cliqué sur le bouton magique !";
        maZoneMessage.style.color = "blue";
        maZoneMessage.style.fontSize = "20px";
    }
    );
} else {
    console.error("Impossible de trouver le boutonMagique ou la zoneMessage.");
}