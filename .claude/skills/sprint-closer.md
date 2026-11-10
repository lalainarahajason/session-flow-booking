---
name: sprint-closer
description: Préparer la clôture d'un sprint de session-flow-bookings en contrôlant critères d'acceptation, PHPDoc, audits et tests, en mettant à jour CHANGELOG.md et en rédigeant un bilan traçable. Ne jamais déclarer terminé un sprint non vérifié.
---

# Clôture de sprint

## Identifier ce qui doit être livré

Lire AGENTS.md, le plan de développement et le périmètre du sprint actif. Examiner le diff depuis la référence du sprint si le dépôt permet de l'identifier. Sans référence fiable, expliciter le périmètre disponible ; ne pas inventer les changements ou la preuve d'une livraison.

Établir la matrice : critère Sx-A / exigence PRD → changement → test/preuve → statut. Séparer livré, partiel, non livré et non vérifié. Une fonction hors sprint n'annule pas l'absence d'une fonction obligatoire.

## Documentation PHP obligatoire

- Vérifier les PHPDoc des classes, interfaces, fonctions, méthodes et hooks nouveaux ou modifiés selon WPCS et les conventions du projet ; inclure leur responsabilité et les contrats utiles.
- Contrôler les `@param`, `@return` et `@throws` pertinents contre signatures et comportement réel ; documenter formes de tableaux, nullabilité, unités, fuseaux et montants lorsque nécessaires.
- Pour les hooks publics, documenter nom, moment d'exécution, arguments et effets attendus. Ne pas inventer un `@since` correspondant à une version non décidée.
- Éviter les commentaires trompeurs ou la répétition mécanique d'une signature sans explication du contrat. L'absence de PHPDoc requis sur le code livré est un écart de clôture.

## Exécuter les contrôles de sortie

Appliquer architecture-guard et audit-wp ; appliquer db-migration et wp-performance selon le diff ; appliquer test-runner. Vérifier les tests de régression des sprints précédents et les cas manuels prescrits par le sprint courant.

Vérifier migrations, contrats et documentation opérateur pour les fonctionnalités réellement livrées. Ne pas remplacer un test manquant par une capture ni déclarer des critères satisfaits sur la seule base du code écrit.

## Porte de sortie WordPress.org

Ces vérifications sont bloquantes pour chaque clôture livrant un incrément du plugin. La préparation documentaire seule ne simule pas une clôture. Vérifier le paquet réellement distribuable et les sources correspondantes, pas seulement la branche de travail.

1. **Identité et versions :** nom/slug/text domain `session-flow-bookings`, fichier principal `session-flow-bookings.php`. Exiger l'égalité exacte de l'en-tête `Version` avec `Stable tag` dans `readme.txt`, et avec toute constante de version runtime. `trunk`, valeur absente ou divergente bloque notre clôture. Ne jamais utiliser la version du PRD ou d'OpenAPI comme version du plugin. Comparer aussi les en-têtes de licence. Voir [le fonctionnement du readme](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).
2. **Sources :** tous les JS/CSS compilés ou minifiés doivent avoir leurs sources lisibles incluses dans le paquet, à côté ou dans un dossier source clairement associé, avec instructions de compilation et dépendances verrouillées. Une sourcemap seule ne suffit pas. Les règles WordPress.org autorisent certaines références à un dépôt source ; notre exigence locale impose en plus la présence des sources dans le paquet. Vérifier que `.distignore` ne les retire pas ; aucun fichier compilé signifie non applicable, avec inventaire à l'appui.
3. **Services tiers :** dans `readme.txt`, documenter chaque service réellement utilisé, notamment Stripe dès son intégration : finalité, données transmises, circonstances et déclenchement des appels, compte/activation requis, liens vers conditions et confidentialité. Aucun secret dans le readme. Une intégration future est identifiée comme non livrée, jamais présentée comme active. Vérifier le code et les dépendances, pas seulement la mention « Stripe ».
4. **Licence :** choix projet GPLv2 ou ultérieure (`GPL-2.0-or-later`). Inventorier licences et provenance du code, des dépendances distribuées, polices, images et autres assets. Inclure `LICENSE` et les notices requises ; toute licence inconnue ou incompatible bloque la distribution. Les directives officielles exigent une licence compatible GPL et recommandent GPLv2+ ; notre choix est explicitement GPLv2+. Vérifier la compatibilité de chaque composant, pas seulement l'en-tête du plugin.
5. **Plugin Check :** exécuter les commandes de [la procédure du projet](../../docs/wordpress-org.md) après les dernières modifications pertinentes, y compris celles du readme et du paquet. Rapporter version de Plugin Check, WordPress/PHP/BDD, cible, commande, sortie complète et code de retour. Un résultat manquant/échoué ou un avertissement non traité interdit la clôture. Le contrôle ne remplace ni PHPUnit, ni WPCS, ni la revue manuelle et ne constitue pas une approbation WordPress.org.

Références officielles : [directives](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/), [problèmes fréquents](https://developer.wordpress.org/plugins/wordpress-org/common-issues/), [Plugin Check](https://github.com/WordPress/plugin-check).

## CHANGELOG.md

- Créer `CHANGELOG.md` à la racine s'il manque, ou conserver sa structure existante.
- Ajouter dans la section non publiée/Unreleased les changements effectifs, groupés si pertinent : Added, Changed, Fixed, Security, Deprecated, Removed.
- Décrire l'effet utilisateur, les migrations et changements incompatibles ; ne pas annoncer des fonctions seulement planifiées. Éviter secrets, données clients et détails exploitables inutiles.
- Ne pas publier une version, créer un tag, fusionner une branche ou déployer simplement parce qu'un bilan est demandé. Une version/date de sortie doit correspondre à une décision et une livraison réelles.

## Bilan à enregistrer

Créer ou mettre à jour `specs/sprints/sprint-N-bilan.md` pour le numéro de sprint identifié, sans écraser les preuves précédentes. Inclure :

1. Objectif, périmètre et référence de code examinée.
2. Livrables réels et critères d'acceptation, chacun avec preuve et statut.
3. Commandes de test, environnement, résultats, cas skipped/incomplete et recette manuelle.
4. Résultats des audits WPCS/sécurité/architecture/BDD/performance, Plugin Check et porte WordPress.org (versions, sources, services tiers, licences), avec contrôles non applicables justifiés.
5. Migrations, changements de contrat et documentation/PHPDoc/CHANGELOG mis à jour.
6. Anomalies restantes, priorité, impact, responsable si connu et prochaine action.
7. Décision : « prêt pour validation », « bloqué » ou « clôturé avec preuves ». Ne pas inventer un accord du porteur du projet.

Aucun critère obligatoire échoué ou non vérifié ne permet de passer au sprint suivant. Si l'environnement empêche les tests, préparer le bilan avec statut bloqué et les étapes précises de reprise, sans marquer le sprint terminé. La clôture V1 exige la recette finale du PRD, pas seulement les tests du dernier ticket.
