// On attend que tout le contenu de la page soit chargé avant d'exécuter le script
document.addEventListener('DOMContentLoaded', function() {

    // --- ÉTAPE 1: Récupération des éléments et des données ---

    const container = document.getElementById('house-plan-container');
    const tooltip = document.getElementById('object-tooltip');

    // Si on ne trouve pas le conteneur, on arrête tout pour éviter des erreurs
    if (!container) {
        console.error("Le conteneur #house-plan-container n'a pas été trouvé.");
        return;
    }

    // On récupère la chaîne de caractères JSON depuis l'attribut data-zones
    const zonesDataString = container.dataset.zones;
    let zonesData;

    try {
        // On transforme la chaîne JSON en un véritable objet JavaScript
        zonesData = JSON.parse(zonesDataString);
    } catch (e) {
        console.error("Erreur lors de l'analyse des données JSON :", e);
        // On arrête le script si les données sont invalides
        return;
    }

    // --- ÉTAPE 2: Mise en place de l'interactivité sur les zones ---

    const zones = document.querySelectorAll('.interactive-zone');

    zones.forEach(zoneElement => {
        const zoneKey = zoneElement.dataset.zoneKey;
        const data = zonesData[zoneKey];

        // Si aucune donnée n'existe pour cette zone, on passe à la suivante
        if (!data || data.objets.length === 0) {
            zoneElement.style.cursor = 'default'; // Optionnel : change le curseur si la zone est vide
            return;
        }

        // Événement: La souris entre dans la zone
        zoneElement.addEventListener('mouseover', function() {
            // Création du contenu HTML riche pour le tooltip
            const titre = zoneKey.charAt(0).toUpperCase() + zoneKey.slice(1);
            const actifsText = data.actifs > 0 ? `<div class="tooltip-alert">${data.actifs} objet(s) actif(s)</div>` : '';

            // On boucle sur chaque objet pour créer sa fiche détaillée
            const objetsHtml = data.objets.map(objet => {
                const etatClass = objet.etat.toLowerCase() === 'actif' ? 'etat-actif' : 'etat-inactif';
                
                // On boucle sur les paramètres de l'objet
                const paramsHtml = objet.parametres.map(p => `<li><strong>${p.nom}:</strong> ${p.valeur}</li>`).join('');

                return `
                    <div class="tooltip-objet">
                        <div class="tooltip-objet-header">
                            <h5>${objet.nom} <span class="etat ${etatClass}">(${objet.etat})</span></h5>
                            <small>${objet.marque}</small>
                        </div>
                        <p class="tooltip-description">${objet.description}</p>
                        ${paramsHtml ? `<ul class="tooltip-params">${paramsHtml}</ul>` : ''}
                    </div>
                `;
            }).join('');

            tooltip.innerHTML = `
                <div class="tooltip-header">
                    <h3>Zone ${titre}</h3>
                    ${actifsText}
                </div>
                ${objetsHtml}
            `;
            tooltip.style.display = 'block';
        });

        // Événement: La souris quitte la zone
        zoneElement.addEventListener('mouseout', function() {
            tooltip.style.display = 'none';
        });

        // Événement: Clic sur la zone
        zoneElement.addEventListener('click', function() {
            window.location.href = `objets.php?zone=${zoneKey}`;
        });
    });

    // On fait suivre le tooltip du curseur pour une meilleure expérience
    container.addEventListener('mousemove', function(event) {
        if (tooltip.style.display === 'block') {
            tooltip.style.left = (event.pageX + 20) + 'px';
            tooltip.style.top = (event.pageY + 20) + 'px';
        }
    });
});