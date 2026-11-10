---
name: architecture-guard
description: Vérifier toute conception, modification de code ou revue de session-flow-bookings contre le PRD, les modèles de données, OpenAPI et invariants validés. Appliquer avant implémentation et avant validation de la tâche.
---

# Garde d'architecture

## Sources de vérité

Lire AGENTS.md et les spécifications effectives : `specs/prd.md`, `specs/fonctionnalites.md`, `specs/plan-developpement.md`, `specs/contracts/README.md`, `contrats-systeme.md`, `modele-donnees.md`, `invariants.md` et `openapi.json` sous `specs/contracts/`.

Si elles ne sont pas encore présentes dans session-flow-bookings, utiliser en lecture seule les fichiers existants du projet de préparation `../ameliabooking/specs/` en indiquant ce chemin et leurs versions. Ne jamais confondre le code Amelia avec celui du produit. Ne pas copier ou déplacer les sources de vérité sans demande explicite ; si deux copies divergent, demander uniquement l'arbitrage nécessaire, sans en choisir une silencieusement.

Les spécifications locales (PRD 0.4, contrats 0.1.1) sont issues de la reprise explicitement demandée le 10 novembre 2026. Leur provenance est dans `specs/contract-sources.json` ; la source de préparation ancienne reste en lecture seule. Cette divergence historique documentée ne nécessite pas un nouvel arbitrage. Un conflit fonctionnel nouveau reste à arbitrer.

## Procédure obligatoire

1. Associer la tâche aux IDs d'exigences PRD, opérations OpenAPI, invariants INV et tests CT/REC concernés. Définir le périmètre du sprint ; ne pas ajouter une fonction V2–V5 à V1 implicitement.
2. Identifier les objets touchés, leur propriétaire, leurs transitions et les conséquences sur disponibilité, finances et permissions. Distinguer exigences validées et paramètres commerciaux encore ouverts.
3. Respecter les frontières : contrôleurs/adaptateurs WordPress pour transport et accès ; services applicatifs pour orchestration ; domaine pour règles ; repositories pour persistance ; adaptateurs pour fournisseurs. Ne pas déplacer une règle métier exclusivement dans le JavaScript ou un template.
4. Vérifier les DTO fermés, champs obligatoires, types, limites, projections par rôle, codes d'erreur, If-Match, clé d'idempotence et permissions par objet. Ne jamais mass-assigner le corps reçu à une entité.
5. Vérifier modèle persistant, relations, index, isolation par site, unicité, snapshots historiques et migrations. Toute évolution de stockage déclenche db-migration.
6. Respecter l'ordre de verrouillage commun. Audit/outbox et mutation métier doivent être cohérents dans la transaction ; les appels externes se font hors transaction.
7. Confirmer qu'aucun cache, retour navigateur ou donnée envoyée par le client n'est la source de vérité d'une réservation ou d'un paiement.
8. Après implémentation, relire le diff et ses appels, exécuter la validation contractuelle et les tests liés via test-runner. Fournir une matrice exigences → fichiers → tests → résultat.

## Invariants à ne jamais affaiblir

- Pas de double réservation de ressource, dépassement de capacité ou attribution partielle d'un groupe.
- Une session collective publiée bloque ses ressources même sans inscrit ; une attente expirée ne consomme plus de place, même sans cron.
- Une personne, un acheteur et un compte WordPress sont des notions distinctes. L'email seul n'autorise aucun accès à un historique tiers.
- Les calculs financiers et remboursements se font par ligne d'inscription puis sont agrégés. Ne pas compenser implicitement la dette des participants restants avec le remboursement d'un autre.
- Paiement vérifié côté serveur, opérations idempotentes, états incertains rapprochés, plafonds de remboursement protégés contre la concurrence.
- Une annulation explicite n'est pas inversée par un paiement tardif. Un déplacement échoué conserve l'ancien créneau.
- Aucun état ou secret privé ne fuit dans une projection publique ou formateur.

## Validation et blocages

Lancer le validateur OpenAPI de référence configuré par le projet. Le script `specs/contracts/validate_contracts.py`, s'il existe, complète ce contrôle mais ne remplace ni le validateur complet ni les tests runtime.

Ne pas modifier une spécification ou un test seulement pour faire accepter un comportement non conforme. Si un changement de contrat est réellement nécessaire, exposer le conflit et son impact, puis obtenir la décision fonctionnelle manquante avant l'implémentation dépendante. Continuer les parties indépendantes déjà définies.

Bilan obligatoire : sources et versions lues, exigences couvertes, écarts, migrations/API affectées, tests exécutés, points ouverts. Une divergence bloquante interdit de déclarer la tâche conforme.
