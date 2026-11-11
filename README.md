# Session Flow Bookings

Plugin WordPress : socle Sprint 1, version de développement 0.1.0.
Dépôt : https://github.com/lalainarahajason/session-flow-booking.git
Le dossier de travail reste `session-flow-booking`, le slug installé est `session-flow-bookings`.

## Développement

Lire AGENTS.md et docs/developpement.md. Installer Composer et npm depuis les lockfiles.
`composer test`, `composer lint`, `npm run validate:contracts`, `npm run test:tooling`.
Les tests d'intégration emploient exclusivement MySQL 8.4 / sfbk_test ; voir bin/install-test-environment.sh.
`npm run test:e2e` teste l'instance wp-env de recette, `npm run build:plugin` prépare le paquet.
Aucun build JS/CSS : assets/admin.css est la source lisible distribuée.

## Livraison

Licence GPL-2.0-or-later. Les contrôles et limites sont suivis dans specs/sprints/sprint-1-execution.md.
Le socle ne livre pas les parcours métier de réservation/paiement.
