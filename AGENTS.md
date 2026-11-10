# session-flow-bookings — Instructions du projet

## Dossier de travail

Le plugin se nomme **session-flow-bookings** (slug/text domain identiques, namespace `SessionFlowBookings`, préfixe `sfbk_` / `SFBK_`). Le dossier physique de travail reste `session-flow-booking` ; ne pas le renommer implicitement. Ne pas traiter le dossier voisin `ameliabooking` comme le code du produit : il sert de référence tant qu'une réutilisation n'est pas explicitement décidée. Résoudre les chemins relatifs depuis cette racine.

## Audit WordPress obligatoire

Pour toute analyse, revue ou modification du code WordPress de ce projet, lire et appliquer le skill [audit-wp](.claude/skills/audit-wp.md).

- Avant une modification, identifier les points d'entrée et les risques dans le périmètre concerné.
- Avant de terminer une implémentation, auditer le diff et ses dépendances directes avec ce skill ; rapporter les tests et les limites.
- Pour une demande d'audit global, examiner tout le code du produit selon le périmètre du skill.
- Les tâches uniquement documentaires ne nécessitent pas d'audit de code ; ne pas prétendre en avoir effectué un.

**Règle stricte : chaque audit doit vérifier systématiquement l'échappement des sorties (`esc_html()`, `esc_attr()` et fonctions adaptées au contexte), la validation des permissions (`current_user_can()` et portée de l'objet) et l'utilisation de `$wpdb->prepare()` pour les requêtes SQL brutes dynamiques.** Toute protection équivalente ou non-applicabilité doit être expliquée et étayée, conformément au skill. Aucun des trois contrôles ne peut être omis du bilan.

Vérifier également les risques XSS, CSRF, SQLi, la conformité WPCS et l'optimisation des hooks. Un nonce ne remplace pas une permission ; la sanitation d'entrée ne remplace pas l'échappement ; la présence de `prepare()` ne suffit pas si les données ont déjà été interpolées.

Le fichier `.claude/skills/audit-wp.md` est le point d'entrée local demandé. Cette référence explicite impose sa lecture aux agents qui suivent AGENTS.md ; elle ne suppose pas qu'un outil découvre automatiquement ce nom de fichier comme un skill natif.

Conserver les fichiers tiers et générés hors des corrections automatiques. Ne pas modifier globalement les règles WPCS pour faire disparaître les alertes. Ne pas annoncer une faille comme reproduite sans preuve ni conclure à une sécurité totale sur la seule base d'un outil.


## Skills de développement obligatoires

Lire les skills suivants selon la tâche et les appliquer dans cet ordre logique. Leur référence ici impose leur prise en compte aux agents qui suivent AGENTS.md, sans supposer une découverte native des fichiers `.md` par tous les outils.

| Skill | Déclenchement | Exigence de passage |
|---|---|---|
| [architecture-guard](.claude/skills/architecture-guard.md) | Avant conception, code ou revue ; puis sur le diff final | Conformité PRD, modèles, API et invariants ; écarts explicites |
| [db-migration](.claude/skills/db-migration.md) | Stockage, schéma, installation, mise à jour ou désinstallation | dbDelta adapté, versions vérifiées, reprise et tests de conservation/suppression |
| [test-runner](.claude/skills/test-runner.md) | Toute tâche de code et chaque validation de sprint | PHPUnit nominal/erreurs/permissions pertinent exécuté ; aucun succès présumé |
| [wp-performance](.claude/skills/wp-performance.md) | Assets, hooks, requêtes, caches ou jobs | Chargement conditionnel, Transients sûrs, mesures et invalidation |
| [sprint-closer](.claude/skills/sprint-closer.md) | Fin de sprint ou demande de bilan | PHPDoc, CHANGELOG, critères et preuves de recette à jour |

Le skill [audit-wp](.claude/skills/audit-wp.md) reste obligatoire pour le code WordPress, en complément de ces contrôles.

### Règles transversales

- Vérifier les exigences avant d'implémenter ; ne pas modifier un contrat ou affaiblir un test uniquement pour faire passer du code non conforme.
- Écrire ou compléter les tests PHPUnit des comportements PHP, avec cas nominaux, erreurs et permissions applicables ; les exécuter avant validation. Les tâches documentaires et purement frontend suivent les exceptions explicites de test-runner, sans faux tests PHP.
- Un test obligatoire impossible à exécuter reste « non vérifié / bloqué ». Ne pas déclarer la tâche ou le sprint validé.
- Les Transients ne sont jamais une autorité de réservation, de paiement, de permission ou de verrouillage critique.
- La désactivation ne détruit pas les données métier. `uninstall.php` protège son accès et conserve les données par défaut ; nettoyage complet uniquement selon le choix explicite décrit dans db-migration.
- Avant clôture : contrôler PHPDoc, mettre à jour le CHANGELOG avec les changements réels, produire le bilan et appliquer les critères de passage du plan de sprints.
- Si les spécifications ne sont pas encore dans ce projet, leur source de préparation est `../ameliabooking/specs/`, en lecture seule. Signaler les versions utilisées et toute divergence ; ne pas déplacer les fichiers implicitement.

## Exécution autonome des sprints

Lire et appliquer [execute-sprint](.claude/skills/execute-sprint.md) **uniquement après validation explicite par l'utilisateur du cadrage du sprint identifié**. Une validation déjà donnée reste valable. Créer ce skill ou demander un plan ne déclenche aucun sprint.

Le pipeline comporte exactement six étapes successives, sans confirmation intermédiaire de routine :

1. **Isolation Git :** créer `git checkout -b sprint-[numero]` avant toute modification de fichier. Pour une reprise prouvée du même sprint, réutiliser sa branche sans la recréer. Un dépôt absent, une branche ambiguë ou du travail étranger non isolé bloque l'écriture.
2. **Implémentation :** appliquer architecture-guard et db-migration si la BDD change.
3. **Tests QA :** appliquer test-runner, écrire et exécuter PHPUnit ; corriger puis relancer jusqu'à zéro erreur et zéro échec, sous réserve du coupe-circuit.
4. **Audit Sécurité & Performance :** appliquer audit-wp et wp-performance ; corriger les anomalies du périmètre et repasser par QA après toute correction.
5. **Recette :** vérifier tous les critères du plan ; coder les éléments manquants puis rejouer les étapes 3 et 4 avant nouvelle recette.
6. **Clôture :** appliquer sprint-closer pour PHPDoc, CHANGELOG.md et rapport final complet fondé sur des preuves.

**Coupe-circuit strict :** si une même erreur de test ou d'audit persiste après trois tentatives de correction sur un même fichier, arrêter immédiatement le pipeline, sans quatrième tentative. Afficher le log complet (avec masquage des seuls secrets/données personnelles), les trois tentatives, le fichier et les critères bloqués, puis demander l'intervention de l'utilisateur. Conserver les compteurs entre étapes et reprises. Cette règle prévaut sur les consignes de poursuite jusqu'à réussite des autres skills.

L'exécution autonome autorise les corrections nécessaires dans le périmètre validé, y compris pendant l'audit. Elle n'autorise ni contournement des permissions, ni tests destructeurs en production, ni publication, ni fusion, ni lancement implicite du sprint suivant. Ne jamais déclarer validé un contrôle obligatoire non exécuté.

## Validation WordPress.org

Appliquer les exigences NFR-17/18 du PRD 0.4 et la [procédure wp-env / Plugin Check](docs/wordpress-org.md). Chaque clôture exige Plugin Check statique/runtime et la revue manuelle du paquet : HTTP WordPress, fonctions dangereuses, préfixes, bibliothèques du cœur, versions, sources, services tiers et GPLv2+. Aucun résultat non exécuté ne vaut validation. Les tâches de documentation/outillage autorisées séparément ne déclenchent pas un sprint.

Les spécifications locales sont désormais la référence (PRD 0.4, contrats 0.1.1). Les fichiers du dossier de préparation restent en lecture seule ; leurs divergences historiques sont tracées dans `specs/contract-sources.json`.
