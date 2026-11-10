---
name: execute-sprint
description: Exécuter un sprint de session-flow-bookings uniquement après validation explicite de son cadrage par l'utilisateur. Enchaîner isolation Git, implémentation, PHPUnit, audit, recette et clôture ; arrêter immédiatement après trois corrections infructueuses d'une même erreur sur un même fichier.
---

# Exécution autonome stricte d'un sprint

## Condition de déclenchement

Déclencher ce pipeline uniquement après validation explicite par l'utilisateur du cadrage du sprint identifié : numéro, périmètre et critères d'acceptation. Une validation déjà donnée dans la conversation reste valable ; ne pas la redemander. Une demande de créer/modifier ce skill, un plan proposé ou une approbation générale du produit ne constitue pas cette validation.

Si la validation manque ou si le sprint n'est pas identifiable, rester en lecture seule et demander précisément la validation du cadrage concerné. Ne créer ni branche ni fichier du sprint avant cette validation.

Après validation, exécuter les six étapes consécutivement sans confirmation intermédiaire. Donner de courtes mises à jour de progression. Ne pas arrêter après un plan ou une implémentation partielle. Les seules interruptions sont le coupe-circuit ci-dessous et les blocages externes réels qui rendent la suite impossible ou non autorisée.

Lire préalablement en lecture seule AGENTS.md, le cadrage, le plan de développement et les six skills référencés ci-dessous. Résoudre les spécifications comme indiqué par architecture-guard ; ne pas confondre le produit et le code Amelia de référence.

## 1. Isolation Git

**Avant toute modification de fichier**, y compris un journal de progression, un fichier généré, les dépendances ou les tests :

1. Vérifier en lecture seule la racine du dépôt, la branche courante et `git status --short`. Vérifier que le dépôt correspond bien à session-flow-bookings.
2. Identifier la branche de départ validée. Si aucune autre n'est désignée, utiliser la branche courante, sauf HEAD détachée ou incohérence avec le cadrage.
3. Pour un nouveau sprint, exiger un arbre propre et l'absence de la branche cible, puis exécuter exactement :

   ```sh
   git checkout -b sprint-[numero]
   ```

   Remplacer `[numero]` par le numéro validé, par exemple `git checkout -b sprint-1`.

4. Vérifier que la branche active est bien `sprint-[numero]` avant de commencer l'étape 2. Si Git échoue, ne modifier aucun fichier.

Ne pas initialiser automatiquement un dépôt manquant, écraser une branche avec `-B`, supprimer une branche existante, stasher, committer ou réinitialiser le travail de l'utilisateur pour faire passer cette étape. Signaler le blocage précis si une décision est nécessaire.

**Reprise :** si cette même branche a déjà été créée par le pipeline pour ce sprint validé, vérifier sa provenance et l'état enregistré ; reprendre dessus sans la recréer. Les modifications du sprint en cours ne sont pas un arbre sale étranger. En revanche, une branche préexistante non attribuable au sprint ou des modifications non identifiées exigent un arbitrage avant écriture.

Après isolation seulement, tenir `specs/sprints/sprint-N-execution.md` : cadrage validé, branche et commit de départ, étape courante, critères, commandes/résultats, compteurs de correction et prochaine action. Préserver ce suivi à la reprise.

## 2. Implémentation

**Objectif :** écrire le code défini par le cadrage validé.

- Appliquer [architecture-guard](architecture-guard.md) : conformité stricte PRD, modèles persistés, API, permissions, états et invariants. Relier chaque tâche aux critères du sprint et aux tests CT/REC pertinents.
- Appliquer [db-migration](db-migration.md) dès que la base, ses index, options de version, données persistées ou cycle de désinstallation sont modifiés.
- Réaliser les parcours complets du périmètre, y compris erreurs et contrôles serveur. Ne pas ajouter une fonctionnalité d'un autre sprint implicitement.
- Préserver les modifications de l'utilisateur et les dépendances tierces. Ajouter une PHPDoc utile pendant l'implémentation ; le contrôle final se fait à la clôture.
- Passer automatiquement à l'étape 3 lorsque l'implémentation prévue est testable.

## 3. Tests QA

**Objectif :** obtenir zéro erreur et zéro échec sur les tests requis.

- Appliquer [test-runner](test-runner.md). Écrire ou compléter les tests PHPUnit nominaux, erreurs et permissions des comportements PHP ; ajouter les tests d'intégration WordPress, concurrence, contrats et frontend nécessaires.
- Exécuter les commandes du projet dans un environnement local isolé, sans base ni paiement de production. Consigner commande, configuration, code de sortie, nombre de tests et résultat réel.
- En cas d'échec, identifier l'erreur et le fichier responsable, corriger le code, puis relancer le test de reproduction et les suites affectées. Répéter jusqu'à zéro erreur et zéro échec, **sous réserve du coupe-circuit de trois tentatives**.
- Ne jamais masquer un échec par suppression de test, affaiblissement d'assertion, changement de contrat non validé ou ignorance du code de sortie. Corriger un test réellement incorrect seulement avec justification issue du contrat.
- Une suite absente, un test obligatoire sauté/incomplet, un bootstrap indisponible ou un outil manquant ne vaut pas réussite. Déclarer le contrôle non vérifié et traiter le blocage.
- Rejouer les régressions exigées par le sprint avant de passer automatiquement à l'étape 4.

## 4. Audit Sécurité & Performance

**Objectif :** corriger les anomalies du périmètre livré avant la recette.

- Appliquer [audit-wp](audit-wp.md), puis [wp-performance](wp-performance.md). Auditer le diff et les chemins d'exécution nécessaires ; exécuter WPCS et les mesures pertinentes.
- Vérifier systématiquement l'échappement des sorties (`esc_html`, `esc_attr` et contexte), les permissions (`current_user_can` et propriété des objets), les requêtes SQL (`$wpdb->prepare()` ou paramétrage démontré), XSS, CSRF et SQLi.
- Vérifier chargement conditionnel des assets, hooks, requêtes, cache Transients et invalidation ; aucun cache n'est l'autorité des places, paiements ou permissions.
- Exécuter la porte WordPress.org : `npm ci`, `npm run env:start` (installation automatique de Plugin Check officiel), puis `npm run plugin-check`. Suivre [la procédure wp-env](../../docs/wordpress-org.md), vérifier contrôles statiques ET runtime, et conserver les rapports. Réinstaller explicitement avec `npm run plugin-check:install` si nécessaire. Aucun outil/cible manquant ni résultat non exécuté ne vaut réussite. Les critères HTTP WordPress, fonctions dangereuses, préfixes et bibliothèques du cœur sont bloquants même si Plugin Check ne les détecte pas.
- Corriger immédiatement toute alerte WPCS, faille ou anomalie de performance confirmée dans le périmètre. Une alerte non applicable doit être justifiée par une preuve ; ne pas désactiver globalement une règle ou modifier vendor pour la faire disparaître.
- Ajouter le test de régression approprié. Après toute correction runtime, migration, configuration ou test, repasser par l'étape 3 puis rejouer l'étape 4. Appliquer le même compteur de correction, sans le réinitialiser en changeant d'étape.
- Contrôler de nouveau la conformité architecture. Un problème hors périmètre est signalé avec son impact ; s'il compromet le sprint ou nécessite un changement de cadrage, ne pas déclarer le sprint validé.

Passer automatiquement à l'étape 5 seulement lorsque les contrôles requis sont exécutés, sans anomalie confirmée non résolue dans le périmètre.

## 5. Recette

**Objectif :** démontrer chaque critère d'acceptation du sprint.

1. Relire le plan de route effectif et les critères Sx-A, PRD et tests de recette associés.
2. Pour chaque critère, renseigner scénario, résultat attendu, résultat observé, preuve et statut. Vérifier les états persistés, pas seulement un message ou un code HTTP.
3. Exécuter les tests manuels avec les outils autorisés : parcours utilisateurs, rôles distincts, clavier/mobile, migrations ou paiements de test selon le sprint. Ne pas inventer des essais pilotes humains.
4. Si un élément manque ou échoue, l'implémenter dans le périmètre validé, appliquer db-migration si nécessaire et **repasser par les étapes 3 et 4**, puis reprendre la recette affectée et ses régressions.
5. Ne pas retirer un critère du plan ni le reporter implicitement pour finir le sprint. Appliquer le coupe-circuit aux erreurs de tests/audits rencontrées pendant ces boucles.

Passer automatiquement à l'étape 6 uniquement lorsque tous les critères obligatoires sont vérifiés. Un critère nécessitant une ressource externe ou des pilotes indisponibles reste bloquant et doit être signalé.

## 6. Clôture

**Objectif :** produire un incrément documenté et un rapport final complet.

- Appliquer [sprint-closer](sprint-closer.md) : générer ou compléter les commentaires PHPDoc conformes au code livré, mettre à jour `CHANGELOG.md` et produire `specs/sprints/sprint-N-bilan.md`.
- Décrire uniquement les changements réels ; utiliser la section non publiée tant qu'aucune release n'est décidée.
- Si la clôture ne change que des commentaires PHP, rejouer au minimum lint/WPCS. Toute modification runtime, contrat, migration ou test repasse par les étapes 3 et 4 puis la recette concernée. Une nouvelle erreur reste soumise au coupe-circuit.
- Faire correspondre les preuves à la dernière version des fichiers. Mettre à jour le suivi sans supprimer l'historique des corrections.
- Avant de déclarer la clôture, appliquer la porte WordPress.org de sprint-closer : Version PHP = Stable tag, sources non minifiées distribuées, services tiers documentés, GPLv2+ et notices compatibles. Rejouer Plugin Check sur le paquet final si le packaging/readme/code a changé depuis l'audit ; conserver ses empreintes et le rapport, sans effacer les résultats précédents. Un code de sortie non nul, contrôle incomplet ou résultat non traité bloque la clôture et suit le même coupe-circuit.
- Afficher le rapport final : branche, objectif, livrables, critères vérifiés, tests et commandes, résultats QA/WPCS/sécurité/performance, migrations, PHPDoc et CHANGELOG, anomalies et limites, décision de passage.

Déclarer « terminé — critères vérifiés » uniquement si les six étapes ont réussi. Ne pas committer, fusionner, pousser, publier, déployer ou commencer le sprint suivant sans instruction couvrant cette action. La création de branche de l'étape 1 est, elle, obligatoire dans le sprint validé.

## Coupe-circuit obligatoire — trois tentatives sur un même fichier

Cette règle prévaut sur toute consigne « corriger jusqu'à réussite » ou « poursuivre de façon autonome » des autres skills.

### Comptage

- Dès le premier échec de test/audit, enregistrer une identité stable : fichier source concerné, test ou règle, symptôme et commande de reproduction. Le premier échec détecté est le constat initial, pas une tentative de correction.
- Une tentative est un cycle **modification destinée à corriger cette erreur → réexécution du contrôle pertinent**. Si la même erreur persiste, incrémenter le compteur du couple fichier/erreur.
- Si le correctif touche plusieurs fichiers, attribuer la tentative à chacun des fichiers concernés par la même erreur. Un numéro de ligne changé, un renommage, un déplacement de code, un changement d'étape ou une reprise de conversation ne remet pas le compteur à zéro.
- Conserver les compteurs dans le suivi du sprint. Une réexécution sans modification n'est pas une nouvelle correction et ne justifie pas une boucle infinie ; diagnostiquer ou signaler l'environnement bloqué.
- Une erreur réellement corrigée reste enregistrée comme résolue. Si elle réapparaît pendant le même sprint, reprendre son historique de tentatives infructueuses, sans contourner la limite.

### Arrêt immédiat à la troisième tentative infructueuse

1. Arrêter immédiatement toute correction et toute progression dans le pipeline. **Ne pas effectuer de quatrième tentative**, ne pas traiter une autre tâche du sprint et ne pas lancer la clôture pour annoncer un succès.
2. Conserver la branche et les fichiers tels quels ; ne pas annuler les changements ou réinitialiser l'arbre de travail. Finaliser seulement la trace du blocage dans le journal existant si l'écriture reste disponible.
3. Afficher le log complet de la commande en échec, avec commande, code de sortie, sortie standard et sortie d'erreur, sans réduction aux dernières lignes. Si le journal dépasse la capacité de réponse, conserver le journal intégral dans `specs/sprints/logs/` et fournir son accès avec un extrait clairement annoncé comme tel ; ne pas prétendre que l'extrait est complet.
4. Masquer uniquement secrets et données personnelles réellement présents, avec marqueurs explicites ; conserver toute information de diagnostic non sensible. Ne jamais publier de clé d'API pour satisfaire l'exigence de log complet.
5. Afficher le fichier concerné, l'erreur persistante, les trois corrections tentées et leurs résultats, les critères bloqués et la question précise nécessitant l'intervention de l'utilisateur.
6. Marquer le sprint « bloqué — coupe-circuit 3/3 ». Attendre une instruction explicite de l'utilisateur avant toute reprise ; conserver l'historique et documenter le nouveau budget de tentatives autorisé, sans en présumer un.

Les erreurs d'environnement (permissions, dépôt absent, base de test inaccessible, dépendance impossible à installer) peuvent bloquer immédiatement sans trois modifications factices. Expliquer alors la cause exacte et l'action minimale nécessaire ; ne pas contourner les protections système.

## Reprise et rapport

À la reprise, vérifier branche, cadrage, diff, compteurs, dernière étape et preuves ; invalider les résultats affectés par des changements ultérieurs. Reprendre à la première étape réellement incomplète, sauf coupe-circuit non levé.

Le bilan final distingue chaque étape : réussie, échouée, non exécutée ou bloquée. Une demande de créer ce skill ne déclenche jamais l'exécution d'un sprint.
