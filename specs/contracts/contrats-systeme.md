# session-flow-bookings — Contrats système V1

Version 0.1.1 — 10 novembre 2026 — proposition technique, non implémentée.

## 1. Portée et sources de vérité

Ce dossier traduit le [PRD 0.4](../prd.md) en contrats testables. Les choix techniques ci-dessous sont proposés pour l'implémentation ; les paramètres commerciaux ouverts du PRD restent à valider.

| Fichier | Autorité |
|---|---|
| [openapi.json](openapi.json) | Routes, types, champs obligatoires, limites, projections et réponses HTTP |
| [modele-donnees.md](modele-donnees.md) | Relations persistées, contraintes, données dérivées et index |
| [invariants.md](invariants.md) | Invariants, transactions, transitions et scénarios concurrents |
| Ce document | Authentification, erreurs, idempotence, flux et limites de transport |

Une validation JSON ne prouve ni les permissions ni les invariants métier. Les quatre fichiers s'appliquent ensemble. En cas de conflit avec le PRD, suspendre la fonctionnalité concernée et corriger les documents avant le code.

L'API V1 est celle utilisée par les interfaces du plugin. L'API pour intégrateurs externes reste V4 ; pas de clé administrateur distribuée aux clients, pas de promesse de compatibilité tierce avant cette version.

Références techniques consultées : [OpenAPI 3.1.1](https://spec.openapis.org/oas/v3.1.1.html) et [authentification REST WordPress](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/). Le document fige volontairement OpenAPI 3.1.1, sans prétendre qu'il s'agit de la version la plus récente. La convention nonce/cookie WordPress est utilisée pour l'interface connectée ; le nonce ne remplace jamais les contrôles de capacité et de propriété.

## 2. Conventions normatives

- Base indicative `/wp-json/booking/v1` ; le frontend reçoit la base générée par WordPress, sans concaténation d'un domaine codé en dur.
- HTTPS en production ; UTF-8 ; JSON strict. Taille maximale d'une requête métier : 256 Kio. Webhook : 1 Mio. Dépassement : 413.
- Tous les DTO internes sont fermés (`additionalProperties: false`), y compris les objets imbriqués. Pas de coercition de `"1"` en entier ni de champs inconnus silencieusement ignorés.
- `null` est accepté uniquement lorsqu'il figure dans le schéma ; tableau vide et champ absent sont distincts. Les PUT remplacent toute la configuration autorisée, sans PATCH partiel implicite.
- Identifiants publics : UUID opaques ; aucune autorisation ne découle de leur imprévisibilité. Les identifiants WP sont des références internes non publiques.
- Dates API : UTC au format `YYYY-MM-DDTHH:mm:ssZ`. Fuseau IANA obligatoire pour l'intention locale d'une séance. Le serveur vérifie la validité du fuseau et la correspondance du créneau.
- Montants : entiers en unité mineure, bornés à 999 999 999 999. Pas de flottants ni de prix fourni par le navigateur lors d'un encaissement.
- Succès JSON : `{ "data": ..., "request_id": "UUID" }`. Erreur : `{ "error": { "code", "message", "request_id", "retryable", "fields": [{ "path", "code" }] } }`.
- `message` est destiné à l'humain ; le client branche sur `code`, jamais sur une traduction. `fields.path` est un JSON Pointer.
- GET sans mutation métier, notamment aucun remboursement, confirmation ou consommation de lien d'accès. Un téléchargement reste soumis à autorisation.
- Toutes les réponses privées et financières : `Cache-Control: no-store`. La première version peut aussi appliquer no-store aux disponibilités publiques pour éviter les incohérences.
- Listes : `limit` de 1 à 100, défaut 25 ; curseur opaque signé, lié au site, au principal, aux filtres et à l'ordre. Tri stable `(created_at, id)` croissant. Curseur invalide : 422. Pas de garantie de snapshot immuable pendant une pagination concurrente.
- Slots : ordre `(starts_at)` ; intervalle `from` inclus, `to` exclu, maximum 30 jours. La réponse est indicative ; la commande réalloue sous verrou.
- Les limites de 100 participants/commande, 100 séquences/session et 30 jours/recherche sont des bornes opérationnelles proposées, distinctes de la capacité totale d'une formation.

## 3. Authentification et permissions

### 3.1 Principaux

| Principal | Preuve | Portée |
|---|---|---|
| Anonyme | Aucune | Catalogue, devis, nouveau checkout et demande de lien uniquement |
| Compte WordPress | Cookie WordPress ET `X-WP-Nonce` | Capacités attribuées ET portée des objets |
| Acheteur invité | Cookie `booking_guest` ET `X-Booking-CSRF` | Commandes explicitement rattachées à l'identité vérifiée |
| Checkout non vérifié | Cookie `booking_checkout` ET `X-Booking-CSRF` | Commande créée dans ce checkout, lecture et paiement initial uniquement |
| Fournisseur de paiement | Signature sur corps brut | Ingestion d'événements ; aucun accès aux routes clients |
| Worker | Contexte interne non HTTP public | Commandes système explicitement autorisées |

Les alternatives OpenAPI ne sont pas cumulables pour élargir la portée : une requête choisit un seul principal. Une identité invalide ne retombe pas silencieusement sur un principal moins privilégié.

### 3.2 Accès invité et checkout

- `POST /orders` ne cherche jamais à fusionner un client connu sur le seul email. Il crée une identité acheteur de checkout isolée. Un utilisateur WP peut être lié seulement si cookie et nonce sont vérifiés ensemble ; sinon aucun rattachement.
- Le cookie checkout est créé lors de la première commande et peut référencer plusieurs checkouts du même navigateur, chacun avec sa propre échéance. Il ne permet jamais d'accéder à d'autres commandes partageant l'email ni d'annuler/déplacer une réservation.
- Durée checkout proposée : jusqu'à l'expiration de la réservation plus 60 minutes, maximum 24 heures. Après confirmation, lecture de cette commande seulement ; le paiement du solde exige un acheteur vérifié.
- `POST /access/requests` renvoie toujours 202 avec la même forme, que l'adresse soit connue ou non. Limitation des tentatives et envoi différé pour limiter l'énumération.
- Le lien contient un secret aléatoire d'au moins 256 bits, stocké sous forme de condensat. Validité proposée : 15 minutes, usage unique. Il ouvre une page de confirmation sans le consommer ; POST explicite `/access/exchanges` consomme le secret atomiquement et émet une session de 24 heures.
- Le serveur délivre cookies `HttpOnly; Secure; SameSite=Strict`, chemin restreint au site et secret CSRF associé ; aucune identité/secret dans un paramètre de pagination, journal ou redirection tierce. `Referrer-Policy: no-referrer` sur la page d'accès ; aucune ressource tierce sur cette page.
- Origin autorisée et CSRF contrôlés pour les routes utilisant des cookies ; CORS non ouvert à des origines arbitraires. Aucun nonce WordPress partagé entre visiteurs n'est utilisé comme identité invitée.
- Le rapprochement après vérification de l'email ne relie que les commandes dont cet email était celui de l'acheteur, jamais les fiches de participant partageant cet email. L'historique d'autres acheteurs reste inaccessible.
- Le destinataire d'un lien de paiement reçoit un accès limité à la commande et au montant, pas à un rôle de gestionnaire. En V1, le paiement du solde passe par le flux d'accès acheteur vérifié ; pas de lien de débit autonome en GET.

### 3.3 Capacités

`booking_manage_catalog`, `booking_manage_schedule`, `booking_manage_customers`, `booking_manage_orders`, `booking_read_finance`, `booking_record_payments`, `booking_refund`, `booking_manage_settings`, `booking_export` sont des capacités proposées, pas des noms de rôles implicites. `booking_manage` dans OpenAPI signifie administrateur ou gestionnaire autorisé à gérer les sessions ; ce n'est jamais un alias pour toute capacité financière.

Chaque route contrôle ensuite l'objet : propriété de commande, affectation à la séquence ou périmètre des données exportées. L'administrateur est autorisé par capacités attribuées, sans comparaison fragile du nom de rôle.

Un objet inexistant ou hors périmètre donne la même réponse 404. Une opération interdite sur un objet déjà visible donne 403. Les projections publiques, acheteur et formateur sont distinctes : aucune sérialisation automatique de l'entité persistée. Les secrets Stripe sont configurés hors des DTO de lecture : intégration via un écran dédié côté serveur, ne renvoyant que l'état de connexion. Son flux de provisioning devra être figé avant lot paiement ; aucun CRUD générique de secrets n'est autorisé.

## 4. Idempotence et concurrence HTTP

### 4.1 Idempotency-Key

Toutes les mutations métier client portent `Idempotency-Key: UUID`. L'ingestion fournisseur utilise son propre identifiant d'événement. Clé absente : 428.

- Portée unique : `(site_id, principal_scope, operation_id, key)` ; pour création anonyme de checkout, namespace anonyme du site et clé à entropie UUIDv4. Une clé n'est pas un moyen de consulter les données d'un autre principal.
- Empreinte : méthode, chemin canonique, DTO JSON canonique et `If-Match` ; exclure nonce et secrets de session. Les données conservées sont protégées et ne sont pas journalisées en clair.
- Même clé et même empreinte : retourner le résultat enregistré sans rejouer les effets ; `Idempotency-Replayed: true`. Le résultat peut être un snapshot ancien ; recharger l'agrégat pour son état actuel.
- Même clé et empreinte différente : 409 `IDEMPOTENCY_MISMATCH`.
- Même requête encore en cours : 409 `REQUEST_IN_PROGRESS`, `Retry-After: 2`. Le worker récupère une opération abandonnée avec un bail ; ne pas supprimer la clé pour « réessayer » un encaissement.
- Authentification, portée et révocation sont revérifiées avant restitution du résultat. L'idempotence ne restaure jamais un secret de lien déjà consommé et ne contourne pas une révocation.
- Exception `/access/exchanges` : une répétition n'émet une session que si la session créée est encore prouvée par son cookie ; sinon `ACCESS_LINK_USED` et demander un nouveau lien. Ne pas mettre de Set-Cookie secret dans un cache de réponses public.
- Création anonyme `/orders` : en cas de perte de réponse avant réception du cookie checkout, ne pas rejouer la réponse contenant les données personnelles au seul détenteur de la clé. Renvoyer 409 `CHECKOUT_RECOVERY_REQUIRED` et récupérer la commande par vérification de l'email. L'ordre n'est pas recréé.
- Résultats ordinaires conservés 24 h ; références des opérations financières conservées pendant leur durée de vie documentaire. Leur identifiant durable garantit la déduplication même après purge du cache HTTP.
- `retryable=true` autorise la répétition de la même intention avec la même clé, après le délai ; jamais une nouvelle opération financière aveugle. Une nouvelle intention nécessite nouvelle clé et nouveau contrôle métier.

### 4.2 Versionnement optimiste

Les modifications d'agrégats portent `If-Match: "version"`. Version retournée dans le DTO ; un GET d'agrégat expose aussi `ETag: "version"`. Absence : 428 ; obsolète : 412 `VERSION_MISMATCH`. Le serveur renvoie les versions aux seuls lecteurs autorisés.

Ordre : authentifier → valider schéma → retrouver une répétition idempotente → verrouiller → comparer version → appliquer transaction. Une répétition réussie ne doit pas échouer parce que son propre premier traitement a incrémenté la version.

`x-version-owner` indique l'agrégat : commandes imbriquées sous `/orders/{order_id}` utilisent la version de la commande, même pour paiement/refund ; présence utilise sa propre version ; planning utilise celle du planning. La création d'un planning vide et des présences initiales se fait avec version 1 avant leur première modification. Les créations de commandes n'ont pas d'If-Match : elles référencent un devis versionné et sont contrôlées transactionnellement.

Les versions optimistes ne remplacent pas les verrous sur la capacité et les ressources.

## 5. Codes d'erreur métier

| HTTP | Code | Sens / comportement client |
|---|---|---|
| 400 | INVALID_JSON | Corps non interprétable |
| 401 | AUTH_REQUIRED / ACCESS_EXPIRED | Authentifier à nouveau |
| 403 | FORBIDDEN / CSRF_INVALID | Aucun effet, pas de répétition automatique |
| 404 | NOT_FOUND | Objet absent ou hors portée |
| 409 | SLOT_UNAVAILABLE / CAPACITY_EXCEEDED | Recharger les disponibilités |
| 409 | REGISTRATION_CLOSED / SESSION_CANCELLED | Ne pas reprendre une inscription |
| 409 | QUOTE_EXPIRED / QUOTE_CHANGED | Nouveau devis et consentement requis |
| 409 | INVALID_TRANSITION / SCHEDULE_CONFLICT | État ou planning incompatible |
| 409 | PAYMENT_UNCERTAIN / REFUND_LIMIT_EXCEEDED | Rapprocher ou corriger ; pas de nouveau débit |
| 409 | IDEMPOTENCY_MISMATCH / REQUEST_IN_PROGRESS | Changement d'intention interdit / attendre |
| 409 | CHECKOUT_RECOVERY_REQUIRED / ACCESS_LINK_USED | Repasser par l'accès vérifié |
| 412 | VERSION_MISMATCH | Relire avant de présenter une nouvelle mutation |
| 413 | BODY_TOO_LARGE | Réduire la requête |
| 422 | VALIDATION_ERROR | Champs inconnus, format, bornes ou contrainte croisée invalides |
| 428 | PRECONDITION_REQUIRED | Clé d'idempotence ou If-Match manquant |
| 429 | RATE_LIMITED | Respecter Retry-After ; plafonds par site et principal |
| 503 | TEMPORARILY_UNAVAILABLE | Même clé ; résultat de paiement éventuellement à vérifier |
| 500 | INTERNAL_ERROR | Message générique et identifiant de diagnostic ; aucune erreur SQL |

Une opération acceptée en 202 n'est ni un paiement réussi ni une annulation entièrement compensée. Le client consulte la ressource/opération associée.

## 6. Flux contractuels

### 6.1 Devis → commande → paiement

1. Consulter catalogue/slots. Aucun blocage créé par GET.
2. `POST /quotes` reçoit une sélection discriminée et des références participants sans identité. Le serveur calcule les lignes, la taxe, l'acompte et les conditions ; expiration proposée 5 minutes. Un devis ne réserve rien.
3. `POST /orders` reçoit `quote_id`, la version des conditions acceptées, l'acheteur et les participants. L'ensemble des `client_ref` doit correspondre exactement au devis, sans doublon, ainsi que les modes. Le formulaire est validé selon sa version du devis.
4. Sous transaction, contrôler devis, prix actuel, état session, capacité et ressources. Une modification de prix/configuration invalide le devis : 409, pas de nouveau montant accepté silencieusement. Créer commande, inscriptions, allocations, audit et événements.
5. Carte : état held jusqu'à échéance ; manuel : pending_manual jusqu'à échéance ; gratuit : confirmed immédiatement, aucune ligne de paiement fictive. Individuel = exactement un participant et une séquence créée pour le créneau ; collectif = une session existante, 1 à 100 participants.
6. `POST /orders/{order_id}/payments` reçoit seulement `purpose`. Le serveur détermine le montant à partir du devis/solde et crée une opération durable. L'appel fournisseur se fait hors transaction ; 202 avec état processing ou verification_required. Un client_secret éventuel ne donne aucun droit sur l'API session-flow-bookings.
7. Le webhook vérifié ou rapprochement confirme l'encaissement puis alloue/convertit la capacité selon les invariants. Une page retour navigateur ne confirme rien.
8. Après commit, les notifications sont traitées par outbox. L'absence du navigateur ne bloque pas la confirmation.

Le checkout scoped peut payer seulement `initial` pendant sa validité ; `balance` exige le principal acheteur vérifié. Les méthodes Stripe proposées en V1 se limitent aux cartes prises en charge par le flux choisi ; les moyens à règlement différé ne sont pas ajoutés implicitement.

### 6.2 Annulation et remboursement

- Créer un cancellation-preview avec IDs d'inscriptions appartenant toutes à la commande ; serveur calcule diminution de dette et remboursements par ligne. Validité 5 minutes, liée à la version de commande et aux politiques.
- Confirmer avec preview_id, raison, If-Match et clé d'idempotence. Transaction : annuler les lignes ciblées, ajuster dette, libérer allocations et produire événements.
- Le besoin de remboursement devient une obligation persistée. Un opérateur avec `booking_refund` l'exécute ; l'acheteur ne peut pas choisir son montant ni le bénéficiaire.
- Le remboursement cible un paiement original, ses lignes et ses montants disponibles. Réserver le plafond avant l'appel réseau. Un résultat incertain garde la réserve jusqu'à rapprochement.
- Un remboursement manuel exige la preuve/référence d'une opération externe. Un ajustement commercial de dette est séparé de son remboursement ; un ajustement n'est pas un encaissement.
- Annulation de session : barrière atomique sur la session empêchant toute nouvelle inscription ; création d'un job et d'une obligation à traiter pour chaque commande. Les traitements par commande sont idempotents. Une commande non encore traitée ne rend pas la session réservable ; les soldes des inscriptions de cette session sont déjà non payables. L'état financier détaillé signale le traitement en cours.

### 6.3 Déplacement, mode et présence

- Déplacement individuel : une inscription active, prix inchangé, même offre et équipe ; verrouiller anciennes/nouvelles ressources puis substituer l'occupation atomiquement. Même session/inscription conservées, séquence versionnée, rappels précédents invalidés.
- Changement présentiel/distanciel : gestionnaire uniquement en V1 ; vérifie sous verrou les deux capacités, sans changement de prix.
- Présence : la personne est inscrite et confirmée, la séquence appartient à sa session et le formateur y est affecté. Historique conservé. Aucune modification d'une inscription annulée. Les présences ne sont pas des signatures d'émargement.

### 6.4 Jobs, exports et confidentialité

Les jobs sont persistés avec progression et état ; aucun appel navigateur n'est nécessaire pour continuer. L'export stocke une projection limitée à la portée autorisée au lancement, puis revérifie l'autorisation au téléchargement. Pas d'URL publique longue durée. CSV UTF-8, champs de formule neutralisés ; expiration proposée du fichier : 24 h. La limite de pagination ne tronque pas silencieusement un export.

## 7. Contraintes croisées obligatoires

Ces validations sont en plus des JSON Schemas :

- IDs liés au même site et références réellement existantes ; aucun `site_id`, `buyer_id`, statut ou montant payé accepté dans un DTO de commande publique.
- `start < end`, pas de séquences chevauchantes dans une même session ; timezone valide ; respect durée, pas, horizon et buffers de l'offre individuelle.
- Capacités : `0 <= onsite <= total`, limite salle sur chaque séquence ; en onsite, onsite=total ; en remote, onsite=0 ; le mode participant appartient à l'offre.
- Liste d'intervenants sans doublons ; au moins un par séquence publiée. Publication de l'offre individuelle exige équipe, durée et configuration de lieu cohérentes.
- Champs select : valeur dans options ; checkbox : booléen ; champs requis non vides ; IDs de champs sans doublon. Un champ texte accepte une réponse non vide seulement, les champs facultatifs non remplis sont omis.
- Acompte percent : valeur 1–100 en pourcentage entier ; fixed : unité mineure ; none : valeur 0. Si acompte >= total ou échéance du solde passée, paiement intégral uniquement.
- Planning : weekday 1=lundi ; horaires locaux start<end, pas de plage traversant minuit (la scinder). Une seule exception par date, qui remplace la journée entière ; intervalles sans chevauchement. Changement de fuseau contrôlé comme changement de planning.
- PUT training : modifier un prix n'altère pas les snapshots des commandes existantes. Changer kind après publication est interdit. PUT session : brouillon seulement ; les autres mutations sont des commandes explicites.
- Archivage d'un intervenant/salle/formation avec réservations futures actives : 409 jusqu'à résolution. Archiver une session exige completed ou cancelled, pas une simple dissimulation de ressources occupées.

## 8. Couverture et points encore à figer

Le contrat couvre le catalogue, disponibilités, réservations, paiements, annulations, présences et administration principale V1. Il n'est pas une implémentation, et les schémas ne garantissent pas à eux seuls la compatibilité runtime WordPress.

Avant lot concerné : arrêter le provisioning Stripe et sa version d'API, les valeurs de rétention/quota, la matrice PHP/WordPress/base SQL et les politiques fiscales locales. Les outils de confidentialité s'intègrent aux exporteurs/effaceurs WordPress et utilisent les contraintes décrites dans le modèle ; pas de route publique d'effacement ajoutée ici. Le CRUD de rôles s'appuie sur les capacités WordPress contrôlées côté serveur.

Toute route ajoutée doit déclarer DTO fermé, principal, capacité, portée objet, agrégat de version, transaction et invariant couvert. Tout changement incompatible de champ/état/unité exige une nouvelle version de contrat et des tests de consommateurs.
