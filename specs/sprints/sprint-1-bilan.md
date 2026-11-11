# Sprint 1 — bilan de l'exécution locale

**Décision : clôture suspendue à la preuve de CI GitHub.** Le commit/push des changements produit n'a pas été autorisé. Aucun passage au Sprint 2.

Branche `sprint-1`, référence initiale `cdf1836` (commit des spécifications/outils autorisé séparément).
Origin : https://github.com/lalainarahajason/session-flow-booking.git ; lecture des références : dépôt distant vide.
Sources : PRD 0.4, contrats 0.1.1 / OpenAPI 3.1.1. Aucun changement de route métier, DTO ou invariant.

## Livrables

Plugin `session-flow-bookings` 0.1.0, namespace `SessionFlowBookings`, préfixes `sfbk_` / `SFBK_`.
Bootstrap Composer PSR-4 ; lifecycle, navigation native et réglage explicite de désinstallation.
Cinq tables techniques InnoDB, schéma 0001 ; idempotence, audit, outbox, jobs avec baux et reprises transactionnelles bornées.
Adaptateurs REST avec DTO stricts, versions, projections et normalisation des erreurs du cœur.
Démonstrateur `sfbk-test/v1` dans les seuls tests, exclu du ZIP.
Aucune réservation ni opération financière métier livrée dans cet incrément.

## Critères et preuves

| Critère | Exigences / invariants | Fichiers principaux | Preuves locales | Statut |
|---|---|---|---|---|
| S1-A1 | NFR-10/12, INV-01 | Lifecycle, MigrationRunner, SchemaVerifier, Version0001 | Installation wp-env, LifecycleTest, MigrationTest ; table manquante recréée et audit conservé | Vérifié localement |
| S1-A2 | WP-02, NFR-01/02, INV-02/20 | Capabilities, Menu, UninstallSettings, AuthorizationPolicy | AdminPermissionsTest, SiteIsolationTest, Playwright ; accès direct formateur refusé, nonce manquant refusé | Vérifié localement |
| S1-A3 | NFR-02, INV-19/20, CT-15/16 | DtoValidator, Version, RequestContext, ResponseFactory, RouteRegistrar | DTO imbriqués, JSON/types/bornes, versions, taille, dispatch WordPress réel, SQL traité comme valeur | Vérifié localement |
| S1-A4 | INV-18, CT-06 | IdempotentMutation, WpdbIdempotencyStore | Replay, mismatch, droits révoqués et deux processus indépendants ; un seul audit/outbox/intention | Vérifié localement |
| S1-A5 | NFR-14, INV-15/18 | WpdbTransactionManager, JobWorker, stores | Rollback après toutes les écritures ; deux workers ; bail repris ; ancien jeton refusé ; deux jobs pour un événement, un effet | Vérifié localement |
| S1-A6 | NFR-10/11/12/17/18 | Uninstaller, workflows, bin, readme, LICENSE | Désinstallation conservatrice/explicite, WPCS, OpenAPI, PHPUnit, paquet | Tous contrôles locaux dont Plugin Check vérifiés ; CI distante non exécutée |

REC-10/12 préparés sur objets techniques ; couverture métier à reprendre après création de ces objets.

## Tests et environnement

- PHPUnit 11.5.57, PHP 8.3.33 : **29 tests d'intégration, 121 assertions**, MySQL 8.4 dédié `sfbk_test`, WordPress 7.1.4 ; rapport `artifacts/qa/integration-wordpress71.txt`.
- PHPUnit sur PHP 8.3.33 et PHP 8.4.15 : **21 tests unitaires / 24 assertions** sur chaque version ; `unit-php83-final.txt`, `unit-php84-final.txt`.
- Premières intégrations également exécutées sur WordPress 6.8.3. La dernière régression étendue est sur 7.1.4 ; ne pas confondre ces preuves.
- Aucune suite finale sautée/incomplète/risquée signalée par PHPUnit ; pas de couverture chiffrée revendiquée.
- PHPCS 3.13.6 / WPCS 3.4.1 : **0 erreur, 0 avertissement**, code produit et templates ; `artifacts/qa/wpcs-final.json`.
- `npm run validate:contracts` : Redocly CLI 2.77.0 preset spec, puis Python complémentaire ; succès, 91 opérations / 98 schémas / 11 exemples.
- Contrat du démonstrateur validé séparément par Redocly.
- `npm run test:tooling` : **3 tests Node**, succès.
- `npm run test:e2e` : **3 parcours Chromium**, navigation, clavier, réglage de suppression, rejet sans nonce, formateur refusé, assets isolés et 360px ; rapports sous `artifacts/playwright/`.
- `bash -n` des scripts et `git diff --check` : succès.

Les commandes d'intégration exactes emploient `php vendor/bin/phpunit --no-configuration --bootstrap tests/bootstrap-integration.php tests/Integration` dans le conteneur PHP 8.3 et le montage de sources de développement. Le script `bin/install-test-environment.sh` sélectionne le conteneur du projet, attend MySQL et exige un résumé PHPUnit réel pour refuser un éventuel wp_die avec code zéro.

## Audit WordPress du code et des dépendances distribuées

| Contrôle obligatoire | Statut | Preuve / chemin | Limites |
|---|---|---|---|
| Sorties : esc_html / esc_attr et contexte | Conforme dans le périmètre | Templates admin : esc_html du titre, esc_url action, checked/wp_nonce_field natifs ; test titre malveillant inerte | JSON sérialisé, pas échappé comme HTML ; exception JSON Pointer locale justifiée |
| Permissions : current_user_can et portée objet | Conforme dans le périmètre | Menu/render, UninstallSettings (manage_options + booking_manage_settings), RequestContext puis AuthorizationPolicy site/propriétaire ; tests refus/revocation | Démonstrateur seulement pour les objets absents ; aucune autorisation invitée prétendue livrée |
| SQL : prepare ou paramétrage démontré | Conforme dans le périmètre | Stores : prepare avec valeurs et %i ; insert/update avec formats explicites ; DDL fixe à partir du préfixe et get_charset_collate | DDL n'accepte aucune structure client ; %i compatible WP minimum 6.8 |
| CSRF et authentification | Conforme dans le périmètre | check_admin_referer réglage ; nonce wp_rest et identité WP ; Playwright POST sans nonce 403 | Uninstall s'appuie sur WP_UNINSTALL_PLUGIN et le choix préalablement autorisé ; pas de nonce HTTP fictif |
| WPCS | Conforme automatiquement | Rapport final zéro ; exceptions locales PSR-4, DDL et contexte JSON documentées | Aucun vendor/généré corrigé ; règles de sécurité non désactivées globalement |
| Hooks et cycle de vie | Conforme dans le périmètre | Enregistrement unique, cron sans doublon, désactivation conservatrice ; tests dédiés et correction double callback | Déclenchement serveur sur site sans trafic documenté ; pas d'email réel |
| API HTTP WordPress / transports tiers | Non applicable au runtime actuel | Inventaire du paquet : aucun appel HTTP produit, aucun fournisseur actif | Les futurs adaptateurs devront employer les API HTTP WordPress |
| Fonctions d'exécution dangereuses | Aucune occurrence exécutable identifiée dans le paquet | Inventaire des PHP distribués ; proc_open exclusivement dans tests/Support, absent du ZIP | Aucun audit global de toutes les dépendances de développement revendiqué |
| Préfixe et namespace | Conforme dans le périmètre | sfbk_, SFBK_, SessionFlowBookings ; rôles et assets préfixés | booking/v1 et booking_* conservés conformément aux contrats |
| Bibliothèques du cœur non dupliquées | Conforme par inventaire | Aucun React/jQuery/PHPMailer ; vendor runtime limité à autoload/composer | Les dépendances de dev restent hors paquet |
| Plugin Check statique/runtime | Conforme : zéro constat, code 0 | Plugin Check 2.2.0, WP 7.1.4/PHP 8.3.33/MariaDB 12.3.3 ; rapport artifacts/plugin-check/2026-11-11T17-16-56.379Z | Les échecs précédents sont conservés, pas transformés en succès |

Le lecteur numérique errno du gestionnaire transactionnel accède uniquement au diagnostic de la connexion wpdb existante ; aucune requête par mysqli direct. Exception de sniff locale et justifiée. Les erreurs 1205/1213 sont reprises au plus trois fois après rollback ; jamais d'effet externe dans le callback.

## Migrations, données et limites

`dbDelta()` suivi de vérifications moteur/colonnes/index. Verrou de connexion durable le temps de l'exécution, checkpoints conservés sur échec ; publication version après succès seulement. Le DDL n'est pas supposé annulable par rollback.
Conservation à la désactivation et par défaut à la désinstallation ; suppression explicite limitée aux objets du plugin. Utilisateurs et données étrangères conservés dans les tests.
Pas de migration de données métier antérieures : elles n'existent pas encore. Mise à jour d'installations réelles et restauration coordonnées à rejouer avec les objets des sprints suivants.

## Performance

Mesures CLI ponctuelles sur WP 7.1.4/PHP 8.3, mêmes données :
- lecture étrangère plugin chargé : 12 requêtes totales, pic 54 316 560 octets ; lecture 0.022ms / 0 requête ajoutée ;
- lecture étrangère plugin ignoré : 10 requêtes totales, pic 54 253 872 octets ; lecture 0.016ms / 0 requête ajoutée ;
- rendu du dashboard : 3.226ms / 0 requête ajoutée, 1 512 octets de mémoire delta.
Ces échantillons ne mesurent pas un p95 HTTP ni NFR-08. Surcoût bootstrap observé : 2 lectures techniques ; aucun asset sur écran étranger, vérifié au navigateur. Aucun transient : données sensibles/transactionnelles et écrans légers.

## Paquet et licences

Archive : `dist/session-flow-bookings-0.1.0.zip`, 58 fichiers.
SHA256 : `bd345ecbe107e73d61b21ec9c598f386e97db22064299d6e7a41622eeb6ce24a`.
Les 58 empreintes du paquet monté et de l’archive décompressée correspondent ; dernière recette navigateur du paquet : 3/3. Capture à 360px inspectée visuellement.
Manifest et correspondance binaire sources/paquet : `artifacts/qa/package-manifest.json`.
Version PHP = constante runtime = Stable tag : **0.1.0** ; Tested up to 7.1 seulement après tests réels 7.1.4.
Code propre GPL-2.0-or-later, LICENSE GPLv2 complète ; chargeur Composer avec notice MIT distribuée. Aucun asset tiers.
CSS lisible distribué, aucun bundle compilé/minifié ; aucune source requise exclue. Services tiers runtime : aucun.
PHPDoc et CHANGELOG Unreleased mis à jour avec les changements réels.

## Risques résiduels et travail restant

- CI GitHub distante : non exécutée ; workflows prêts, dépôt distant vide. Nécessite autorisation explicite de commit/push du code produit.
- Sept avis npm sur l'outillage wp-env 10.10.0 hérité : 2 critiques, 3 élevés, 2 faibles, rapport complet conservé. Aucune dépendance concernée dans le paquet produit. Exploitabilité locale non reproduite ; ne pas présenter cet inventaire comme une absence de risque sur les postes de développement. Mise à jour majeure non appliquée silencieusement.
- Recette navigateur automatisée, pas de pilote humain ni d'audit exhaustif lecteur d'écran.
- La sécurité globale, le moteur de réservation, les finances et les performances métier ne sont pas certifiés par ce socle.

Le sprint reste non clôturé tant que les preuves obligatoires manquantes ne sont pas obtenues.
