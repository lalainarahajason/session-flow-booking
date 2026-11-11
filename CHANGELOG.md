# Changelog

## Unreleased

### Added
- Bootstrap WordPress, administration protégée et CSS limité aux écrans du plugin.
- Migrations versionnées de cinq tables techniques et conservation par défaut à la désinstallation.
- Primitives transactionnelles, idempotence persistée, audit, outbox et baux de jobs.
- Tests unitaires et intégration MySQL avec démonstrateur exclu du paquet.
- Enveloppes REST uniformes, retries transactionnels bornés sur deadlock et contrôle CSRF des réglages.
- Recette technique sur WordPress 6.8.3 et 7.1.4 ; PHPUnit sur PHP 8.3 et 8.4.

Aucune route métier de réservation ou paiement n'est livrée à ce stade.
