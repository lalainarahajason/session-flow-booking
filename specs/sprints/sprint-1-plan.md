# Sprint 1 — Socle WordPress installable et sécurisé

Date du cadrage : 10 novembre 2026. Révision documentaire : identité et WordPress.org demandées le même jour.

**Statut : Sprint 1 clôturé sur `sprint-1` — critères vérifiés, bilan et recette finalisés.** L'autorisation d'enregistrer ce document ne vaut ni validation du cadrage, ni autorisation de lancer le sprint. Aucun code produit n'a été écrit pour ce cadrage.

## 1. Objectif et sources

Le Sprint 1 doit livrer un plugin installable, une administration protégée par les rôles et des primitives techniques testées : validation API, idempotence, transactions, audit et traitements différés. Il ne livre pas encore de réservation ni de paiement réel.

Le cadrage applique [architecture-guard](../../.claude/skills/architecture-guard.md) et les instructions d'[AGENTS.md](../../AGENTS.md). Les exigences de migrations, tests, audit, performance et clôture ont également été consultées pour préparer les travaux.

Les références effectives sont désormais les spécifications locales dans `specs/`, issues de la source de préparation conservée en lecture seule. Provenance dans `specs/contract-sources.json` :

- PRD **0.4**, daté du 10 novembre 2026 ;
- fonctionnalités du **9 novembre 2026**, plan de développement aligné le **10 novembre 2026** ;
- contrats système, modèle de données et invariants **0.1.1** ;
- document OpenAPI **3.1.1**, version de contrat **0.1.1**, comprenant 91 opérations.

Les références courantes au PRD sont alignées sur 0.4 ; les mentions des anciennes versions dans l’historique sont conservées. Les contrats 0.1.1 ne changent ni routes ni DTO. Toute divergence fonctionnelle nouvelle devra être arbitrée avant l’implémentation dépendante.

## 2. Choix techniques proposés à validation

| Sujet | Proposition pour le Sprint 1 |
|---|---|
| Base produit | Code neuf ; aucune reprise du code Amelia |
| Identité | Plugin `session-flow-bookings`, namespace PHP `SessionFlowBookings`, domaine de traduction `session-flow-bookings` |
| Préfixes | Tables/options/hooks internes `sfbk_` ; capacités `booking_*` conformément aux contrats |
| API métier | Conserver la base contractuelle `booking/v1`, construite avec `rest_url()` |
| PHP | PHP 8.3 et 8.4 dans la matrice de tests |
| WordPress | WordPress 6.8.x comme version minimale proposée ; versions correctives exactes figées lors de la préparation de l'environnement |
| Base | MySQL 8.4, tables InnoDB ; compatibilité MariaDB non revendiquée dans ce sprint |
| Installation | WordPress monosite ; activation réseau refusée explicitement tant que le multisite n'est pas implémenté |
| Administration | Écrans PHP utilisant les composants natifs de WordPress, sans SPA |
| Dépendances | Composer pour PHP, PHPUnit et WPCS ; Playwright pour les parcours navigateur |
| Environnement | wp-env Docker pour Plugin Check, bases développement/tests distinctes ; environnement MySQL 8.4 métier à préparer au Sprint 1 |

Ce choix PHP est cohérent avec la [matrice officielle WordPress/PHP](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) ; le choix de base respecte les [prérequis WordPress](https://wordpress.org/about/requirements/). Cela reste une cible à vérifier par la CI, pas une compatibilité déjà démontrée.

### Préparation Git

Au moment du cadrage, le dossier produit n'est pas un dépôt Git. Le démarrage proposé sera donc : initialisation locale du dépôt, puis création de `sprint-1` avant toute modification de fichiers au titre de l'exécution du sprint. Le présent enregistrement documentaire, demandé séparément, ne déclenche pas cette exécution. Aucun dépôt distant ni publication n'est inclus.

### Disponibilité des spécifications en CI

Les dix fichiers de spécifications sont désormais repris localement à la demande de mise à jour du PRD. Leur provenance et leurs empreintes sont enregistrées ; la préparation reste intacte.

## 3. Architecture

Les dépendances suivront cette direction :

```text
Adaptateurs WordPress : administration, REST, cron
                         ↓
Services applicatifs : autorisation, mutation, idempotence, worker
                         ↓
Domaine et interfaces de persistance
                         ↑
Adaptateurs MySQL / WordPress
```

Le domaine ne dépendra ni de `$wpdb`, ni des contrôleurs REST, ni des templates. Les contrôleurs ne porteront pas les règles métier.

Les objets métier futurs conserveront leurs frontières : personne, acheteur et compte WordPress distincts ; autorisations par site et objet ; aucune autorité de réservation ou de paiement confiée à un cache ou au navigateur. Les invariants de capacité et les règles financières ne seront pas présentés comme implémentés par ce seul socle.

## 4. Lots techniques

### Lot A — Bootstrap et cycle de vie

- Point d'entrée du plugin, garde contre l'accès PHP direct et chargement Composer.
- Vérification des prérequis avant installation.
- Enregistrement centralisé des hooks, sans doublons.
- Activation répétable : installation, capacités et programmation du worker.
- Détection des mises à niveau hors activation, sans exécuter `dbDelta()` à chaque requête.
- Désactivation : arrêt des tâches propres au plugin, conservation des données.
- Désinstallation : conservation par défaut ; suppression complète uniquement si un administrateur l'a explicitement choisie auparavant, avec protection CSRF.

Le réglage de suppression sera un paramètre technique local, distinct des futurs réglages commerciaux de l'API. `uninstall.php` vérifiera `WP_UNINSTALL_PLUGIN` et fonctionnera sans le bootstrap normal. Le nettoyage sera limité aux objets appartenant au plugin ; aucun utilisateur WordPress ne sera supprimé. Le mode de conservation gardera notamment les informations nécessaires à une réinstallation cohérente.

### Lot B — Schéma technique et migrations

La première migration créera uniquement ces cinq tables :

| Table, après préfixe WordPress | Garanties principales |
|---|---|
| `sfbk_idempotency_records` | Unicité site/principal/opération/clé, empreinte, état, bail, référence de résultat |
| `sfbk_provider_inbox` | Unicité fournisseur/compte/environnement/événement ; aucun endpoint Stripe livré |
| `sfbk_domain_outbox` | Événement durable, agrégat/version/type/discriminant uniques, bail de traitement |
| `sfbk_jobs` | État, progression, périmètre, échéance, tentatives et reprise |
| `sfbk_audit_events` | Historique append-only, acteur, objet, versions et identifiants de corrélation |

Les tables métier — formations, sessions, commandes, participants et finances — restent pour les sprints concernés.

La migration aura :

- une version de schéma distincte de celle du plugin ;
- un verrou durable d'exécution ;
- des étapes répétables et des postconditions vérifiées ;
- une publication de version uniquement après réussite complète ;
- un blocage des écritures incompatibles en cas d'installation partielle ;
- des noms structurels internes, jamais fournis par une requête.

Les index d'unicité incluront la portée nécessaire. Les index de traitement couvriront les états, échéances et baux. Les relations effectivement introduites seront contrôlées sans supposer que `dbDelta()` sait installer les clés étrangères.

Appliquer [db-migration](../../.claude/skills/db-migration.md) : préfixe du site, charset/collation WordPress, DDL compatible avec `dbDelta()`, vérification des colonnes/index/contraintes et erreurs SQL. Ne pas supposer qu'un rollback transactionnel annule le DDL. Documenter sauvegarde, reprise et restauration coordonnée code/base.

### Lot C — Administration et permissions

Créer la navigation : tableau de bord, calendrier, formations, sessions, clients, paiements et réglages.

Les écrans futurs afficheront clairement leur état non livré dans l'environnement de développement, sans chiffres fictifs ni actions présentées comme fonctionnelles.

| Profil | Accès prévu |
|---|---|
| Administrateur | Ensemble des capacités du plugin, réglages et politique de désinstallation |
| Gestionnaire | Catalogue, planning, clients, commandes et lecture financière ; ni secrets ni réglages ; encaissement manuel et remboursement désactivés par défaut |
| Formateur | Entrée administrative limitée et futures données affectées ; aucun accès financier global ou aux réglages |
| Client / abonné | Aucun accès administratif au plugin |

Chaque écran et traitement vérifiera les capacités côté serveur. Une politique distincte préparera le contrôle du site, du propriétaire et de l'affectation aux objets. Les objets métier absents seront représentés par des fixtures dans les tests ; leur autorisation réelle sera reprise lors de leur livraison.

### Lot D — Socle REST et démonstrateur contractuel

Le socle assurera :

- DTO fermés, y compris les objets imbriqués ;
- types stricts, sans convertir silencieusement `"1"` en entier ;
- rejet des champs inconnus et absence de mass assignment ;
- enveloppes de succès et d'erreur conformes, avec `request_id` ;
- réponses privées `Cache-Control: no-store` ;
- contrôle cookie WordPress, nonce, capacité puis portée ;
- `ETag` et `If-Match`, comparaison de version sous verrou ;
- idempotence persistée et revérification des droits avant restitution d'un résultat ;
- journaux limités aux informations utiles, sans cookies, jetons, secrets ou corps sensibles.

L'ordre sera celui du contrat : authentification et autorisation, validation, recherche d'une répétition autorisée, verrouillage, comparaison de version, mutation. Une répétition réussie ne doit pas échouer parce que son propre premier traitement a incrémenté la version.

**Aucune des 91 opérations métier ne sera annoncée comme livrée.** Pour démontrer S1-A3 à S1-A5, un plugin de test séparé exposera :

- `GET /sfb-test/v1/resources/{id}` ;
- `PUT /sfb-test/v1/resources/{id}`.

Le contrat de test décrira explicitement principal, capacité, portée, DTO, version, transaction et réponses. Ces routes et leurs tables seront absentes du paquet distribuable, activées seulement dans l'environnement de recette.

Elles permettront de vérifier 401, 403, 404, 412, 422 et 428, ainsi que les conflits d'idempotence 409. Un objet absent et un objet hors portée retourneront la même réponse 404 ; une action interdite sur un objet déjà visible retournera 403.

### Lot E — Transactions, audit, outbox et reprise

Une mutation de test devra persister atomiquement :

1. son changement d'état et sa nouvelle version ;
2. son audit ;
3. son événement outbox ;
4. son résultat idempotent.

Un échec avant commit ne laissera aucun effet partiel.

Le worker utilisera un bail durable avec jeton de possession : un ancien worker ne pourra pas confirmer le travail après reprise par un autre. La consommation sera « au moins une fois », avec déduplication de l'effet interne dans la même transaction que son acquittement.

Les tests injecteront des interruptions avant et après commit, après acquisition du bail et avant acquittement. Deux connexions indépendantes testeront les courses d'idempotence et de traitement.

Les appels externes resteront hors transaction. Aucun envoi d'email ou appel de paiement réel n'est prévu. L'ordre global des futurs verrous sera préservé : idempotence, ressources triées, sessions, commandes, opérations financières/lignes. Les primitives de ce sprint ne devront pas imposer un ordre incompatible.

### Lot F — Sécurité, performance et validation

Appliquer [audit-wp](../../.claude/skills/audit-wp.md) au diff et à ses dépendances directes. L'audit final devra rendre compte explicitement des trois contrôles imposés :

| Contrôle | Vérification prévue |
|---|---|
| Échappement | `esc_html()`, `esc_attr()`, `esc_url()` selon le contexte ; sérialisation JSON distincte de l'échappement HTML |
| Permissions | `current_user_can()`, portée par objet/site, refus HTTP direct, nonce pour les actions authentifiées par cookie |
| SQL | `$wpdb->prepare()` sur SQL brut dynamique, ou méthodes `$wpdb` paramétrées avec formats ; identifiants structurels autorisés |

S'ajouteront WPCS, CSRF, absence de fuite dans les logs, absence de hooks dupliqués et chargement des assets uniquement sur les écrans concernés. Les protections équivalentes et non-applicabilités seront justifiées. Aucun correcteur global ne modifiera les tiers ou fichiers générés, ni les règles WPCS pour masquer une anomalie.

**Aucun transient n'est nécessaire au Sprint 1** : les données sont sensibles ou exigent une fraîcheur transactionnelle, et les écrans restent légers. Cette absence sera documentée. Les mesures compareront plugin actif/inactif sur un écran propre au plugin et sur un écran WordPress étranger : temps, requêtes, mémoire et poids des assets. L'objectif NFR-08 du moteur de créneaux ne pourra pas être validé à ce stade.

## 5. Liste exacte des fichiers prévus

Tous les chemins ci-dessous sont relatifs au dossier physique `session-flow-booking/` ; le slug installé est `session-flow-bookings`.

Lors du cadrage initial, ces fichiers étaient à créer. Le plan, les spécifications locales et l’outillage wp-env/Plugin Check ont depuis été créés ou mis à jour sur demande distincte. Les fichiers produit et les tests métier restent à créer après validation du sprint.

### Socle et outillage

```text
.gitignore
.distignore
.editorconfig
.env.example
.wp-env.json
composer.json
composer.lock
package.json
package-lock.json
phpunit.xml.dist
phpcs.xml.dist
playwright.config.ts
compose.yaml
.github/workflows/ci.yml
.github/workflows/plugin-check.yml

session-flow-bookings.php
uninstall.php
readme.txt
LICENSE
README.md
CHANGELOG.md

docker/php/Dockerfile
bin/install-test-environment.sh
bin/validate-contracts.sh
bin/build-plugin.sh
tools/plugin-check.mjs
tools/plugin-check-result.mjs
tools/tests/plugin-check-result.test.mjs
```

### Code produit

```text
src/Plugin.php

src/Domain/Principal.php
src/Domain/OperationException.php
src/Domain/Version.php
src/Domain/Event.php

src/Application/Ports/TransactionManager.php
src/Application/Ports/IdempotencyStore.php
src/Application/Ports/AuditStore.php
src/Application/Ports/OutboxStore.php
src/Application/Ports/JobStore.php

src/Application/AuthorizationPolicy.php
src/Application/IdempotentMutation.php
src/Application/JobWorker.php

src/Infrastructure/Database/TableNames.php
src/Infrastructure/Database/MigrationRunner.php
src/Infrastructure/Database/SchemaVerifier.php
src/Infrastructure/Database/Migrations/Version0001.php
src/Infrastructure/Database/WpdbTransactionManager.php
src/Infrastructure/Database/WpdbIdempotencyStore.php
src/Infrastructure/Database/WpdbAuditStore.php
src/Infrastructure/Database/WpdbOutboxStore.php
src/Infrastructure/Database/WpdbJobStore.php

src/Infrastructure/Logging/RedactingLogger.php

src/WordPress/Lifecycle.php
src/WordPress/Capabilities.php
src/WordPress/Cron.php
src/WordPress/Uninstaller.php
src/WordPress/Admin/Menu.php
src/WordPress/Admin/Assets.php
src/WordPress/Admin/UninstallSettings.php
src/WordPress/Rest/RequestContext.php
src/WordPress/Rest/DtoValidator.php
src/WordPress/Rest/ResponseFactory.php
src/WordPress/Rest/RouteRegistrar.php

templates/admin/dashboard.php
templates/admin/placeholder.php
templates/admin/settings.php
assets/admin.css
```

### Tests et démonstrateur isolé

```text
tests/bootstrap-unit.php
tests/bootstrap-integration.php

tests/Support/FixtureFactory.php
tests/Support/ConcurrentRunner.php
tests/Support/FaultInjector.php

tests/Fixtures/demo-plugin/demo-plugin.php
tests/Fixtures/demo-plugin/DemoController.php
tests/Fixtures/demo-plugin/DemoRepository.php
tests/Fixtures/demo-plugin/DemoJobHandler.php

tests/Unit/DtoValidatorTest.php
tests/Unit/ResponseFactoryTest.php
tests/Unit/IdempotencyFingerprintTest.php
tests/Unit/VersionTest.php
tests/Unit/AuthorizationPolicyTest.php
tests/Unit/RedactingLoggerTest.php

tests/Integration/LifecycleTest.php
tests/Integration/MigrationTest.php
tests/Integration/UninstallTest.php
tests/Integration/AdminPermissionsTest.php
tests/Integration/RestContractTest.php
tests/Integration/IdempotencyTest.php
tests/Integration/TransactionOutboxTest.php
tests/Integration/WorkerRecoveryTest.php
tests/Integration/ConcurrencyTest.php
tests/Integration/SiteIsolationTest.php
tests/Integration/HooksAssetsTest.php

tests/e2e/admin-navigation.spec.ts
tests/e2e/admin-permissions.spec.ts
tests/e2e/assets-isolation.spec.ts
tests/contracts/sprint-1-demo.openapi.json
```

### Conception, exploitation et recette

```text
specs/adr/0001-socle-technique.md
specs/adr/0002-persistance-et-reprise.md
specs/contract-sources.json
specs/sprints/sprint-1-plan.md
specs/sprints/sprint-1-recette.md
specs/sprints/sprint-1-bilan.md
docs/developpement.md
docs/exploitation.md
docs/wordpress-org.md
```

### Spécifications désormais présentes localement

Les dix fichiers suivants sont issus du dossier de préparation, sans modification de celui-ci ; le PRD et les références courantes sont mis à jour :

```text
specs/prd.md
specs/fonctionnalites.md
specs/plan-developpement.md
specs/contracts/README.md
specs/contracts/contrats-systeme.md
specs/contracts/modele-donnees.md
specs/contracts/invariants.md
specs/contracts/openapi.json
specs/contracts/examples.json
specs/contracts/validate_contracts.py
```

Les répertoires `vendor/`, `node_modules/`, les bases locales, rapports et archives seront générés et exclus du versionnage. Les lockfiles seront versionnés. Les tests, fixtures et outils de développement seront exclus du paquet installable.

## 6. Matrice de passage du Sprint 1

| Critère | Exigences et invariants | Preuves attendues |
|---|---|---|
| **S1-A1** Installation et réactivation | NFR-10/12, INV-01 | `LifecycleTest`, `MigrationTest` : installation vierge, répétition, interruption/reprise, schéma et données conservés |
| **S1-A2** Navigation et droits | WP-02, NFR-01/02, INV-02/20 ; socle REC-10 | Tests admin/REST, objets fictifs hors portée, parcours navigateur et clavier |
| **S1-A3** Validation et erreurs | NFR-02, INV-19/20 ; CT-15/16 | DTO imbriqués, types incorrects, champs inconnus, versions et codes HTTP ; aucun effet après refus |
| **S1-A4** Idempotence | INV-18 ; CT-06 | Replay, corps différent, requêtes concurrentes, droit révoqué avant replay |
| **S1-A5** Durabilité et reprise | NFR-14, INV-15/18 | Rollback mutation/audit/outbox, crash worker, bail expiré, concurrence et effet interne unique |
| **S1-A6** CI et conservation | NFR-10/11/12/17/18 ; préparation REC-12 | Validation OpenAPI complète, contrôle Python complémentaire, PHPUnit, WPCS, Playwright, désinstallation conservatrice et suppression explicite ; Plugin Check et porte WordPress.org |

CT-06, CT-15 et CT-16 seront démontrés sur le socle puis rejoués sur les routes métier aux sprints suivants. REC-10 et REC-12 ne seront pas déclarés intégralement couverts tant que les objets métier correspondants n'existent pas.

La CI utilisera un validateur OpenAPI complet compatible 3.1.1, versionné dans les dépendances ; le script Python fourni restera complémentaire. Une CI distante ne sera annoncée comme exécutée qu'avec une preuve d'exécution sur un dépôt distant autorisé.

Les tests suivront [test-runner](../../.claude/skills/test-runner.md) : cas nominaux, erreurs et permissions ; base isolée identifiée avant toute réinitialisation ; concurrence par connexions indépendantes ; assertions sur l'état persisté, pas uniquement sur les réponses HTTP. Les tests d'installation incluront une base technique peuplée, la conservation des objets étrangers au plugin et l'accès direct à `uninstall.php` sans effet.

La recette manuelle comprendra installation/activation/désactivation, navigation clavier, comptes administrateur/gestionnaire/formateur et absence de perturbation des autres écrans WordPress. Le worker sera également vérifié sans dépendance au trafic navigateur, avec procédure de déclenchement serveur documentée.

## 7. Exécution après validation et clôture

Après validation explicite, lire et appliquer `execute-sprint` puis suivre les six étapes imposées :

1. Isolation Git.
2. Implémentation.
3. Tests QA.
4. Audit sécurité et performance.
5. Recette.
6. Clôture.

Toute correction pendant l'audit ou la recette repassera par les tests et contrôles concernés. Un contrôle obligatoire non exécuté restera bloquant.

**Coupe-circuit :** si la même erreur de test ou d'audit persiste après trois tentatives de correction sur un même fichier, arrêter immédiatement, sans quatrième tentative. Conserver les compteurs entre étapes et reprises. Fournir le log complet avec seuls secrets/données personnelles masqués, les trois tentatives, le fichier et les critères bloqués, puis demander l'intervention de l'utilisateur.

La clôture appliquera [sprint-closer](../../.claude/skills/sprint-closer.md) : PHPDoc, CHANGELOG avec changements réels, fiche de recette et bilan traçable. Le bilan associera exigences, fichiers, tests, commandes, environnement, résultats et limites. Aucun succès ne sera présumé et aucun passage au Sprint 2 ne sera implicite.

## 8. Décisions et limites actuelles

### Décisions soumises à validation

- Le périmètre et l'architecture du Sprint 1 décrits ici.
- Une base de code neuve.
- La matrice technique proposée.
- L'initialisation Git locale puis la branche `sprint-1`.
- La copie des spécifications et la mise à jour PRD 0.4 sont désormais demandées et réalisées ; elles ne valent pas validation du sprint.
- Le démonstrateur REST limité aux environnements de test et exclu du paquet distribuable.

Les paramètres commerciaux, pays/devise, fiscalité, provisioning Stripe et durées de conservation métier restent ouverts pour les lots qui en dépendent. Ce cadrage ne les fixe pas implicitement.

### État des vérifications

Le cadrage et les spécifications sont enregistrés. La demande ultérieure a ajouté wp-env, Plugin Check, son wrapper et ses tests Node ; les résultats d'outillage sont consignés dans [la procédure](../../docs/wordpress-org.md). **Aucun audit ni test runtime du code produit n'est revendiqué. PHPUnit est non applicable à ces changements documentaires et Node.** Les critères S1-A1 à S1-A6 restent à démontrer après implémentation.

L'enregistrement de ce document ne lance pas le sprint. La demande ultérieure autorise les spécifications et l’outillage wp-env/Plugin Check. L’initialisation Git et le code métier restent soumis au cadrage du sprint.


## 9. Complément WordPress.org demandé le 10 novembre 2026

Appliquer NFR-17/18 et [la procédure](../../docs/wordpress-org.md) : API HTTP WordPress, fonctions dangereuses interdites, préfixe `sfbk_`, bibliothèques du cœur réutilisées ; Version PHP = Stable tag, sources lisibles dans le paquet, services tiers documentés, GPLv2+ compatible. Plugin Check officiel est installé dans wp-env et exécuté avant clôture avec contrôles runtime. Erreurs et avertissements non traités bloquent ; zéro alerte ne vaut pas acceptation WordPress.org.

wp-env utilise MariaDB pour cette vérification WordPress.org uniquement. Cela ne prouve pas les migrations/verrous MySQL 8.4 retenus dans le cadrage : l'environnement transactionnel cible et sa matrice restent à préparer et tester au Sprint 1. Aucune compatibilité MariaDB métier n'est annoncée.

Le nom `session-flow-bookings`, les exigences WordPress.org et l'outillage demandé sont décidés ; les autres choix techniques proposés et le lancement du Sprint 1 restent en attente de validation.

## Validation et précisions d'exécution

Cadrage validé dans la conversation, puis initialisation/commit Git autorisés.
Référence initiale cdf1836 ; dépôt GitHub : https://github.com/lalainarahajason/session-flow-booking.git.
Version produit proposée 0.1.0 retenue pour l'incrément, schéma 0001.
Le démonstrateur utilise sfbk-test/v1 (préfixe quatre lettres), sans modifier booking/v1.
La recette et les compteurs sont suivis dans sprint-1-execution.md.
Les rôles, migrations et primitives ont des tests MySQL ; les scénarios métier des sprints ultérieurs restent hors incrément.
