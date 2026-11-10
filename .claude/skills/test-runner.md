---
name: test-runner
description: Exiger des tests PHPUnit nominaux, d'erreur et de permissions pour tout comportement PHP nouveau ou modifié de session-flow-bookings, puis leur exécution avant validation de tâche ou clôture de sprint.
---

# Tests et validation de tâche

## Règle de passage stricte

Pour chaque comportement PHP nouveau ou modifié, écrire ou compléter des tests PHPUnit significatifs couvrant le cas nominal, les erreurs et les permissions applicables, puis les exécuter. Réutiliser les tests existants s'ils démontrent réellement le contrat ; ne pas dupliquer des assertions triviales pour augmenter artificiellement la couverture.

Pour une tâche uniquement documentaire, indiquer PHPUnit non applicable avec justification et vérifier liens/exemples. Pour une tâche exclusivement JavaScript/CSS, exécuter les tests frontend pertinents et les tests PHP d'intégration affectés ; ne pas écrire un test PHPUnit qui prétend prouver un rendu navigateur.

## Préparation

- Lire AGENTS.md, le sprint actif, les IDs PRD, INV, CT et REC concernés. Inventorier `composer.json`, lockfile, `phpunit.xml*`, bootstrap et scripts CI ; utiliser la version de PHPUnit compatible avec la matrice du projet, sans mise à jour globale opportuniste.
- Si l'infrastructure PHPUnit est absente, la mettre en place dans le périmètre autorisé, avec bootstrap explicite. Si des dépendances ou un accès réseau manquent, signaler ce qui empêche l'exécution et ne pas déclarer les tests réussis.
- Utiliser une base WordPress de test distincte, vérifiée avant toute réinitialisation. Refuser l'exécution destructive si la cible de test n'est pas identifiée sans ambiguïté.
- Employer des données fictives et des horloges contrôlables. Aucun paiement réel, email réel ou secret de production dans les fixtures et logs.

## Couverture minimale

| Niveau | Vérifications |
|---|---|
| Unitaire | Calculs, validations, transitions, erreurs, arrondis, échéances et décisions d'autorisation isolables |
| Intégration WordPress | Capabilities, propriétaire d'objet, nonce, AJAX/REST, hooks, migrations et sérialisation réelle |
| Base concurrente | Allocation dernière place, chevauchements, remboursements concurrents, unicité/idempotence et rollback |
| Fournisseurs | Adaptateur simulé contrôlable : succès, refus, timeout, événements répétés ou désordonnés, mauvaise signature et reprise |
| Interface | Parcours manuel ou test navigateur pour clavier, mobile, formulaires, chargement des assets et messages |

Inclure selon la tâche : invité, rôle insuffisant, objet d'un autre acheteur, formateur non affecté, nonce absent/invalide ; payload invalide/champ inconnu ; état expiré/annulé ; version obsolète ; duplication d'intention. Vérifier à la fois réponse et absence d'effet persistant pour un refus.

Tester la concurrence avec des connexions/processus indépendants et une synchronisation contrôlée. Deux appels séquentiels, un sleep arbitraire ou un repository mocké ne prouvent pas l'absence de course. Ne pas remplacer la base cible par SQLite pour valider des verrous spécifiques à MySQL/MariaDB.

## Exécution obligatoire

1. Pour une correction, obtenir si possible un test échouant sur le défaut avant le correctif ; documenter si ce n'est pas reproductible.
2. Lancer les tests ciblés, puis les suites concernées et les régressions des sprints précédents. Utiliser le script Composer existant ou `vendor/bin/phpunit -c <configuration vérifiée>` ; ne pas inventer un nom de suite.
3. Lancer la validation OpenAPI, WPCS et les tests frontend lorsqu'ils sont affectés. Une validation de schéma ne remplace pas un test de comportement.
4. Corriger les échecs sans désactiver de test, affaiblir d'assertion ou remplacer une preuve métier par un mock complaisant.
5. Rejouer les tests après le dernier changement pertinent. Pour une clôture de sprint, passer la suite complète exigée par le plan, pas seulement le dernier test modifié.

## Compte rendu obligatoire

Indiquer : environnement/configuration, commandes exactes, code de sortie, tests/assertions si disponibles, suites passées/échouées, skipped/incomplete/risky, cas manuels réalisés, limites et critères non couverts.

Statuts autorisés : « validé » si les contrôles obligatoires passent ; « échoué » en présence d'échecs ; « non vérifié / bloqué » si l'exécution requise est impossible. Un test critique sauté ou une dépendance manquante interdit « validé ». Ne jamais annoncer une couverture mesurée sans rapport de couverture.
