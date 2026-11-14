# Recette Sprint 1 — finalisée

Références : PRD 0.4, contrats 0.1.1, branche sprint-1 depuis cdf1836.
Recette achevée ; critères S1-A1 à S1-A6 vérifiés localement et par les workflows GitHub sur 9417a24.

| Critère | Preuves réalisées | État |
|---|---|---|
| S1-A1 | Activation wp-env, réactivation, migrations sur MySQL 8.4 ; conservation et reprise | Vérifié ; régression étendue 7.1.4 |
| S1-A2 | PHPUnit autorisations/sites/objets ; Playwright admin, formateur, appel direct réglages 403 | Vérifié sur 6.8.3 et 7.1.4 |
| S1-A3 | DTO fermés, JSON invalide, taille, versions, erreurs REST | Vérifié, SQL traité comme valeur et réponse sans fuite |
| S1-A4 | Replay, mismatch, droits révoqués, deux processus indépendants | Vérifié sur MySQL 8.4 |
| S1-A5 | Rollback et reprise de bail, rejet de l'ancien worker | Vérifié, deux workers et consommateur outbox dédupliqué |
| S1-A6 | WPCS zéro, validation OpenAPI Redocly + Python, packaging et revue | Vérifié ; Sprint quality et Plugin Check distants réussis |

## Commandes de référence

- `vendor/bin/phpcs` : code produit et templates, WPCS 3.4.1 / PHPCS 3.13.6.
- `npm run validate:contracts` : Redocly CLI 2.77.0 preset spec puis Python complémentaire.
- `npm run test:tooling` : 3 tests Node.
- PHPUnit 11.5.57 : bootstrap unit et bootstrap intégration explicites.
- PHP 8.3.33 dans tests-cli ; PHP 8.4.15 via image WordPress isolée.
- MySQL 8.4 dédié sfbk_test pour transactions et concurrence ; MariaDB de wp-env uniquement Plugin Check.
- `npm run test:e2e` : Chromium, 3 parcours sur http://localhost:8897.
- `npm run build:plugin` : archive allowlist sans tests/outillage/dev-vendor.
- `npm run plugin-check` : paquet décompressé, cli.php préchargé, rapports complets horodatés.

## Limites

REC-10 et REC-12 préparés sur objets techniques, pas sur objets métier absents.
NFR-08 du moteur de disponibilités hors Sprint 1. Aucun essai pilote humain revendiqué.
Exécutions GitHub finales : Sprint quality 43407162787 et Plugin Check 43407162677, toutes deux réussies.

## Résultat final du contrôle WordPress.org
Plugin Check 2.2.0 statique/runtime : code 0, aucun constat. WordPress 7.1.4 / PHP 8.3.33 / MariaDB 12.3.3.
Rapport complet : artifacts/plugin-check/2026-11-11T17-16-56.379Z.
SHA256 archive : bd345ecbe107e73d61b21ec9c598f386e97db22064299d6e7a41622eeb6ce24a.
S1-A1 à S1-A6 vérifiés. Preuves CI détaillées dans sprint-1-bilan.md : 29 tests d’intégration / 121 assertions, 3 parcours navigateur, deux versions PHP et contrôle WordPress.org vert.
