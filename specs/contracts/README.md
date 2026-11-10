# session-flow-bookings — Contrats système

Version 0.1.1 · 10 novembre 2026 · proposition technique pour le [PRD 0.4](../prd.md).

## Livrables

| Document | Contenu |
|---|---|
| [Contrats système](contrats-systeme.md) | Conventions API, sécurité, autorisations, idempotence, versions, erreurs et parcours |
| [OpenAPI JSON](openapi.json) | 91 opérations, 98 schémas, DTO fermés et projections distinctes |
| [Modèle de données](modele-donnees.md) | Tables logiques, relations, contraintes, index, snapshots et calculs financiers |
| [Invariants](invariants.md) | 22 invariants, stratégie de verrouillage, transitions et 28 scénarios contractuels |
| [Exemples](examples.json) | 11 exemples de requêtes/événements valides ou volontairement invalides |
| [Vérification locale](validate_contracts.py) | Références, structure, objets fermés et tests des exemples sur le sous-ensemble de schémas utilisé |

## Lire et implémenter

1. Lire le contrat transversal, puis les modèles et invariants.
2. Utiliser OpenAPI pour les DTO et clients ; les contraintes croisées du domaine restent obligatoires.
3. Implémenter les transactions de capacité avant le paiement.
4. Implémenter inbox/outbox et opérations durables avant les traitements externes.
5. Exécuter les tests d'intégration de concurrence, autorisation et reprise avant de considérer le contrat satisfait.

L'OpenAPI décrit l'API interne des interfaces V1, et non l'offre d'API pour tiers prévue en V4. Les secrets d'intégration et opérations de confidentialité WordPress ne sont pas exposés comme CRUD public. Leur intégration finale est explicitement identifiée dans le contrat.

## Vérification

Depuis la racine du plugin :

```sh
python3 specs/contracts/validate_contracts.py
```

Vérifié lors de la création : références locales résolues, operationId uniques, paramètres de chemin présents, authentification déclarée, clés d'idempotence des mutations, DTO internes fermés et 11 exemples conformes au résultat attendu.

Limites : cette vérification sans dépendance n'est pas un validateur complet OpenAPI/JSON Schema. Elle ne vérifie pas tous les formats (notamment email/URI), le runtime WordPress, les contraintes métier croisées ou les transactions. Un validateur de référence doit être ajouté à la CI avant génération des clients. Les 28 scénarios CT sont spécifiés, pas exécutés contre une implémentation.

## Couverture principale

| Besoin PRD | Surface contractuelle | Invariants centraux |
|---|---|---|
| CAT, TAR | /catalog, /admin/trainings, /quotes | INV-17, INV-20 |
| DIS, IND, COL, RES | /slots, /admin/sessions, /schedule, /orders | INV-03 à INV-07, INV-22 |
| INS, CLI, WP | /orders, /me/orders, /access, /admin/customers | INV-01, INV-02, INV-05, INV-20 |
| PAY, REF | /payments, /manual-payments, /refunds, /reconcile, /integrations/stripe/events | INV-08 à INV-12, INV-18 |
| CAN | /cancellation-previews, /cancellations, /move, /sessions/{id}/cancel | INV-08, INV-13, INV-21 |
| PRE | /staff/sequences/{sequence_id}/roster et /presences | INV-14, INV-19 |
| NOT | outbox, /admin/notifications et /retry | INV-15, INV-16 |
| EXP, TAB | /exports, /jobs, /admin/dashboard | INV-02, INV-20 |

Les mentions raccourcies de routes dans cette table sont des familles ; les chemins exacts sont dans OpenAPI.

## Clarification métier apportée

Le PRD 0.4 conserve le calcul par ligne introduit en 0.3. Pour cinq places à 100 € et un acompte de 20 € chacune, annuler une place sans frais produit simultanément 320 € dus sur les quatre places actives et 20 € à rembourser sur la cinquième. Un simple solde global de 300 € aurait masqué cette obligation. Le modèle, les invariants INV-12 et le test CT-12 reprennent cette règle.

## Décisions restant à confirmer

- Base technique neuve ou réutilisation contrôlée du code existant ; versions SQL/PHP/WordPress.
- Pays, devise, fiscalité, conditions d'annulation et durée de conservation.
- Provisioning Stripe, version d'API fournisseur et paramètres opérationnels exacts.
- Durées proposées des devis, cookies et accès invités ; limites par commande et quotas anti-abus.

Ces points ne bloquent pas la lecture du contrat ; ils doivent être tranchés avant le lot qui en dépend. Aucun plugin, compte Stripe, schéma de base ou endpoint n'a été créé par ces documents.

## Révision 0.1.1

Alignement du nom session-flow-bookings et des références PRD 0.4. Aucun changement des routes, DTO, opérations ou invariants métier ; OpenAPI reste en 3.1.1. Les références au PRD 0.3 dans l’historique financier restent historiques. La source locale est désormais la référence ; provenance dans `../contract-sources.json`.
