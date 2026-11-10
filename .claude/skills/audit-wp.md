---
name: audit-wp
description: Auditer le code WordPress de session-flow-bookings selon WPCS, les risques XSS, CSRF et SQLi, les permissions et la performance des hooks. Appliquer lors des revues, avant une modification PHP/JS ou API, puis sur le diff avant de terminer une implémentation.
---

# Audit WordPress

## Périmètre et méthode

1. Lire le fichier AGENTS.md du projet, les instructions des sous-dossiers concernés et les spécifications disponibles. Identifier le code du produit, les dépendances et les fichiers générés.
2. Examiner le diff lorsque la demande porte sur une modification ; suivre aussi les routes, callbacks, templates et helpers nécessaires pour comprendre son exécution. Pour un audit global, inventorier les points d'entrée : REST, AJAX connecté/non connecté, admin-post, formulaires, shortcodes, blocs, cron, webhooks et accès PHP directs.
3. Utiliser `rg` pour repérer les zones sensibles, puis lire les chemins d'exécution. Une occurrence ou une absence de fonction dans un fichier n'est pas une preuve suffisante de vulnérabilité.
4. Auditer sans modifier le code par défaut. Si une correction est demandée, appliquer le correctif minimal et tester la régression. Ne pas modifier les dépendances ni lancer un correcteur global pour masquer les constats.
5. Exécuter les vérifications disponibles, préciser les outils manquants et distinguer analyse statique, hypothèse et reproduction locale. Ne pas effectuer de test destructeur ou sur un service tiers.

## Règle stricte — trois contrôles systématiques

Chaque audit DOIT examiner et rendre compte des trois contrôles suivants, même si PHPCS ne remonte aucune erreur. Aucun contrôle ne peut être omis. Utiliser les statuts « conforme », « anomalie », « non applicable avec justification » ou « non vérifié avec motif ». Un contrôle non vérifié interdit de conclure que l'audit est complet.

### 1. Échappement des sorties — XSS

- Vérifier chaque donnée dynamique rendue, y compris les données stockées, traductions, options et métadonnées : suivre sa source jusqu'au contexte de sortie.
- Exiger `esc_html()` pour le texte HTML et `esc_attr()` pour les attributs ordinaires, ou leur variante d'échappement/traduction appropriée.
- Employer `esc_url()` pour href/src, `esc_textarea()` pour textarea et `wp_kses_post()` ou une liste autorisée explicite pour le HTML intentionnel. Ne pas échapper du HTML autorisé comme du texte par défaut.
- Vérifier l'échappement au dernier moment. `sanitize_text_field()` à l'entrée ne remplace pas l'échappement à la sortie ; `esc_attr()` ne valide pas le protocole d'une URL.
- Auditer `echo`, `print`, `printf`, interpolation de templates, HTML d'administration, `innerHTML`, `v-html` et équivalents. Vérifier les garanties d'échappement du moteur de template et ses contournements.
- Pour les réponses JSON, utiliser une sérialisation correcte et un Content-Type cohérent ; ne pas appliquer `esc_html()` indistinctement au JSON. Auditer séparément son insertion HTML/JS dans le navigateur, notamment les scripts inline et fermetures de balises.

### 2. Permissions et intention — accès non autorisé / CSRF

- Vérifier systématiquement `current_user_can()` pour chaque lecture sensible ou action privilégiée, directement ou via un helper traçable. Tester la capacité adaptée et la portée de l'objet : propriétaire, formateur affecté, site courant.
- Refuser comme justification suffisante `is_admin()`, un menu masqué, une connexion active ou une comparaison au seul nom d'un rôle.
- Pour les mutations utilisant une session/cookie, vérifier un nonce côté serveur adapté au contexte (`check_admin_referer`, `check_ajax_referer`, vérification REST WordPress ou équivalent explicite). Le nonce ne remplace jamais les permissions et n'est pas un mécanisme d'authentification invité.
- Contrôler chaque `permission_callback` REST. `__return_true` n'est acceptable que pour une route volontairement publique, documentée et ne retournant pas de données privées.
- Pour les réservations publiques et accès invités, documenter l'absence légitime de `current_user_can()` et vérifier l'autorisation alternative : jeton limité, propriété, expiration, révocation et protections anti-abus. Ne pas imposer une connexion WordPress à une réservation publique par erreur.
- Pour les webhooks, vérifier signature sur corps brut, compte fournisseur, environnement, déduplication et portée ; ne pas demander un nonce WordPress à un prestataire externe.
- Vérifier absence de mutation sensible par GET, comportement en cas de nonce absent/expiré, accès avec un autre compte et appels directs contournant l'interface.

### 3. Accès SQL — SQLi

- Inventorier les appels SQL et vérifier systématiquement `$wpdb->prepare()` pour les requêtes SQL brutes contenant des valeurs dynamiques. Utiliser les placeholders et arguments adaptés, sans interpolation préalable de données non fiables.
- Vérifier que les placeholders ne sont pas quotés manuellement, que les arguments correspondent et que la chaîne SQL préparée est effectivement exécutée.
- Les méthodes `$wpdb->insert()`, `$wpdb->update()` et `$wpdb->delete()` avec formats appropriés, ou une abstraction réellement paramétrée, peuvent fournir la protection nécessaire : documenter le chemin exact au lieu d'ajouter artificiellement `prepare()`.
- Les requêtes entièrement constantes n'exigent pas un appel sans placeholder. Ne pas confondre une requête non préparée constante avec une injection exploitable.
- Vérifier les identifiants et fragments dynamiques : tables, colonnes, ORDER BY, ASC/DESC, IN et LIMIT. Utiliser des listes autorisées pour les choix structurels ; `%i` uniquement si la version WordPress supportée le permet. Un placeholder de valeur n'est pas un placeholder de syntaxe SQL.
- Pour LIKE, appliquer `$wpdb->esc_like()` avant de construire l'argument transmis à `prepare()`. Pour IN, générer un placeholder par valeur et traiter explicitement une liste vide.
- `esc_sql()`, `sanitize_text_field()` ou une conversion de type isolée ne constituent pas un remplacement général des requêtes paramétrées.

## WordPress.org — contrôles bloquants

Appliquer les [problèmes fréquents de revue](https://developer.wordpress.org/plugins/wordpress-org/common-issues/) et les [directives du répertoire](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/), puis nos exigences locales ci-dessous. Vérifier les règles officielles lors d'une préparation de soumission ; cette liste n'est pas exhaustive et Plugin Check ne garantit pas l'acceptation humaine.

- **HTTP serveur :** tout appel HTTP du code propre au plugin passe par l'API WordPress (`wp_remote_get`, `wp_remote_post`, `wp_remote_request`, variantes `wp_safe_remote_*` pour une URL influencée par l'utilisateur). Bloquer cURL direct, sockets et lecture d'URL par `file_get_contents`/`fopen` comme transports réseau. Vérifier erreurs `WP_Error`, délais, TLS, redirections et risque SSRF. Une lecture de fichier local n'est pas un appel HTTP. WordPress.org permet le transport cURL de certaines bibliothèques tierces : notre choix projet est de raccorder aussi les appels Stripe à un adaptateur HTTP WordPress, sans modifier automatiquement vendor.
- **Exécution dangereuse :** règle locale stricte, bloquer dans le code PHP distribué les appels à `eval`, `exec`, `shell_exec`, `system`, `passthru`, `popen`, `proc_open`, l'opérateur backtick, `assert` exécutant une chaîne et toute construction dynamique équivalente. Examiner les appels indirects et les dépendances distribuées ; une occurrence en commentaire n'est pas une faille. Les outils Node/shell de développement exclus du paquet peuvent lancer des processus : ils ne constituent pas du code PHP du plugin et doivent rester isolés de toute entrée HTTP.
- **Préfixe unique :** au moins quatre caractères distinctifs avant le séparateur pour les symboles globaux, options et tables propres au produit ; convention retenue `sfbk_` / `SFBK_`, namespace `SessionFlowBookings`. `sfb_` est abandonné. Les classes/fonctions sous namespace n'ont pas besoin d'un second préfixe ; les méthodes ne sont pas des symboles globaux. Inclure globals JS, hooks propres, transients et identifiants d'assets dans la revue de collisions. Le préfixe WordPress du site ne remplace pas le préfixe produit des tables. Ne pas renommer les API du cœur ou les symboles tiers ; inventorier ces dépendances séparément. Les identifiants contractuels existants (`booking/v1`, capacités `booking_*`, cookies) restent ceux d'OpenAPI jusqu'à une révision explicite du contrat ; ils ne sont pas des symboles PHP globaux/options/tables.
- **Bibliothèques du cœur :** aucune copie embarquée de jQuery, React/wp-element, PHPMailer, SimplePie ou autre bibliothèque déjà fournie par la version WordPress ciblée. Examiner aussi les dépendances transitives et les bundles compilés, déclarer les handles WordPress via les API d'enqueue et externaliser les dépendances de build. Une exclusion vendor de PHPCS ne dispense pas de cet inventaire.
- **Plugin Check officiel :** installer puis exécuter `npm run plugin-check:install` et `npm run plugin-check` dans le Docker wp-env isolé, selon [la procédure du projet](../../docs/wordpress-org.md). Inclure les contrôles runtime via `--require=.../plugin-check/cli.php`. Conserver version, commande, code de sortie, rapport complet et périmètre contrôlé. Aucun `--ignore-errors`, `--ignore-warnings`, filtre de catégorie ou exclusion générale pour obtenir artificiellement un succès. Toute erreur ou avertissement non résolu bloque le contrôle automatisé ; une non-applicabilité exige une preuve et un arbitrage documenté, jamais une suppression silencieuse. Un outil absent, une cible absente ou un contrôle runtime non exécuté reste non vérifié/bloqué.

## Conformité WPCS

- Lire la configuration PHPCS existante et les versions du projet. Chercher `phpcs.xml`, `phpcs.xml.dist`, `.phpcs.xml`, `.phpcs.xml.dist` et les scripts Composer.
- Si disponible, lancer `vendor/bin/phpcs -i`, puis PHPCS avec la configuration du projet sur les fichiers concernés. Sans configuration, utiliser la norme installée `WordPress` sur le code du produit ; exclure explicitement vendor, node_modules et les bundles générés de ce contrôle de style.
- Contrôler indentation, noms, comparaisons, documentation PHP, internationalisation et domaine de traduction, validation/sanitation des entrées et usage des API WordPress.
- Lire les suppressions `phpcs:ignore` et exclusions de sniffs : elles doivent être locales et justifiées, jamais masquer une vérification de sécurité.
- Rapporter commande, périmètre, version des outils et résultat. Si PHPCS/WPCS manque, poursuivre la revue manuelle et indiquer que la conformité automatique n'a pas été vérifiée. Ne pas installer silencieusement des dépendances.
- Distinguer défaut de style, bug fonctionnel et risque de sécurité. Un passage PHPCS ne prouve pas l'absence de vulnérabilités.

## Hooks, performances et cycle de vie

- Relever les `add_action` / `add_filter`, contexte, priorité, arguments attendus et nombre d'enregistrements. Vérifier les retours des filtres et les récursions possibles.
- Repérer les lectures DB non bornées, requêtes N+1, appels HTTP synchrones et traitements lourds dans `init`, `wp_loaded`, `admin_init`, `the_content` ou les hooks déclenchés fréquemment.
- Charger scripts/styles uniquement sur les pages nécessaires, avec les hooks d'enqueue adaptés. Vérifier que les styles du plugin ne perturbent pas les écrans WordPress voisins.
- Éviter `flush_rewrite_rules()` à chaque requête ; vérifier son usage limité aux changements qui le nécessitent.
- Contrôler la programmation cron sans doublons, le nettoyage des tâches à la désactivation, les reprises idempotentes et la prévention des exécutions concurrentes. Vérifier le comportement sur site sans trafic.
- Vérifier pagination, invalidation des caches et portée des clés. Un cache partagé ne doit jamais exposer les données d'un autre utilisateur ni devenir la seule autorité de réservation.
- Vérifier migrations répétables, activation sans effets multiples et désinstallation respectant le choix de conservation des données.
- Étayer les problèmes de performance par fréquence, volume, nombre de requêtes ou mesure si disponible. Une priorité de hook arbitraire n'est pas à elle seule une anomalie.

## Vérification et rapport

Pour chaque constat, fournir : sévérité, fichier et ligne, point d'entrée, source et destination de la donnée, droits nécessaires, scénario déclencheur, impact, preuve, correction proposée et test de non-régression. Distinguer « confirmé par lecture », « reproduit localement » et « hypothèse à vérifier ». Ne pas annoncer une SQLi ou une XSS uniquement sur la base d'un grep.

Prioriser critique/élevée/moyenne/faible selon impact et préconditions. Ne pas transformer un défaut WPCS cosmétique en faille exploitable.

Inclure obligatoirement ce bilan :

| Contrôle | Statut | Preuve / fichiers | Limites ou action |
|---|---|---|---|
| Sorties : esc_html / esc_attr et contexte | À renseigner | À renseigner | À renseigner |
| Permissions : current_user_can et portée objet | À renseigner | À renseigner | À renseigner |
| SQL : $wpdb->prepare ou paramétrage démontré | À renseigner | À renseigner | À renseigner |
| CSRF et authentification des points publics | À renseigner | À renseigner | À renseigner |
| WPCS | À renseigner | À renseigner | À renseigner |
| Hooks et cycle de vie | À renseigner | À renseigner | À renseigner |
| API HTTP WordPress / transports tiers | À renseigner | À renseigner | À renseigner |
| Fonctions d'exécution dangereuses | À renseigner | À renseigner | À renseigner |
| Préfixe unique et namespace | À renseigner | À renseigner | À renseigner |
| Bibliothèques du cœur non dupliquées | À renseigner | À renseigner | À renseigner |
| Plugin Check statique et runtime | À renseigner | À renseigner | À renseigner |

Tests minimaux pertinents : valeurs HTML/attributs non fiables rendues inertes, requête sans nonce rejetée, rôle insuffisant et objet d'autrui refusés, chaînes SQL traitées comme valeurs, hooks non dupliqués après réactivation. Exécuter les tests dans l'environnement local autorisé et vérifier l'état persisté, pas seulement le code HTTP.

Terminer par les risques résiduels et les vérifications non effectuées. S'il n'y a aucun constat, indiquer « aucune anomalie identifiée dans le périmètre examiné », sans certifier la sécurité globale. Ne pas créer automatiquement un rapport dans le dépôt sauf demande de fichier.

## Références officielles

- WPCS : https://github.com/WordPress/WordPress-Coding-Standards
- Échappement : https://developer.wordpress.org/apis/security/escaping/
- Nonces : https://developer.wordpress.org/apis/security/nonces/
- Permissions : https://developer.wordpress.org/reference/functions/current_user_can/
- Requêtes préparées : https://developer.wordpress.org/reference/classes/wpdb/prepare/
