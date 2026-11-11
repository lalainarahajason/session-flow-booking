# Exécution Sprint 1

Cadrage validé explicitement dans la conversation ; commit initial autorisé ensuite.
Branche : `sprint-1`. Référence initiale : `cdf1836`.
Dépôt : https://github.com/lalainarahajason/session-flow-booking.git

## Progression
1. Isolation Git : réussie, arbre propre avant création de branche.
2. Implémentation : réalisée dans le périmètre.
3. QA : contrôles locaux réussis ; CI distante non exécutée.
4. Audit sécurité/performance : réalisé sur le code et le paquet, limites documentées.
5. Recette : S1-A1 à S1-A5 vérifiés ; S1-A6 partiel pour CI distante.
6. Clôture : suspendue à la preuve CI distante, bilan préparé.

## Compteurs
Historique détaillé ci-dessous ; aucune erreur persistante au coupe-circuit 3/3.

## Périmètre
PRD 0.4, contrats 0.1.1 ; S1-A1 à S1-A6, CT-06/15/16, INV-01/02/15/18/19/20.
Points d'entrée : bootstrap, lifecycle, admin, admin-post, cron et démonstrateur REST isolé.
Contrôles prévus : échappement contextuel, capacités + portée + nonce, SQL paramétré,
migrations répétables, assets conditionnels, aucun transient critique.

## Constats initiaux
- WPCS initial : 758 erreurs, 400 avertissements ; 978 constats automatiquement corrigeables. Rapport conservé dans artifacts ci-dessous. Corrections en préparation.
- Activation de test : erreur SQL sur `cursor`, mot réservé MariaDB. Correction 1 : colonne physique `job_cursor`, sans changement du modèle logique.
- Première commande WP-CLI sans utilisateur refusée conformément aux capacités ; invocation corrigée avec `--user=1` (pas de changement runtime).

## Reprise après interruption du serveur (11 novembre 2026)
État vérifié : branche sprint-1, référence cdf1836, origin configuré sur le dépôt communiqué.
L'archive dist/session-flow-bookings-0.1.0.zip et Chromium ont été générés avant l'interruption.
Aucun push, merge ou commit produit effectué. Aucun coupe-circuit déclenché.

### Corrections et preuves conservées
- SQL `cursor` réservé : 1 correction (job_cursor), résolue par activation réussie.
- WPCS format initial : 978 constats corrigeables traités, code produit uniquement.
- PHPDoc : ajout initial puis complément des méthodes sans paramètres et paramètres promus ; déduplication finale des tags ajoutés en double. Tous résolus : artifacts/qa/wpcs-fourth-check.json = 0 erreur, 0 avertissement.
- Exception JSON Pointer (DtoValidator.php) : deux placements de commentaire sans effet puis troisième placement sur le champ signalé ; contrôle suivant réussi. Historique 2 tentatives infructueuses, résolu à la troisième. Ne pas réinitialiser si réapparition.
- IdempotencyTest 403 : isolation du WP_User entre scénarios, résolue ; 1 correction du support de test, aucun affaiblissement du contrat.
- Bootstrap CLI : HTTP_HOST explicite ; URL vide persistée lors de la première installation corrigée dans sfbk_test. Contrôle PHPUnit réel requis, une page wp_die avec code 0 ne vaut jamais réussite.

### Résultats observés avant interruption
- PHP local 8.5.4 : 21 tests unitaires, 24 assertions, succès (complément hors matrice).
- PHP 8.3.33 / MySQL 8.4 : 21 tests intégration, 85 assertions, succès.
- Concurrence isolée : 1 test, 6 assertions, deux processus synchronisés par pipes, succès.
- Redocly CLI 2.77.0 : OpenAPI valide avec preset spec ; 3 tests Node outillage réussis.
- Suite navigateur et contrôle du paquet : en cours, non validés.

## Recette navigateur : constats initiaux
- Dashboard rendu deux fois : callback enregistré par menu principal et sous-menu de même slug. Correction 1 : un seul callback sur le sous-menu.
- Test CSS arrivé sur le formulaire de connexion : synchronisation du test après redirection de connexion. Correction 1 du test, pas de contournement du contrôle d'assets.
- npm audit : 7 avis sur dépendances de développement héritées de wp-env 10.10.0 ; rapport artifacts/qa/npm-audit.json. Aucune de ces dépendances dans le paquet PHP distribué. À qualifier avant clôture.

## Derniers résultats et décisions de recette
- PHP 8.4.15 : 21 tests unitaires, 24 assertions, succès via wordpress:6.8.3-php8.4-apache.
- Dernière suite PHP 8.3 / MySQL : 23 tests, 95 assertions, succès ; unitaires 21/24.
- Playwright : 3/3 réussis après callback unique et synchronisation avec wp_attempt_focus (200ms, vérifié dans WordPress).
- Normalisation des refus REST du cœur ajoutée sur les seuls namespaces enregistrés ; test de dispatch réel.
- Plugin Check sur racine : refus des fichiers de développement, aucun résultat vert revendiqué.
- Plugin Check sur paquet : seul constat restant `outdated_tested_upto_header` (6.8 < 7.1).
- API officielle WordPress vérifiée : 7.1.4 ; ajout d'une cible de tests 7.1.4 avant toute modification du Tested up to. Pas de simple changement cosmétique du readme.
- Paquet intermédiaire : SHA256 b7e618dad040425fccbbc22422267458fdcfeb8843ba73ad675e407f03f3e8ca ; versions produit/Stable tag 0.1.0, 58 fichiers, aucune dépendance de développement.

## Concurrence étendue
Deux workers indépendants ont reproduit un deadlock InnoDB lors de la modification de l'index d'éligibilité. Correction 1 du gestionnaire transactionnel : capture errno 1205/1213 avant rollback et au plus trois reprises de la même opération locale, conformément aux contrats. Aucun appel externe dans le callback. Test inchangé, à rejouer.

## Cible WordPress courante
wp-env 10.10 partage le clone d'une URL Git entre références : le contrôle de version a empêché de déclarer 7.1.4 testée alors que 6.8.3 tournait encore. Utilisation de l'archive officielle 7.1.4 pour un cache distinct.

## Dernier état local
- Reprise 1205/1213 : test deux workers réussi (correction 1 runtime), aucune assertion affaiblie.
- PHP 8.3 : 29 intégrations / 121 assertions ; PHP 8.3 et 8.4 : 21 unitaires / 24 assertions chacun.
- WPCS final : zéro erreur/avertissement. OpenAPI et tests Node : succès.
- WordPress 7.1.4 effectif ; base du cœur de tests mise à jour par wp core update-db (pas une base de production).
- Après reconstruction, Docker gardait le bind mount sur l'ancien inode : constat expliqué par SHA256 hôte/conteneur divergent, aucune erreur readme masquée. Recréation des seuls tests-cli/tests-wordpress, volumes conservés, empreintes concordantes.
- Plugin Check final : 0 constat, code 0 ; artifacts/plugin-check/2026-11-11T17-16-56.379Z.
- Paquet final SHA256 bd345ecbe107e73d61b21ec9c598f386e97db22064299d6e7a41622eeb6ce24a.
- Repo distant vide ; pas de commit produit ou push effectué. Prochaine étape externe : autorisation commit/push pour obtenir la preuve GitHub Actions.

## Vérification finale de correspondance
Les 58 SHA256 du paquet monté correspondent exactement au manifest final. Dernière recette Playwright après recréation des conteneurs : 3/3, y compris 360px et nonce. Capture settings.png inspectée visuellement ; aucun débordement. Aucun nouveau changement PHP après les dernières suites.

## Autorisation de publication de branche
Utilisateur : commit et push de sprint-1 explicitement autorisés pour exécuter la CI et terminer S1-A6. Aucune autorisation de fusion, release ou Sprint 2.
Contrôle préparatoire du script CI : correction de l'échappement excessif du motif vérifiant le résumé PHPUnit, validé contre le rapport existant. Aucun changement PHP produit.
