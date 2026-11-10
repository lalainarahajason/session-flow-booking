# Validation WordPress.org — session-flow-bookings

Référence : PRD **0.4**, NFR-17/18 ; contrats **0.1.1**, OpenAPI **3.1.1**. Cette procédure ne lance pas un sprint et n'autorise aucune soumission/publication. Plugin Check est un contrôle complémentaire, pas une certification ni une revue humaine du répertoire.

## Identité et règles

- Nom/slug/text domain : `session-flow-bookings` ; fichier principal `session-flow-bookings.php` ; namespace `SessionFlowBookings`.
- Préfixe interne : `sfbk_` / `SFBK_` (quatre lettres avant le séparateur). Le dossier de travail reste `session-flow-booking/` ; wp-env le monte sous le slug correct.
- Règles bloquantes : API HTTP WordPress dans le code produit et adaptateur Stripe ; fonctions PHP d'exécution dangereuses interdites ; symboles globaux/options/tables préfixés ; aucune bibliothèque du cœur réembarquée.
- Paquet : Version PHP = Stable tag exactement ; sources lisibles fournies avec les fichiers compilés ; services tiers décrits ; GPLv2+ et licences compatibles, notices incluses.
- Les règles locales imposant les sources dans le paquet, interdisant les fonctions d'exécution et bloquant tous les avertissements sont volontairement strictes. WordPress.org accepte certains liens vers les sources et certains transports cURL de bibliothèques tierces ; ne pas présenter notre convention comme une interdiction officielle universelle.
- Les outils Node/shell et Plugin Check sont des dépendances de développement, à exclure du ZIP produit. Les contrôles manuels couvrent aussi les dépendances distribuées que l'outil ignore par défaut.

## Environnement isolé

Prérequis : Docker Compose disponible, Node 22.13+ et npm. `@wordpress/env` **10.10.0** et Plugin Check **2.2.0** sont épinglés. Le lockfile fixe les dépendances npm. Cette version Docker de wp-env évite les dépendances Playground récentes incompatibles avec le Node local ; elle conserve des dépendances de développement dépréciées, à réévaluer avant les livraisons.

`.wp-env.json` fixe WordPress 6.8.3 et PHP 8.3 pour ce contrôle local, pas comme annonce de dernière version ni comme recette de toute la matrice. wp-env 10.10.0 utilise `mariadb:lts` ; consigner la version/digest effectifs dans chaque preuve. Cette base ne valide pas le comportement transactionnel MySQL 8.4 proposé au Sprint 1. Les tests de verrous/migrations devront s'exécuter sur la base cible ; aucune compatibilité métier MariaDB n'est déduite de Plugin Check.

Les conteneurs et volumes créés par wp-env sont propres au projet. Le contrôle utilise `tests-cli` et sa base de tests, jamais une base de production. Ne pas lancer `cleanup`, `destroy` ou une réinitialisation destructive pour masquer un échec. Le montage n'active pas automatiquement le plugin produit ; les contrôles runtime de Plugin Check en gèrent le contexte.

```sh
npm ci --ignore-scripts
npm run env:start
npm run test:tooling
npm run plugin-check
npm run env:stop
```

`afterStart` exécute automatiquement `npm run plugin-check:install` dans `tests-cli`. L'installation officielle équivaut à :

```sh
npm run wp-env -- run tests-cli wp -- plugin install plugin-check --version=2.2.0 --activate
npm run wp-env -- run tests-cli wp -- plugin verify-checksums plugin-check
```

La version est vérifiée ; une version déjà installée différente bloque sans être écrasée. Pour relancer l'installation après un échec réseau : `npm run plugin-check:install`.

Le contrôle effectif est :

```sh
npm run wp-env -- run tests-cli wp -- plugin check session-flow-bookings --require=/var/www/html/wp-content/plugins/plugin-check/cli.php --format=strict-json
```

**Utiliser `npm run plugin-check` pour la porte de sortie.** La commande WP-CLI brute ne suffit pas : Plugin Check 2.2.0 peut retourner zéro malgré des erreurs/avertissements. Le wrapper lit la sortie complète, bloque les constats, sorties vides/inconnues, erreurs de processus et diagnostics stderr. Il conserve les logs séparés des messages wp-env. Le chargement anticipé de `cli.php` active les contrôles runtime ; ne pas l'enlever.

## Preuves et limites

Les rapports horodatés sont dans `artifacts/plugin-check/` (non versionnés). Ils comprennent commandes, codes de sortie, version WordPress/PHP/BDD/Plugin Check et sorties intégrales. La CI les conserve même après échec. Ne pas journaliser de vrais secrets ou données personnelles ; utiliser des données fictives.

Le wrapper refuse une cible absente avec le code 2 ; tant que le Sprint 1 n'a pas produit `session-flow-bookings.php`, **la validation du produit est bloquée**, même si l'installation de l'outil réussit. La CI ne masque pas cette absence par un skip vert. Les tests Node du wrapper ne valent pas tests du plugin. PHPUnit n'est requis ici que si du comportement PHP produit est créé/modifié ; aucun faux test PHP n'est ajouté pour ces scripts Node.

Lors d'une clôture, préparer le paquet avec ses dépendances runtime et ses sources, puis monter la copie décompressée via `.wp-env.override.json` :

```json
{
  "mappings": {
    "wp-content/plugins/session-flow-bookings": "./dist/session-flow-bookings"
  }
}
```

Relancer `npm run env:start`, puis `npm run plugin-check`. Le wrapper vérifie le fichier principal dans cette cible ; les rapports utilisent un montage séparé, hors du plugin contrôlé. Le code source racine ne remplace pas le contrôle du contenu réellement distribué. Ne pas écraser un override existant sans en préserver les autres réglages. Retirer seulement ce mapping pour revenir au contrôle des sources, puis redémarrer wp-env.

Le script de packaging reste à réaliser au Sprint 1 ; aucun paquet final n'est encore disponible. Conserver l'empreinte du paquet et les preuves de revue : versions égales, correspondance sources/builds, inventaire des licences et appels tiers. Les exclusions de développement du paquet ne doivent pas supprimer les sources nécessaires.

## Références officielles consultées

- [Directives du répertoire](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) : licences, lisibilité, services, bibliothèques du cœur.
- [Problèmes fréquents de revue](https://developer.wordpress.org/plugins/wordpress-org/common-issues/) : HTTP API, préfixes, versions, services et dépendances.
- [Readme et Stable tag](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).
- [Plugin Check officiel](https://github.com/WordPress/plugin-check) et [commande CLI](https://github.com/WordPress/plugin-check/blob/2.2.0/docs/CLI.md).
- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) ; les options réellement utilisées doivent être vérifiées dans le README de la version épinglée, car les versions récentes diffèrent.

Une soumission WordPress.org reste une action distincte, après livraison complète et autorisation explicite.

## Vérifications effectuées le 10 novembre 2026

| Contrôle | Résultat observé |
|---|---|
| Configuration JSON, workflow YAML, frontmatter des sept skills | Valides ; liens locaux résolus |
| `node --check tools/plugin-check.mjs` | Code de sortie 0 |
| `npm run test:tooling` | 3 tests réussis, 0 échec, 0 skipped ; erreurs, avertissements et sorties incomplètes bloqués |
| `python3 specs/contracts/validate_contracts.py` | Code 0 : 91 opérations, 98 schémas, 11 exemples ; contrôle complémentaire uniquement |
| Comparaison OpenAPI avec la préparation | Identique hors `info` (titre/version/référence PRD) ; sources de préparation intactes par SHA-256 |
| `npm run env:start` | Réussi après attribution du port de test 8897 ; développement sur 8896 |
| `npm run plugin-check:install` | Réussi, y compris seconde exécution ; Plugin Check 2.2.0 activé, version vérifiée, checksums officiels conformes |
| Environnement effectivement observé | WordPress 6.8.3, PHP 8.3.33, MariaDB 12.3.3 ; ne constitue pas la matrice métier MySQL 8.4 |
| `wp plugin list-checks --require=.../plugin-check/cli.php` | Commande exécutée ; contrôles runtime de scripts/styles présents |
| Essai `wp plugin check plugin-check --require=.../plugin-check/cli.php --format=strict-json` | Exécuté sur l'outil lui-même, **pas sur le produit**. 264 constats (124 erreurs, 140 avertissements), code WP-CLI 0 ; rapport réel correctement refusé par `assessResult` |
| `npm run plugin-check` sur le produit absent | Code 2 attendu : fichier principal absent, contrôle produit non exécuté |
| CI distante, paquet final, PHPUnit/WPCS du produit | Non exécutés : aucun dépôt distant, paquet ou code PHP produit à ce stade |

Le test sur Plugin Check vérifie l'exécution de l'outil et le traitement de ses résultats ; il ne valide pas sa conformité au répertoire, ni celle de session-flow-bookings. Les constats de ce test ne sont pas des failles reproduites du produit. Rapports locaux dans `artifacts/plugin-check/`, notamment `checker-smoke.log` et `checker-smoke-findings.json`.

Revue du périmètre livré (documentation, JSON/YAML et scripts Node de développement) :

| Contrôle obligatoire | Statut / justification |
|---|---|
| Sorties `esc_html()` / `esc_attr()` | Non applicable : aucun rendu PHP/HTML produit créé ; les scripts écrivent des logs CLI |
| `current_user_can()` et portée objet | Non applicable : aucun point d'entrée HTTP ou objet métier créé ; commandes locales uniquement, cible Docker de test fixe |
| `$wpdb->prepare()` | Non applicable : aucune requête SQL dynamique produit ; le seul diagnostic SQL exécuté est le littéral constant `SELECT VERSION()` |
| CSRF/XSS/SQLi runtime et WPCS | Non applicables à ces changements sans PHP produit ; restent obligatoires dès son implémentation |
| Processus de l'outillage | Arguments WP-CLI transmis après `--` ; commande shell interne fixe, sans données HTTP ; sorties et statuts conservés, résultats inconnus refusés |

Ce relevé n'est ni un audit de sécurité du futur plugin, ni une clôture du Sprint 1. Les règles bloquantes seront appliquées au code et au paquet lorsqu'ils existeront.
