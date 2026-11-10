# session-flow-bookings — Invariants, transactions et recette contractuelle

Version 0.1.1 — proposition normative pour V1, non exécutée contre un plugin.

## 1. Registre des invariants

| ID | Invariant | Point de contrôle | Référence PRD |
|---|---|---|---|
| INV-01 | Chaque relation appartient au même site ; aucune entrée client ne choisit le site | FK composites + contexte serveur | WP-02, NFR-01 |
| INV-02 | Une commande n'est lisible/modifiable que par son acheteur prouvé ou une capacité autorisée | Permission par objet avant lecture et replay | CLI-01, REC-10/25 |
| INV-03 | Pour une ressource, deux occupations actives de séquences distinctes ne se chevauchent pas | Resource guards + vérification transactionnelle | DIS-02, REC-02/17 |
| INV-04 | Occupation totale <= capacité totale et occupation onsite <= capacité onsite | Verrou session + somme allocations actives | COL-01, REC-01/22 |
| INV-05 | Une inscription possède au plus une allocation ; un groupe est alloué entièrement ou pas du tout | UNIQUE + transaction groupe | INS-01, REC-03/18 |
| INV-06 | Une expiration passée ne consomme pas de capacité même sans worker | Horloge DB dans chaque lecture d'allocation | REC-18, NFR-16 |
| INV-07 | Confirmation implique capacité acquise et mode de confirmation autorisé : gratuit, paiement vérifié ou décision manuelle | Commande de confirmation | PAY-01/02, REC-06/24 |
| INV-08 | Une annulation explicite ne peut être inversée par paiement tardif | État vérifié sous verrou et exception financière | REC-21 |
| INV-09 | Un succès financier vérifié n'est pas perdu par un événement plus ancien ; une incertitude interdit un nouveau débit aveugle | Inbox + état opération + rapprochement | REC-04/06/27 |
| INV-10 | Somme allocations paiement = paiement ; devise et commande identiques | Transaction financière | PAY-03, REC-07 |
| INV-11 | Remboursements réussis + réservés <= encaissement disponible, par paiement et ligne | Verrou paiement/commande, réserve persistée | REF-01, REC-23 |
| INV-12 | Une annulation ajuste le contrat avant remboursement ; le solde s'agrège par lignes | Écritures immuables et calculs §4 du modèle | REC-19/20 |
| INV-13 | Un déplacement échoué ne modifie ni l'ancienne réservation ni ses ressources | Transaction de substitution | CAN-02, REC-16 |
| INV-14 | Une présence concerne une inscription confirmée et une séquence de la même session | FK logique + portée formateur | PRE-01, REC-15 |
| INV-15 | Un événement métier confirmé et son outbox sont durables ensemble | Même transaction DB | NOT-01, NFR-14 |
| INV-16 | Un rappel invalide/obsolète ne part pas après modification ou annulation connue avant sa prise en charge | Vérification état/révision au claim worker | REC-26 |
| INV-17 | Les snapshots de prix et conditions ne changent pas rétroactivement | Append-only + config version | TAR-01, REC-14 |
| INV-18 | Même intention/idempotency-key = au plus un effet métier | Enregistrement unique et opération durable | REC-04/27 |
| INV-19 | Une mutation sur version obsolète n'écrase pas l'état récent | If-Match vérifié sous verrou | Toutes mutations |
| INV-20 | Un DTO public/formateur ne contient pas de champ privé non autorisé | Projection explicite + tests négatifs | REC-10/25/30 |
| INV-21 | Session annulée : aucune nouvelle inscription ni paiement de ses soldes, même pendant le job de compensation | Barrière session synchronisée | CAN-01, REC-29 |
| INV-22 | L'archivage/congé ne fait pas disparaître une occupation future encore engagée | Vérification ressources et état | REC-28 |

## 2. Stratégie transactionnelle proposée

### Verrous communs

Choix de départ : verrous de lignes en base transactionnelle, pas un verrou PHP en mémoire, ni un simple cache distribué. Les ressources ont une ligne `resource_guard` stable même si aucune réservation n'existe encore ; elle prévient la course « deux recherches trouvent zéro conflit ».

Ordre global obligatoire pour toutes les commandes :

1. Réserver la clé d'idempotence de la requête/opération.
2. Verrouiller les lignes de garde de toutes les ressources concernées, triées par `(type,id)`.
3. Verrouiller les sessions concernées, triées par id.
4. Verrouiller les commandes, triées par id.
5. Verrouiller opérations financières/lignes, triées par id.

La lecture préliminaire des IDs ne fait pas autorité : après verrouillage, revérifier versions et ensembles de ressources. Si le périmètre a changé, rollback et recommencer selon le même ordre ; ne jamais acquérir après coup une ressource antérieure dans l'ordre. Une commande qui ne touche qu'un suffixe (ex. remboursement) n'acquiert pas ensuite un verrou de session ou ressource.

Le traitement d'un succès de paiement nécessitant réallocation démarre avec les ressources/session avant le verrou commande ; il ne commence pas par verrouiller le paiement. Les changements de planning verrouillent les mêmes resource guards, puis les sessions impliquées. Un deadlock/timeout produit rollback ; au plus trois reprises bornées de la même opération, puis erreur récupérable.

Les appels Stripe et l'envoi email sont toujours hors transaction. Une transaction ne couvre jamais la base et Stripe ensemble : elle persiste une intention durable, puis une seconde transaction applique le résultat vérifié. Une interruption au milieu laisse une opération récupérable.

### Réservation collective

```text
begin
  lock session (les ressources sont déjà occupées par la session publiée)
  vérifier état, clôture, devis et version de configuration
  compter allocations held/pending non expirées + confirmed
  vérifier total et quota onsite pour tout le groupe
  consommer devis (unique), créer commande, lignes, inscriptions, allocations
  si gratuit : confirmer ; sinon fixer attente + échéance
  écrire audit + outbox + résultat idempotent
commit
```

La clôture et l'annulation de session utilisent le même verrou. L'inscription n'ajoute pas une occupation de formateur par personne.

### Réservation individuelle

Verrouiller l'équipe et la salle avant la recherche de chevauchement, revérifier horaires/congés et buffers, créer session + séquence + occupations temporaires + commande et inscription dans une transaction. Les occupations expirées sont ignorées selon l'horloge DB. Seule la réussite du commit crée la place temporaire.

### Publication et déplacement

Publication : vérifier tous les intervenants/salles et toutes les séquences ; si une seule échoue, aucun blocage partiel. Déplacement : verrouiller union des anciennes et nouvelles ressources, exclure uniquement l'occupation de la séquence déplacée de la recherche, vérifier le nouveau créneau puis remplacer. Ni libération anticipée ni changement de prix.

### Paiement et résultat tardif

1. Sous verrou commande, réserver l'opération et le montant courant, bloquer une seconde tentative concurrente sur la même dette, puis commit.
2. Appeler le prestataire avec une clé dérivée de l'operation_id durable. Si résultat inconnu, conserver `verification_required` et sa réserve.
3. Vérifier le montant, la devise, le compte fournisseur, l'environnement live/test et le lien avec notre opération. Les métadonnées externes seules ne suffisent pas à choisir une commande.
4. Reprendre la transaction dans l'ordre global. Si held/pending valide : convertir l'allocation. Si expired : réallouer seulement si état session/clôture/capacité l'autorisent. Si cancelled/rejected : ne pas réactiver ; constater les fonds et créer exception/remboursement dû.
5. Dédupliquer l'événement et l'opération métier séparément : deux événements différents peuvent décrire un même paiement. L'identifiant d'objet fournisseur et operation_id empêchent de compter les fonds deux fois.
6. Écrire paiement, allocations, états, audit et outbox atomiquement. Une confirmation annulée par l'organisateur pendant l'appel réseau doit être compensée financièrement, jamais confirmée à nouveau.

### Remboursement

Réserver par paiement ET ligne les montants demandés, incluant autres demandes en cours et incertaines. Un échec vérifié libère la réserve, un résultat inconnu la conserve. Succès : convertir réserve en remboursement réussi dans une transaction ; ne pas réduire deux fois la dette contractuelle (déjà ajustée lors de l'annulation ou du geste commercial).

### Annulation collective par lots

La transaction initiale pose `session.state=cancelled`, libère les occupations futures et crée le job/outbox. Les inscriptions restantes sont effectivement non utilisables tant que la compensation n'est pas matérialisée. Tous les endpoints et workers utilisent aussi l'état session, pas seulement enrollment.status.

Chaque sous-commande par commande annule les inscriptions ciblées et ajuste leurs lignes une seule fois via une clé déterministe `(session_cancel_operation,order_id)`. Les paiements en cours constatés ultérieurement deviennent des exceptions à rembourser. Le job est terminé quand chaque commande est compensée localement et chaque remboursement dû enregistré ; les remboursements externes peuvent rester en cours, clairement affichés séparément.

## 3. Transitions autorisées

| Agrégat | Source | Commande / événement | Cible et garde |
|---|---|---|---|
| Session collective | draft | publish | published, toutes ressources disponibles |
| Session collective | published | close | closed, occupations conservées |
| Session | published/closed | complete | completed seulement après fin de dernière séquence |
| Session | draft/published/closed | cancel | cancelled, barrière + compensations |
| Session | completed/cancelled | archive | archived, historique conservé |
| Inscription | held | payment verified / free confirmation | confirmed si allocation valide |
| Inscription | held/pending_manual | deadline reached | expired, capacité libérée logiquement |
| Inscription | pending_manual | manual confirmation | confirmed avec acteur habilité, dette conservée si non payée |
| Inscription | pending_manual | reject | rejected, allocation libérée |
| Inscription | held/pending_manual/confirmed | explicit cancellation | cancelled avec politique et ajustements |
| Inscription | expired | late verified payment | confirmed seulement si réallocation autorisée ; sinon exception |
| Inscription | cancelled/rejected | late payment | reste annulée/refusée, exception financière |
| Paiement | created | dispatch | processing |
| Paiement | processing | timeout | verification_required, aucun nouveau débit |
| Paiement | created/processing/verification_required | verified success | succeeded une fois |
| Paiement | processing/verification_required | verified terminal failure | failed ; nouvelle intention possible |
| Remboursement | requested | dispatch | processing, montant réservé |
| Remboursement | processing | timeout | verification_required, réserve conservée |
| Remboursement | processing/verification_required | verified result | succeeded ou failed ; conversion/libération réserve |

Un événement incompatible avec l'état attendu n'est pas silencieusement écarté s'il indique un mouvement de fonds : il déclenche un rapprochement. Les états terminalement échoués reçoivent un nouvel identifiant pour une vraie nouvelle tentative ; le paiement d'origine reste traçable.

L'état Order est une projection persistée sous verrou : payment_exception si anomalie financière ouverte empêchant confirmation ; cancelled si toutes inscriptions annulées ; partially_cancelled si mélange confirmé/annulé ; confirmed si toutes actives confirmées ; pending_manual/held/expired/rejected selon le flux homogène. Aucun PATCH public d'état n'existe. L'état ne remplace pas les sommes ni la présence de remboursements en cours.

## 4. Événements internes et workers

Envelope fermé : `event_id UUID`, `event_type enum`, `schema_version=1`, `site_id`, `aggregate_id`, `aggregate_version`, `occurred_at UTC`, `causation_id`, `correlation_id`, `payload` validé par type. Noms initiaux : `order.created`, `enrollment.confirmed`, `enrollment.cancelled`, `sequence.rescheduled`, `payment.succeeded`, `refund.requested`, `refund.succeeded`, `session.cancelled`.

Payloads minimaux : références internes et revision ; données personnelles résolues par le worker dans la portée nécessaire, pas copiées dans tous les événements. Consommation au moins une fois, effets consommateurs idempotents. Un événement par agrégat/version/type/discriminant ; pas de promesse « exactly once » entre systèmes externes.

Un worker réclame un job par bail à expiration, traite puis confirme. Après crash, un autre reprend. Réessais avec délai exponentiel borné (proposition : 1 min, 5 min, 30 min, 2 h, 12 h), puis échec visible et reprise manuelle. Un échec d'email n'inverse jamais un paiement ou une réservation.

Pour les emails, revérifier inscription, session et revision juste avant soumission. Si une annulation intervient après la soumission déjà acceptée par le transport, l'email ne peut pas être retiré ; une notification corrective est envoyée. INV-16 ne promet pas l'annulation d'un message déjà envoyé.

## 5. Suite de tests contractuels à implémenter

| Test | Entrée / interleaving | Assertion |
|---|---|---|
| CT-01 | Deux transactions demandent la dernière place | Une réussite, une erreur capacité ; compteur final 1 |
| CT-02 | Deux cours individuels sur une ressource sans occupation préalable | Garde ressource sérialise, une seule occupation |
| CT-03 | Groupe N dépassant capacité ou quota onsite | Aucun morceau du groupe persisté/alloué |
| CT-04 | Publication de deux sessions sur une salle commune | Une seule publication ; rollback de toutes les séquences perdantes |
| CT-05 | Expiration sans cron, puis nouvelle inscription | Ancienne allocation ignorée à l'heure DB, pas de surbooking |
| CT-06 | Même clé, même corps ; même clé, autre corps | Replay sans effet ; 409 mismatch respectivement |
| CT-07 | Succès paiement, crash avant notification | Paiement durable ; worker reprend un seul événement logique |
| CT-08 | Même paiement annoncé par deux event_ids | Un seul encaissement comptabilisé |
| CT-09 | Annulation concurrente au succès fournisseur | Pas de réactivation ; trop-perçu tracé et compensable |
| CT-10 | Expiration puis réallocation de la dernière place à autrui | Paiement tardif initial : exception, pas de confirmation |
| CT-11 | Deux remboursements concurrents dont la somme dépasse le paiement | Une demande refusée avant appel externe |
| CT-12 | Cinq places/100 €, acompte 20 € chacune ; annuler une place | Dette 320 € et remboursement dû 20 € simultanés, puis dette inchangée après restitution |
| CT-13 | Timeout paiement puis nouvelle tentative UI | État à vérifier, pas de seconde opération de débit |
| CT-14 | Acheteur A lit commande B ; formateur non affecté modifie présence | 404 sans donnée ni effet |
| CT-15 | DTO avec buyer_id/status/amount_minor inattendu | 422, aucun mass assignment |
| CT-16 | If-Match obsolète / manquant | 412 / 428, zéro mutation |
| CT-17 | Déplacement raté après test de nouvelle disponibilité | Ancienne occupation et ancien prix inchangés |
| CT-18 | Session annulée, job moitié terminé, relance | Plus aucun paiement de solde ; aucune double compensation |
| CT-19 | Rappel en queue puis déplacement | Ancienne révision supprimée, nouvelle date utilisée |
| CT-20 | Webhook mauvais compte/mode/devise/signature | Aucun effet métier, rejet ou exception contrôlée |
| CT-21 | Cookie checkout présenté pour paiement balance ou autre commande | Refus, même si l'email est identique |
| CT-22 | Clé d'un checkout anonyme réutilisée sans cookie | Aucune donnée personnelle restituée ; flux de récupération |
| CT-23 | Présentiel plein/distanciel libre | Remote accepté, onsite rejeté ; changement mode vérifié |
| CT-24 | Présence d'une autre session ou inscription annulée | Refus, pas de ligne créée |
| CT-25 | Offres gratuites et doublons de requête | Une inscription, zéro paiement fictif |
| CT-26 | Heure locale inexistante/ambiguë et changement de fuseau planning | Validation explicite et aucune mutation silencieuse |
| CT-27 | Taxe/prix changés entre devis et commande | 409 quote changed ; nouveau consentement nécessaire |
| CT-28 | Perte de droits après génération d'export | Téléchargement interdit et aucun fichier public |

Les tests de schéma et de références peuvent être exécutés avant le plugin ; CT-01 à CT-28 nécessitent une implémentation, une base transactionnelle et, pour les paiements, un simulateur de prestataire contrôlable. Aucun de ces tests d'intégration n'est prétendu exécuté à ce stade.
