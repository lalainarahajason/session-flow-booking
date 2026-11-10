---
name: wp-performance
description: Vérifier les performances WordPress lors des changements de hooks, scripts/styles, requêtes, rendus, tâches et caches. Imposer des assets conditionnels et un usage maîtrisé des Transients sans compromettre les invariants de réservation.
---

# Performances WordPress

## Chargement conditionnel des assets

- Inventorier chaque handle JS/CSS : enregistrement, dependencies, version, hook, pages concernées et coût. Réutiliser les mécanismes WordPress, sans doublons de bibliothèques ni balises script injectées partout.
- En administration, filtrer par écran/`hook_suffix` vérifié ; ne pas charger tout le plugin sur chaque page wp-admin.
- En frontend, charger les assets uniquement lorsque le bloc, shortcode ou composant concerné est réellement présent. Vérifier les rendus dynamiques et les templates : un simple test has_shortcode sur le contenu principal peut manquer des usages.
- Enregistrer suffisamment tôt pour que l'enqueue conditionnel fonctionne ; ne pas supposer qu'un style demandé après wp_head sera présent sans stratégie testée. Documenter le mécanisme retenu pour blocs et shortcodes.
- Séparer les bundles admin/public, protéger les dépendances et l'ordre d'exécution. Utiliser defer/async uniquement lorsque le graphe et la version WordPress supportée le permettent.
- Ne pas envoyer secrets, listes clients ou paramètres inutiles via données localisées dans le JavaScript public. Éviter les règles CSS globales perturbant le thème ou WordPress.

## Transients : règles obligatoires

Utiliser les API `get_transient()`, `set_transient()` et `delete_transient()` pour les caches temporaires pertinents du plugin. Toute décision d'absence de cache doit être justifiée par la faible charge, le caractère privé ou les contraintes de fraîcheur ; ne pas ajouter un cache dangereux pour respecter artificiellement une checklist.

Pour chaque cache, documenter source, clé, portée, TTL, événements d'invalidation et reconstruction :

- Clé préfixée et bornée, incluant site, version de schéma/cache, paramètres et langue nécessaires ; inclure identité/portée lorsque des données autorisées dépendent du lecteur.
- TTL fini. Traiter strictement `false === get_transient(...)` comme cache miss ; préserver les résultats vides ou zéro via un format adapté.
- Reconstruire si le transient disparaît avant son expiration. Ne pas lire/écrire directement les lignes `_transient_*` ni supposer un stockage SQL ; tester avec et sans cache objet persistant.
- Invalider après commit des changements concernés, pas avant. Éviter qu'un calcul concurrent republie une ancienne valeur sous une nouvelle révision ; associer résultat et révision source.
- Aucun transient comme autorité des disponibilités, du nombre de places, des permissions, des montants dus ou de la déduplication financière. La validation finale revient toujours à la base sous transaction.
- Aucun verrou critique basé uniquement sur un transient : son expiration/éviction ne garantit ni exclusion mutuelle ni atomicité.
- Ne pas stocker de secrets ou dossiers clients complets dans un cache partagé. Réévaluer les droits à la lecture ; protéger aussi contre les caches HTTP/pages/CDN.
- Ne pas utiliser `set_site_transient()` pour une donnée propre à un seul site ; sa portée réseau exige une justification explicite.

## Requêtes et traitements

Vérifier pagination, index et nombre de requêtes, notamment les boucles de participants/sessions. Éviter scans complets et appels HTTP synchrones dans les hooks fréquents. Préférer les jobs bornés et reprenables pour exports, emails et traitements lourds ; ne pas confondre asynchronisme et garantie de livraison.

Mesurer avant/après sur un jeu comparable : temps, nombre de requêtes, mémoire, taille des assets et taux d'erreur. Comparer cache froid/chaud. Appliquer NFR-08 du PRD lorsqu'il est concerné ; ne pas inventer un gain ou un percentile depuis une seule requête.

## Recette obligatoire

- Page sans Booking et écran admin étranger : aucun asset propre au plugin chargé sans nécessité documentée.
- Page avec shortcode, bloc et rendu dynamique : formulaire fonctionnel, assets présents une seule fois, pas d'erreur console.
- Cache absent, expiré, invalidé et évincé : résultat correct, reconstruction possible, aucune erreur fatale.
- Modification de tarif/session : données rafraîchies ; une ancienne valeur affichée ne permet jamais une réservation invalide.
- Deux utilisateurs de droits différents et deux contextes de site : aucune fuite via clé de cache ou réponse HTTP.
- Exécuter les tests de comportement affectés via test-runner ; fournir mesures et limites.

Référence : https://developer.wordpress.org/apis/transients/
