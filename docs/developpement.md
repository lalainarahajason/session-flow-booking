# Développement

Sources : PRD 0.4, contrats 0.1.1 / OpenAPI 3.1.1. Le cadrage validé reste dans specs/sprints.
PHP local observé : 8.5.4 (contrôle supplémentaire uniquement). La recette PHP cible utilise Docker 8.3.
Composer et npm sont verrouillés. Aucun correcteur ne doit toucher vendor/node_modules.

Commandes : `composer test`, `composer lint`, `npm run validate:contracts`, `npm run test:tooling`.
`bin/install-test-environment.sh` prépare wp-env et MySQL 8.4 avec base sfbk_test dédiée.
Les constantes du bootstrap refusent toute autre cible. Les DELETE de fixtures sont limités aux tables techniques de test.
Le démonstrateur tests/Fixtures expose uniquement sfbk-test/v1 ; il ne fait pas partie du paquet.

Pour les tests navigateur : activer le produit sur tests-cli avec --user=1, créer le compte fictif
sfbk_browser_trainer (rôle sfbk_trainer), puis lancer `npm run test:e2e`.
Les identifiants dans les fixtures sont exclusivement locaux et ne doivent jamais servir en production.

Le ZIP est créé par une allowlist dans bin/build-plugin.sh. Composer runtime sans dépendances de dev.
Contrôler sa copie décompressée avec Plugin Check statique/runtime selon wordpress-org.md.
