# session-flow-bookings — Product Requirements Document

| Métadonnée | Valeur |
|---|---|
| Version du document | 0.4 — identité produit et validation WordPress.org |
| Date | 10 novembre 2026 |
| Statut | Proposition à valider avant implémentation |
| Produit | session-flow-bookings — nom confirmé |
| Support | Plugin WordPress |
| Cible | Formateurs indépendants et organismes de formation |
| Référence fonctionnelle | [Fonctionnalités V1–V5](fonctionnalites.md) |

## 1. Vision et problème à résoudre

Permettre aux formateurs de vendre et d'organiser des cours individuels et des formations collectives depuis leur site WordPress, sans maintenir des calendriers, listes d'inscrits et suivis de paiement séparés.

Le produit doit traiter correctement les situations réelles : une entreprise achète plusieurs places pour des participants différents, une formation se déroule sur plusieurs jours et plusieurs intervenants partagent leurs disponibilités entre cours individuels et sessions collectives.

La proposition de valeur repose sur trois axes :

1. Une disponibilité cohérente des intervenants et des salles.
2. Des inscriptions et paiements fiables, traçables et réparables.
3. Une administration compréhensible sans connaissance technique.

### Origine des besoins

- Choix exprimés dans le projet : secteur de la formation, cours individuels et collectifs.
- Corpus de 797 avis Amelia : signaux sur les intégrations, les parcours administratifs et les limites métier. Ces avis ne démontrent pas la présence actuelle de chaque problème.
- Revue statique de la copie locale Amelia Lite 2.4.14 : référence technique partielle, sans validation dynamique ni audit complet de la version payante.
- [Grille commerciale Amelia](https://wpamelia.com/pricing/) : référence concurrentielle évolutive, pas spécification de session-flow-bookings.

## 2. Décisions et hypothèses

| Sujet | État | Conséquence |
|---|---|---|
| Cible : formateurs | Confirmé par le porteur du projet | Priorité aux inscriptions et sessions pédagogiques |
| Individuel et collectif | Confirmé par le porteur du projet | Moteur de disponibilité partagé |
| Plugin WordPress | Hypothèse retenue dans la feuille de route | Pas de SaaS autonome en V1 |
| V1 à V5 | Proposition de livraison | Ne définit pas les offres commerciales |
| Stripe en premier | Proposition | Un seul prestataire de carte bancaire en V1 |
| Une organisation et une devise par site | Hypothèse V1 | Plusieurs lieux possibles, sans gestion multi-organismes |
| Session collective indivisible | Hypothèse V1 | Une inscription couvre toutes les dates de la session |
| Nouveau code ou dérivé du code existant | À décider | Aucun choix de réutilisation n'est présumé validé |
| Marché géographique et tarification | À décider | Conditionnent facturation, textes et commercialisation |

Les seuils chiffrés de ce document sont des objectifs proposés, non des performances déjà mesurées. Les règles ajoutées lors de la revue sont des spécifications proposées, pas des décisions commerciales déjà approuvées.

### Gouvernance du périmètre

- `fonctionnalites.md` décrit les capacités par version ; ce PRD précise leurs règles et critères de recette. Une divergence doit être arbitrée et reportée dans les deux documents avant développement.
- Aucun élément V1 n'est déplacé vers une autre version par cette revue. Les restrictions explicites ci-dessous bornent les cas pris en charge.
- Chaque demande supplémentaire indique le besoin, les exigences affectées, l'effort estimé et la version cible avant acceptation.
- Une règle encore ouverte doit être tranchée avant le lot qui en dépend ; elle ne devient pas une décision par simple implémentation.

## 3. Utilisateurs et responsabilités

| Profil | Besoin principal | Accès V1 |
|---|---|---|
| Administrateur | Configurer et superviser le produit | Réglages, utilisateurs, données, paiements et journal |
| Gestionnaire | Organiser les sessions et traiter les inscriptions | Catalogue, planning, clients, inscriptions et opérations financières autorisées ; pas de secrets d'intégration |
| Formateur | Consulter ses interventions et suivre les participants | Sessions affectées, informations nécessaires et présences ; aucun accès financier global |
| Acheteur particulier | Réserver pour lui-même | Ses commandes, réservations et paiements |
| Acheteur professionnel | Inscrire plusieurs personnes | Ses commandes et participants associés ; espace entreprise complet reporté en V3 |
| Participant distinct de l'acheteur | Connaître ses dates et modalités | Informations de sa participation ; aucun accès aux paiements ou aux autres inscrits |

Le compte WordPress et la fiche participant sont deux notions distinctes. Un participant n'a pas besoin d'un compte pour être inscrit. Les capacités explicitement attribuées à un compte déterminent ses accès ; un rôle de formateur ne lui donne jamais les capacités d'un gestionnaire. Les relations acheteur/participant n'accordent aucun rôle administratif.

### Matrice d'accès V1

| Action | Administrateur | Gestionnaire | Formateur | Acheteur | Participant distinct |
|---|---|---|---|---|---|
| Régler paiements, secrets et rôles | Oui | Non | Non | Non | Non |
| Gérer catalogue et planning | Oui | Oui | Lecture des affectations | Non | Non |
| Consulter liste des participants / présences | Oui | Oui | Séquences affectées uniquement | Ses participants uniquement | Sa participation uniquement |
| Modifier les présences | Oui | Oui | Séquences affectées uniquement | Non | Non |
| Annuler / déplacer une réservation | Oui | Oui | Non | Ses réservations, selon règles | Non ; contacter l'acheteur |
| Lire données financières | Oui | Oui | Non | Ses commandes | Non |
| Rembourser / constater un paiement manuel | Oui | Capacité dédiée, désactivée par défaut | Non | Demande uniquement | Non |
| Exporter données | Oui | Selon capacités de lecture | Liste minimale des séquences affectées | Ses documents uniquement | Non |

Un acheteur professionnel n'accède pas à l'historique de ses salariés avec d'autres acheteurs. Le formateur ne voit pas l'ensemble des réponses personnalisées : chaque champ précise sa visibilité, restreinte au gestionnaire par défaut. Le participant distinct reçoit en V1 une convocation personnelle ; un portail participant autonome est hors V1. Les opérations d'un acheteur sont autorisées par la propriété de la commande, pas par la seule connaissance d'un email.

## 4. Objectifs et mesure du succès

| Objectif | Indicateur proposé | Critère initial |
|---|---|---|
| Réservation compréhensible | Réussite d'un scénario sans assistance | Au moins 8 personnes sur 10 lors des tests pilotes |
| Configuration accessible | Temps jusqu'à la première formation réservable | Médiane inférieure à 30 minutes, hors création de compte Stripe |
| Absence de surbooking | Scénarios concurrents individuels et collectifs | Aucun dépassement dans la campagne de tests |
| Cohérence financière | Répétition et désordre des notifications de paiement | Aucun double encaissement, double inscription ou confirmation injustifiée dans les tests |
| Notifications exploitables | Traçabilité des envois et échecs | Chaque événement attendu a un état et une possibilité de relance |
| Utilité administrative | Création d'une inscription groupée de cinq personnes | Réalisable sans changer d'outil ni dupliquer les participants |

Protocole proposé : dix acheteurs réalisent chacun P1 et P2 sur un jeu de données commun ; au moins huit réussissent chaque parcours sans assistance. Trois formateurs minimum testent séparément configuration, inscription manuelle et présences ; les temps bruts et blocages sont conservés avec la médiane.

Les mesures de parcours sont réalisées avec les pilotes. Aucun suivi centralisé des visiteurs ou remontée de données personnelles n'est activé par défaut.

## 5. Périmètre V1

Toutes les exigences ci-dessous font partie du périmètre proposé de V1. L'ordre de réalisation sera progressif ; une version pilote intermédiaire ne doit pas être présentée comme la V1 complète.

| ID | Fonction | Exigence et critère d'acceptation |
|---|---|---|
| CAT-01 | Catalogue | Créer, modifier, publier et archiver une formation avec titre, catégorie, format, description, programme, prérequis, objectifs et image. Une formation archivée conserve ses réservations historiques. |
| CAT-02 | Page publique | Chaque formation publiée possède une URL stable, un contenu indexable et ses prochaines disponibilités ou sessions. Les informations privées n'apparaissent jamais dans le contenu public. |
| DIS-01 | Horaires | Définir horaires hebdomadaires, congés et exceptions par intervenant. Une exception datée prime sur l'horaire habituel. |
| DIS-02 | Disponibilité commune | Déduire cours individuels, sessions collectives, temps de préparation et salles occupées. Tous les intervenants requis doivent être disponibles. |
| IND-01 | Cours individuel | Configurer durée, prix et buffers ; afficher des créneaux et réserver pour un participant. La validation finale recalcule la disponibilité. |
| COL-01 | Session collective | Créer une session composée d'une ou plusieurs séquences datées, avec capacité et échéance d'inscription. L'inscription couvre toutes les séquences. |
| RES-01 | Lieux et salles | Affecter au plus une salle par séquence, ou un lien de visioconférence manuel. En hybride, chaque inscription précise présentiel ou distanciel ; capacité totale et capacité présentielle sont contrôlées. Une salle ne peut pas être réservée deux fois au même moment. |
| INS-01 | Inscription groupée | Un acheteur réserve N places et renseigne N participants distincts. La réservation est refusée si la capacité restante est insuffisante. |
| INS-02 | Formulaire | Collecter coordonnées et champs simples : texte, texte long, sélection et case à cocher. Les contraintes obligatoires sont appliquées côté serveur. |
| INS-03 | Inscription manuelle | Le gestionnaire crée client et participants dans le même parcours, puis choisit paiement manuel ou lien de paiement. Les contrôles de capacité restent applicables. |
| TAR-01 | Tarifs | Prix par séance ou participant, gratuité possible, devise du site et taxes simples configurables selon §7.6. Afficher le montant total avant engagement et conserver les montants par ligne. |
| PAY-01 | Carte bancaire | Paiement Stripe intégral ou acompte. La confirmation dépend du résultat vérifié côté serveur, pas de la seule page de retour du navigateur. |
| PAY-02 | Paiement manuel | Virement ou paiement sur place avec validation explicite du gestionnaire. Une inscription confirmée mais non payée est clairement identifiée comme telle. |
| PAY-03 | Solde | Afficher montant contractuel ajusté, encaissé, remboursé, solde exigible et remboursement restant, selon §7.5. Un lien protégé ne peut encaisser que le solde courant autorisé et n'est plus payable après annulation totale. |
| CAN-01 | Annulation | Appliquer les délais et règles annoncés à la réservation. La libération de place et le remboursement sont suivis séparément. |
| CAN-02 | Déplacement individuel | Déplacer un cours selon les règles et disponibilités, sans perdre le créneau initial si le nouveau ne peut pas être attribué. |
| REF-01 | Remboursement | Un gestionnaire autorisé peut demander un remboursement Stripe total ou partiel dans la limite disponible, en tenant compte des demandes déjà en cours. Un remboursement manuel est enregistré après exécution externe, avec référence et auteur. |
| CLI-01 | Accès client | Réutiliser un compte WordPress ou fournir un accès sécurisé par email pour un invité. L'acheteur ne consulte que ses commandes ; aucun accès à l'historique global d'un participant tiers. Le lien d'accès ne réalise aucune mutation à son ouverture. |
| ADM-01 | Calendrier | Vues jour, semaine et mois ; filtres par formation, intervenant et lieu ; ouverture d'une fiche de session ou réservation. |
| ADM-02 | Fiche client | Réunir coordonnées, commandes, participations, paiements et notes internes. Les notes internes restent invisibles au public. |
| PRE-01 | Présences | Pour chaque participant et séquence : non renseigné, présent, absent ou excusé. L'auteur et la date de modification sont conservés. |
| NOT-01 | Emails | Envoyer confirmation, convocation, rappel, annulation et confirmation de paiement aux destinataires concernés, sans exposer les autres adresses. |
| EXP-01 | Export | Exporter clients, inscriptions et listes de participants en CSV selon les permissions et filtres ; protéger contre les formules de tableur. |
| WP-01 | Insertion | Fournir blocs Gutenberg et shortcodes pour catalogue et réservation, avec fonctionnement indépendant d'un thème particulier. |
| WP-02 | Permissions | Appliquer les droits côté serveur pour chaque action et chaque objet ; ne pas se limiter à masquer des boutons. |
| TAB-01 | Tableau de bord | Montrer prochaines sessions, places disponibles, inscriptions et paiements en attente, avec accès aux listes correspondantes. |

## 6. Parcours de référence

### P1 — Réserver un cours individuel

1. Le particulier ouvre la fiche du cours.
2. Il choisit un créneau dans un fuseau explicitement affiché.
3. Il renseigne ses coordonnées et les questions obligatoires.
4. Il consulte prix total, acompte éventuel et conditions.
5. Le système réserve temporairement le créneau et initie le paiement.
6. Après vérification du paiement, il confirme la réservation et envoie les informations utiles.
7. Le client retrouve sa réservation dans son espace ou via un accès sécurisé.

Cas alternatifs : paiement refusé, abandon, créneau pris entre affichage et validation, notification Stripe retardée. Aucun de ces cas ne doit produire une fausse confirmation.

### P2 — Inscrire cinq personnes à une formation sur deux jours

1. L'acheteur sélectionne une session et cinq places.
2. Il renseigne les coordonnées de l'acheteur et celles des cinq participants.
3. Le serveur vérifie cinq places disponibles pour la session complète.
4. Il reçoit un récapitulatif unique et effectue le paiement ou choisit le virement.
5. Après confirmation, chaque participant est inscrit aux deux dates et reçoit sa convocation si son adresse est fournie. Pour un virement non validé, l'acheteur reçoit seulement un accusé de demande avec échéance ; aucune convocation n'est encore envoyée.
6. Le gestionnaire retrouve une commande, cinq inscriptions et dix lignes de présence pour deux séquences.

### P3 — Inscrire un client par téléphone

Le gestionnaire ouvre une session, crée ou retrouve l'acheteur, ajoute les participants, vérifie la capacité et enregistre l'inscription. Il peut ensuite envoyer un lien de paiement sans ressaisir les coordonnées.

### P4 — Annuler et rembourser

Le client ou gestionnaire demande l'annulation. Le système indique les conséquences avant validation, applique les règles, libère la capacité et enregistre l'annulation. Un remboursement dû ou décidé suit son propre traitement ; un échec de remboursement reste visible et ne réactive pas automatiquement l'inscription.

### P5 — Pointer les présences

Le formateur ouvre une session qui lui est affectée, sélectionne la séquence du jour et renseigne les présences. Il ne peut pas consulter une autre session en modifiant son identifiant dans une requête.

## 7. Règles métier et cas limites

### 7.1 Disponibilités et capacité

- Le catalogue n'occupe aucun créneau. Une session collective publiée bloque ses intervenants et sa salle même avec zéro inscrit ; les inscriptions ne doivent pas compter plusieurs fois cette occupation.
- Un brouillon collectif ne bloque pas les ressources ; publication et allocation de toutes ses séquences sont atomiques. Une session fermée aux inscriptions reste bloquante. L'annulation libère ses occupations futures.
- Une séance individuelle temporaire bloque ses ressources jusqu'à expiration ; la confirmation transforme l'occupation existante sans la doubler. Son expiration ou annulation la libère.
- La présence de données historiques ou d'une session archivée ne bloque pas de nouveaux créneaux.
- Un intervalle inclut le début et exclut la fin. Les buffers prolongent l'occupation autour de la prestation.
- Deux prestations consécutives sont possibles si leurs buffers ne se chevauchent pas.
- Une session avec plusieurs intervenants exige la disponibilité de chacun pour les séquences où il est affecté.
- La V1 ne propose pas de surbooking forcé. Un conflit doit être résolu avant confirmation.
- Une inscription collective consomme une place sur toute la session. La vente par journée est hors V1.
- Les réservations temporaires actives, inscriptions en attente de validation non expirées et inscriptions confirmées consomment la capacité ; les expirées et annulées ne la consomment plus. Un même groupe n'est compté qu'une fois lors du passage d'un état à l'autre.
- Les échéances expirées sont ignorées lors de chaque allocation, même si la tâche de nettoyage n'a pas encore tourné ; le navigateur ne détermine pas l'heure d'expiration.
- La capacité ne peut pas être réduite sous le nombre de places déjà occupées, y compris les attentes et réservations temporaires actives.
- En hybride, la capacité totale et le quota présentiel sont deux limites simultanées. Le quota présentiel ne dépasse pas la capacité de la plus petite salle utilisée. Le mode est fixé par participant ; sa modification exige un nouveau contrôle atomique. Un seul prix par participant, quel que soit le mode, est prévu en V1.
- Au moins un intervenant doit être affecté à chaque séquence publiée. La séance individuelle réserve une équipe explicitement définie pour l'offre ; l'allocation automatique d'équipes interchangeables est hors V1.
- Le délai minimal de réservation, l'horizon maximal, le pas des créneaux et la clôture d'inscription sont configurables. Une clôture ne dépasse pas le début de la première séquence ; les liens de paiement ne permettent pas de la contourner.
- Ajouter un congé ou modifier un horaire ne supprime pas les séances existantes : le système liste les conflits et refuse le changement tant qu'ils ne sont pas résolus.
- Les disponibilités saisies dans session-flow-bookings sont la référence V1. Les événements externes ne sont pas connus avant les connecteurs V2 ; ce point est affiché dans la configuration. Aucun calcul automatique de trajet entre lieux n'est prévu, les buffers doivent le couvrir.
- Une réservation de groupe est atomique : toutes les places demandées sont obtenues, ou aucune.
- Les réservations concurrentes sont arbitrées côté serveur avec une garantie d'atomicité ; la disponibilité affichée n'est pas une garantie de réservation.

### 7.2 Paiement et réservation temporaire

- Hypothèse proposée : blocage d'un créneau pendant 15 minutes pour un paiement en ligne, configurable par l'administrateur.
- Le montant et les taxes sont recalculés côté serveur ; le navigateur ne fait pas autorité.
- Les sommes sont stockées sans calcul flottant imprécis et les règles d'arrondi sont cohérentes au niveau de la commande.
- Un acompte accepté confirme l'inscription si ce mode était proposé ; le solde reste visible.
- Un paiement manuel peut rester en attente de validation jusqu'à une échéance configurable ; cette attente consomme des places et doit pouvoir expirer.
- Une confirmation administrative sans encaissement est possible uniquement avec une action explicite et une trace de l'auteur.
- Les notifications Stripe sont authentifiées, dédupliquées et traitées même si elles arrivent dans un ordre différent.
- Un paiement tardif après expiration peut reprendre la capacité atomiquement seulement si la session reste publiée, l'inscription ouverte et la commande non annulée. Sinon, aucune confirmation ; une exception financière est créée et un remboursement est à traiter.
- Une commande explicitement annulée n'est jamais réactivée par une notification tardive. Annulation, expiration et confirmation concurrentes doivent aboutir à une seule décision métier cohérente.
- Après un délai réseau incertain, l'état devient « à vérifier » ; une nouvelle tentative d'encaissement est interdite tant que l'opération précédente n'est pas rapprochée. Toute demande de paiement et de remboursement porte un identifiant stable de déduplication.
- Le résultat vérifié d'un paiement est durablement enregistré avant l'envoi d'emails. Les notifications manquées sont récupérables par rapprochement périodique et manuel, avec historique.
- Une anomalie affiche un responsable de traitement (administrateur ou gestionnaire autorisé), le montant, la cause, l'ancienneté et les actions permises. Elle reste ouverte jusqu'à résolution financière vérifiée ; le client voit que son paiement a été reçu mais que sa place n'est pas confirmée.
- Aucune donnée de carte bancaire n'est stockée par le plugin.

### 7.3 Annulations et changements

- Les conditions applicables sont enregistrées à la commande pour éviter une modification rétroactive silencieuse.
- Une annulation partielle de groupe identifie les participants retirés et leurs lignes de prix. La remise de dette et le remboursement sont enregistrés séparément, sans modifier les autres inscriptions.
- Si l'organisateur annule une session entière, toutes les inscriptions concernées sont annulées, les soldes correspondants cessent d'être exigibles et les montants encaissés correspondants sont à rembourser. Le traitement est repris sans doublon en cas d'interruption.
- Une inscription annulée ne peut plus être payée via un ancien lien. Les éventuels frais d'annulation déjà couverts par la politique restent visibles ; aucun nouveau débit automatique ni recouvrement de pénalité supplémentaire n'est prévu en V1.
- La somme des remboursements réussis et réservés par des demandes en cours ne peut pas dépasser le montant encaissé disponible ; deux opérateurs ne peuvent pas rembourser le même montant en parallèle.
- Un déplacement autonome en V1 concerne uniquement un cours individuel à tarif inchangé. Les autres changements passent par le gestionnaire.
- Dès qu'une session collective porte une inscription confirmée ou une attente active, dates, durée et lieu sont figés en V1. Un remplacement d'intervenant reste possible avec vérification des conflits et notification. Pour changer les dates, annuler puis proposer une nouvelle inscription ; le transfert/report collectif automatisé relève de V2.
- Une séance individuelle déplacée conserve son prix et son historique. Le nouveau créneau est alloué et l'ancien libéré dans une même décision atomique ; les anciens rappels sont invalidés.
- Une session ayant des inscriptions n'est pas supprimée physiquement ; elle est annulée ou archivée.

### 7.4 Identité et temps

- L'acheteur peut être participant, sans duplication forcée de son identité.
- Un rapprochement avec un compte existant ne donne aucun accès avant vérification de l'identité.
- L'email d'un participant n'est pas nécessairement unique : plusieurs participants peuvent partager un contact. L'identifiant interne distingue les personnes.
- Pour un participant sans email, la convocation est transmise à l'acheteur.
- Chaque lieu/session possède un fuseau de référence. Les instants sont stockés avec leur référence temporelle et affichés sans ambiguïté.
- Les horaires inexistants ou ambigus lors d'un changement d'heure sont détectés ; aucune conversion silencieuse n'est acceptée.

### 7.5 Montants, dettes et remboursements

- Conserver les lignes de commande initiales et les ajustements, sans réécrire l'historique. Les montants sont ventilés par inscription pour permettre une annulation partielle.
- Calculer par ligne d’inscription : `T_i` = total contractuel après ajustements ; `P_i` = encaissements réussis alloués ; `R_i` = remboursements réussis alloués ; `N_i = P_i - R_i`. La dette de la ligne vaut `max(T_i - N_i, 0)` et son trop-perçu `max(N_i - T_i, 0)`. Le solde payable de la commande additionne les dettes des lignes actives ; le remboursement dû additionne les trop-perçus par ligne, déduction faite des réserves de remboursement en cours. Ne pas compenser automatiquement le remboursement d’une place annulée avec la dette des autres participants.
- Pour une annulation, réduire d'abord le total contractuel à ce qui reste dû selon la politique. Un remboursement ne recrée donc pas automatiquement une dette.
- Exemple : commande de 200 €, payée 200 €, annulation intégrale remboursable. `T` devient 0 ; avant remboursement le trop-perçu est 200 €, après remboursement `N = 0`, reste dû = 0.
- Exemple : cinq places à 100 €, acompte total de 100 €. Une place annulée sans frais ramène `T` à 400 €. En V1, l'acompte est ventilé à parts égales : 20 € sont à restituer pour la place annulée ; avant comme après restitution, le reste dû des quatre places est de 320 € ; le remboursement de 20 € reste suivi séparément jusqu’à exécution.
- Les ajustements de prix sont des opérations distinctes et journalisées. Un remboursement commercial conservant l'inscription doit indiquer s'il réduit le prix ; par défaut, une réduction équivalente évite une relance injustifiée.
- Un remboursement externe ou contestation détecté crée une exception de rapprochement. Il ne débite pas automatiquement le client ni n'annule sa place. Le traitement d'un litige se fait dans l'outil du prestataire en V1.
- Une confirmation administrative non payée conserve sa dette jusqu'à paiement ou remise explicite. Un solde arrivé à échéance devient « en retard », sans annulation automatique de l'inscription.

### 7.6 Limites tarifaires V1

- Une devise par site, non modifiable tant que des commandes non soldées existent ; les commandes historiques conservent toujours leur devise.
- Un prix de base par offre/session, sans promotion, tarification géographique ou exemption automatisée. Un taux de taxe simple peut être configuré par offre, avec saisie HT ou TTC explicitement choisie. Les règles d'arrondi par ligne et la ventilation de l'acompte sont documentées dans la conception détaillée et vérifiées par exemples avant développement du paiement.
- L'acompte est un montant ou un pourcentage défini dans l'offre, strictement positif et inférieur au total ; sinon le paiement intégral s'applique. La dernière ligne absorbe le reliquat d'arrondi afin de préserver le total.
- La date d'exigibilité du solde est affichée avant achat et enregistrée. Si elle est déjà passée au moment de réserver, seul le paiement intégral est proposé.
- Une formation gratuite est confirmée après contrôle de capacité sans appel au prestataire et sans paiement fictif.
- Pour les paiements manuels : choix distinct entre « attente de virement avec expiration » et « confirmation avec paiement ultérieur ». Seule une personne habilitée constate l'encaissement, avec date, montant et référence. Les corrections se font par contre-écriture journalisée.
- La facturation complète reste V2. Avant une mise en production payante, l'organisme doit disposer d'un processus de facturation externe adapté à son marché ; cette dépendance doit être testée avec les pilotes.

### 7.7 Identité, emails et abus

- Les données publiques n'exposent ni emails, ni participants, ni liens de réunion. Aucun cache partagé ne doit servir une page client ou de paiement privée.
- Un lien invité est limité à un acheteur et à un périmètre explicite. Son expiration, sa révocation et la demande d'un nouveau lien sont testées. Changer l'email de contact ne transfère pas automatiquement la propriété d'une commande.
- Si une adresse est partagée entre plusieurs participants, elle ne donne pas accès à leurs profils globaux. Aucun rapprochement automatique de personnes sur le seul nom ou email.
- La validation des formulaires distingue les informations de l'acheteur des réponses propres à chaque participant. Seuls les champs nécessaires au formateur lui sont exposés.
- Les réservations temporaires et inscriptions gratuites sont limitées par quotas configurables et contrôles d'abus. Des tentatives répétées ne doivent pas permettre de bloquer indéfiniment la capacité. Les données de limitation sont minimisées et purgées.
- Événements distincts : accusé de demande en attente, confirmation de place, confirmation financière, convocation, rappel, annulation. Un email annonçant une place confirmée n'est produit qu'après confirmation durable de l'inscription.
- Un rappel est revérifié avant envoi : inscription active, bonne séquence et bonne version des dates. Un rappel périmé est supprimé et une relance ne renvoie pas un ancien horaire.
- « Envoyé » signifie accepté par le transport email, pas livré en boîte de réception. L'envoi exactement une fois ne peut pas être promis en cas de réponse réseau perdue ; le système déduplique les événements, rend l'incertitude visible et maîtrise les reprises.

## 8. États et transitions

Les états d'inscription, de paiement et de session sont indépendants.

| Objet | États principaux | Transitions attendues |
|---|---|---|
| Inscription | Temporaire, en attente de validation, confirmée, expirée, refusée, annulée | Temporaire vers confirmée après paiement vérifié ; temporaire vers expirée à échéance ; attente vers confirmée après décision, ou expirée/refusée ; confirmée vers annulée selon les règles ; annulation irréversible par notification de paiement |
| Paiement | Créé, en cours, à vérifier, réussi, échoué | Une réponse incertaine impose un rapprochement avant nouvelle tentative ; un succès vérifié n'est pas rétrogradé par un ancien événement d'échec |
| Remboursement | Demandé, en cours, réussi, échoué | L'inscription peut déjà être annulée pendant le traitement ; le résultat financier reste suivi |
| Session | Brouillon, publiée, fermée aux inscriptions, terminée, annulée, archivée | Une session complète peut rester publiée ; son indisponibilité découle de la capacité |
| Notification | À envoyer, en cours, envoyée, échouée | Reprise contrôlée après échec, avec historique des tentatives |

Le total payé et le total remboursé proviennent des opérations financières confirmées ; le solde tient également compte du total contractuel ajusté (§7.5). Un retour navigateur ne suffit jamais à modifier ces totaux.

## 9. Modèle de données conceptuel

| Entité | Rôle et relations |
|---|---|
| Formation | Offre du catalogue ; format individuel ou collectif ; programme et paramètres commerciaux |
| Session | Réalisation d'une formation ; pour un cours individuel, créée lors de la réservation du créneau |
| Séquence | Intervalle daté d'une session ; une session peut en contenir plusieurs |
| Intervenant | Profil lié éventuellement à un compte WordPress ; horaires et exceptions |
| Affectation | Relation entre intervenant et séquence ; permet plusieurs intervenants |
| Lieu / salle | Adresse, fuseau et ressource réservable associée aux séquences |
| Client / acheteur | Contact à l'origine d'une commande, avec compte WordPress facultatif |
| Participant | Personne inscrite, distincte de l'acheteur si nécessaire |
| Commande | Un acheteur, une devise, un instantané de prix/conditions et plusieurs inscriptions à une même session en V1 |
| Inscription | Relation entre participant et session ; état, mode présentiel/distanciel et réponses au formulaire ; les refusées ne consomment aucune place |
| Réservation temporaire | Occupation limitée dans le temps d'un créneau ou de places |
| Ligne de commande / ajustement | Prix, taxe et part d'acompte par inscription ; ajustements traçables de dette et de remboursement |
| Paiement / remboursement | Opérations financières identifiées séparément, rattachées à la commande et ventilées sur ses lignes |
| Exception financière | Incohérence à traiter, montant, cause, responsable et résolution vérifiée |
| Présence | Participant inscrit × séquence, avec statut et auteur |
| Notification | Destinataire, événement, état et tentatives d'envoi |
| Événement d'audit | Action, date, acteur et références utiles ; sans secrets ni données bancaires |

Ce modèle n'impose pas le schéma SQL ni le framework. Le panier multi-sessions, les forfaits et le compte entreprise complet ne font pas partie du modèle fonctionnel exposé en V1.

## 10. UX et navigation

### Administration

Navigation principale proposée : tableau de bord, calendrier, formations, sessions, clients et participants, paiements, réglages.

- Une fiche session réunit intervenants, dates, lieu, inscrits, présences et état financier accessible selon le rôle.
- La création rapide d'un client se fait sans quitter une inscription.
- Chaque refus de réservation fournit une cause exploitable sans dévoiler des informations privées.
- L'état vide guide vers l'action nécessaire : créer une formation, définir des horaires ou connecter le paiement.
- Les fonctions futures ne remplissent pas les écrans V1 de boutons verrouillés.
- Les opérations destructrices ou financières présentent leurs conséquences avant validation.

### Parcours public

- Afficher tôt dates, durée, lieu, prix total et places disponibles lorsque pertinent.
- Distinguer visuellement acheteur et participants.
- Préserver les données saisies lors d'une erreur récupérable.
- Afficher une attente explicite si le paiement est en cours de vérification.
- Le contenu éditorial des formations est accessible sans exécuter le formulaire interactif.
- Le lien de visioconférence reste réservé aux personnes autorisées.

## 11. Exigences non fonctionnelles

| ID | Domaine | Exigence vérifiable |
|---|---|---|
| NFR-01 | Autorisations | Tests croisés entre administrateur, gestionnaire, formateur, acheteur et invité sur lecture, modification et export |
| NFR-02 | Entrées et sorties | Validation serveur, requêtes paramétrées et échappement adapté aux sorties ; protection des actions contre les requêtes non autorisées |
| NFR-03 | Accès invités | Liens limités, expirables et révocables ; limitation des tentatives ; aucune clé administrative dans le navigateur |
| NFR-04 | Données | Export et suppression/anonymisation selon les règles retenues ; durées de conservation documentées avant lancement |
| NFR-05 | Journalisation | Aucune carte, secret, jeton d'accès ou donnée personnelle superflue dans les journaux ; accès réservé |
| NFR-06 | Accessibilité | Cible WCAG 2.2 AA ; tests clavier, focus, libellés, messages d'erreur et lecteur d'écran sur les parcours critiques |
| NFR-07 | Responsive | Réservation et présence utilisables dès 360 px de largeur sans défilement horizontal du parcours |
| NFR-08 | Performance | Objectif proposé : p95 inférieur à 1 seconde pour la recherche de créneaux, hors services externes, sur une recherche de 30 jours avec 50 intervenants et 10 000 réservations, à 20 recherches concurrentes pendant 5 minutes après préchauffage ; environnement CPU/RAM/base et caches consignés avant mesure |
| NFR-09 | Traitements différés | Envoi et reprise possibles sur un site peu fréquenté ; détection des tâches en retard et procédure de déclenchement serveur documentée |
| NFR-10 | Migrations | Migrations versionnées, reprise après échec et essais sur copie de données ; sauvegarde avant changement de schéma |
| NFR-11 | Désinstallation | Conservation des données par défaut ; suppression volontaire, explicite et documentée |
| NFR-12 | Compatibilité | Matrice WordPress/PHP/thèmes arrêtée avant bêta et vérifiée en intégration continue ; aucun style global ne perturbe l'administration WordPress |
| NFR-13 | Observabilité | État des paiements et emails, erreurs compréhensibles et identifiant de diagnostic dès V1 ; console intégrations avancée en V2 |
| NFR-14 | Durabilité | Après arrêt entre encaissement vérifié, écriture métier et notification, reprise sans perdre l'opération ni doubler l'inscription ; scénarios d'arrêt injectés testés |
| NFR-15 | Cache et données privées | Aucun cache public de compte, formulaire personnalisé, solde ou lien de réunion ; aucune donnée privée dans une page indexable |
| NFR-16 | Anti-abus | Quotas de réservations temporaires et de demandes d'accès testés, expiration effective sans cron et absence de conservation illimitée des données de limitation |
| NFR-17 | Répertoire WordPress.org | Chaque incrément distribué passe Plugin Check officiel (statique et runtime) et la revue manuelle ; HTTP serveur via API WordPress, absence de fonctions d'exécution dangereuses, préfixe unique d'au moins quatre caractères, aucune bibliothèque du cœur dupliquée |
| NFR-18 | Paquet distribuable | Version du fichier PHP principal exactement égale au Stable tag ; sources lisibles incluses avec les fichiers compilés, services tiers documentés, licence GPLv2+ et dépendances/assets compatibles |

Les exigences de protection et de conservation sont des exigences produit. Les obligations locales, textes contractuels et mécanismes fiscaux restent à faire valider pour le marché choisi.

## 12. Hors périmètre V1 et versions suivantes

| Version | Périmètre prévu |
|---|---|
| V2 | Forfaits et crédits, parcours mixtes, récurrence, reports avancés, listes d'attente, Google/Outlook, Zoom/Meet, champs conditionnels et pièces jointes, promotions, PayPal, échéanciers, factures/avoirs, communications avancées, satisfaction, personnalisation, multilingue, import Amelia et diagnostics d'intégration |
| V3 | Entreprises et espace responsable, sessions privées, devis, documents pédagogiques et contractuels, émargement signé, évaluations, supports, multi-établissements, permissions fines, rémunération des intervenants, facturation entreprise, reporting et qualité |
| V4 | API publique, webhooks, Make/Zapier/n8n, WooCommerce, LMS, CRM, comptabilité, SMS/WhatsApp, mesure marketing, Elementor/Divi, extensions développeurs, migrations avancées et multisite |
| V5 | Application mobile, QR code, mode hors connexion, abonnements, crédits partagés, réseau d'établissements, marque blanche, analyse avancée et assistance intelligente |

La liste détaillée de chaque version reste dans [fonctionnalites.md](fonctionnalites.md). La V1 ne délivre pas de certification réglementaire, de LMS complet, de marketplace ni de SaaS autonome. Un reçu de paiement V1 n'est pas présenté comme une facture légale complète.

## 13. Validation et recette

| Test | Scénario | Résultat requis |
|---|---|---|
| REC-01 | Deux clients valident simultanément la dernière place | Une seule inscription obtient la place ; l'autre reçoit un refus clair |
| REC-02 | Un formateur est affecté à une formation collective | Aucun cours individuel ne peut être confirmé sur les séquences occupées et leurs buffers |
| REC-03 | Un acheteur demande cinq places alors qu'il en reste quatre | Aucune inscription partielle ni paiement confirmé |
| REC-04 | Notification de paiement reçue trois fois | Une seule opération financière et une seule confirmation logique |
| REC-05 | Paiement reçu après expiration et réattribution du créneau | Pas de surbooking ; exception financière visible avec traitement prévu |
| REC-06 | Échec du paiement ou fermeture du navigateur | Aucun faux statut payé ; la notification serveur peut terminer le traitement sans navigateur |
| REC-07 | Acompte puis solde | Sommes cohérentes, pas de débit excédentaire et état final soldé |
| REC-08 | Annulation d'un participant dans un groupe de cinq | Une place libérée, quatre participants conservés et remboursement correctement plafonné |
| REC-09 | Changement de fuseau et passage heure d'été/hiver | Même instant réel, affichage explicite et absence de créneau ambigu silencieux |
| REC-10 | Formateur ou acheteur tente d'accéder aux données d'un autre | Accès refusé côté serveur, y compris sur exports et liens directs |
| REC-11 | Email échoué puis relancé | Échec visible, reprise possible, contenu et destinataire corrects |
| REC-12 | Mise à jour d'une installation contenant des inscriptions | Conservation des identifiants, états, montants, présences et relations |
| REC-13 | Navigation mobile et clavier | Réservation et pointage terminés sans blocage ni dépendance à la souris |
| REC-14 | Modification de durée ou prix dans le catalogue | Aucun changement rétroactif silencieux des réservations existantes |
| REC-15 | Session de deux jours, cinq participants | Les dix présences peuvent être suivies indépendamment pour deux séquences |
| REC-16 | Déplacement vers un créneau devenu indisponible | Le rendez-vous d'origine est conservé et le client est informé |

### Recette complémentaire issue de la revue

| Test | Exigences | Scénario et résultat requis |
|---|---|---|
| REC-17 | DIS-02, COL-01 | Session collective publiée sans inscrit : intervenants/salle bloqués une seule fois ; annulation libère les créneaux futurs |
| REC-18 | INS-01, PAY-02 | Virement en attente et blocage temporaire présents : ils sont comptés exactement une fois ; leur expiration libère les places même sans cron |
| REC-19 | PAY-03, CAN-01, REF-01 | Commande payée 200 €, annulée et remboursée : solde final nul, ancien lien non payable |
| REC-20 | INS-01, REF-01 | Groupe avec acompte, une annulation : ventilation et solde conformes à l'exemple §7.5 ; aucune modification des quatre inscriptions restantes |
| REC-21 | PAY-01, CAN-01 | Annulation explicite puis notification de succès tardive : aucune réactivation, exception financière créée |
| REC-22 | RES-01 | Session hybride : quota présentiel plein mais places distantes libres ; seul le distanciel peut encore être réservé |
| REC-23 | REF-01 | Deux demandes de remboursement concurrentes : leur total réservé ne dépasse pas l'encaissement disponible |
| REC-24 | TAR-01 | Formation gratuite : inscription et confirmation sans appel Stripe ni ligne de paiement fictive |
| REC-25 | CLI-01, WP-02 | Acheteur d'entreprise et email partagé : aucune lecture de l'historique privé d'un participant ni mutation au simple clic sur un lien |
| REC-26 | NOT-01, CAN-02 | Déplacement ou annulation avant rappel : aucun rappel avec ancienne date ni rappel d'inscription annulée |
| REC-27 | PAY-01, NFR-14 | Arrêt après encaissement et avant confirmation : reprise cohérente et une seule inscription |
| REC-28 | DIS-01 | Ajout d'un congé recouvrant une session : conflit explicite, aucune disparition silencieuse des réservations |
| REC-29 | CAN-01, REF-01 | Annulation d'une session entière interrompue puis reprise : chaque participant est traité une seule fois et chaque remboursement reste suivi |
| REC-30 | NFR-15, NFR-16 | Cache partagé et abus de blocages : aucune réponse privée servie à autrui, quotas actifs et capacités libérées à échéance |

Avant développement de chaque lot, sa fiche technique relie toutes ses exigences à au moins un scénario de test, y compris les exigences sans test dédié dans cette liste. Un statut HTTP, un bouton visible ou un email reçu ne remplace pas la vérification des états métier persistés.

### Conditions de sortie V1

- Exigences fonctionnelles V1 livrées et recette critique réussie.
- NFR-17/18 vérifiés sur le paquet final ; rapport Plugin Check et preuves manuelles WordPress.org conservés. Un passage automatisé ne vaut pas acceptation du répertoire et n’autorise pas une publication.
- Aucun défaut bloquant connu sur réservations, autorisations, paiements ou conservation des données.
- Parcours testés avec au moins trois formateurs pilotes et des acheteurs représentatifs.
- Procédures de configuration, sauvegarde, mise à jour et traitement des incidents disponibles.
- Matrice de compatibilité et environnement de performance documentés.
- Paramètres commerciaux et documents applicables au marché de lancement validés ; processus de facturation externe opérationnel tant que V2 n'est pas livrée.

## 14. Plan de réalisation

| Lot | Livrable | Dépendance / sortie |
|---|---|---|
| 0 — Cadrage | Décision de réutilisation, structure du dépôt, environnement WordPress et schéma conceptuel | Hypothèses critiques tranchées |
| 1 — Métier | Catalogue, intervenants, sessions, séquences et ressources | Création manuelle cohérente |
| 2 — Disponibilités | Calcul des créneaux et allocation atomique | Tests de conflit et capacité réussis |
| 3 — Inscriptions | Parcours individuel/collectif, acheteur et participants | Réservation sans paiement testable de bout en bout |
| 4 — Financier | Stripe, acompte, paiement manuel, solde, annulation et remboursement | Scénarios asynchrones et rapprochement validés |
| 5 — Exploitation | Emails, espace client, présences, exports et tableau de bord | Parcours administratifs complets |
| 6 — Stabilisation | Accessibilité, sécurité, performances, migrations et pilotes | Conditions de sortie V1 satisfaites |

Aucune date de livraison n'est engagée dans ce document. L'estimation dépendra du choix de base technique, de l'équipe et des décisions encore ouvertes.

## 15. Risques et mesures prévues

| Risque | Réponse prévue |
|---|---|
| Périmètre V1 trop large | Livrer par lots, limiter les intégrations et rendre tout changement de périmètre explicite |
| Reprise d'un code complexe ou incomplet | Décider de la réutilisation après audit ciblé ; ne pas supposer que les intégrations premium sont présentes |
| Disponibilités et encaissements désynchronisés | Allocation atomique, opérations idempotentes, rapprochement et exceptions visibles |
| Sessions multi-dates mal modélisées | Distinguer session, séquence et inscription dès le départ |
| Emails ou tâches différées non exécutés | Suivi, reprise et configuration serveur documentée |
| Besoins réglementaires mal définis | Choisir le marché avant lancement et faire valider les exigences applicables |
| Dépendance excessive aux services tiers | Traitement explicite des indisponibilités et maintien des données métier locales |
| Extension prématurée vers un LMS ou ERP | Maintenir les limites V1 et utiliser les versions suivantes pour les besoins validés |

## 16. Décisions encore attendues

1. Confirmer le plugin WordPress comme produit de lancement et choisir entre nouvelle implémentation et dérivé de la base existante.
2. Choisir le pays, la langue et la devise de lancement.
3. Confirmer Stripe et les règles proposées d'acompte, solde et réservation temporaire.
4. Définir les règles commerciales d'annulation, de remboursement et de paiement manuel.
5. Valider le caractère indivisible des sessions collectives en V1.
6. Choisir le modèle économique, l'équipe et le calendrier ; le nom du plugin est confirmé : session-flow-bookings.
7. Identifier les formateurs pilotes et leurs scénarios concrets.

Ces décisions restent ouvertes ; les propositions de ce PRD ne valent pas validation implicite.


## 17. Synthèse de revue — passage de 0.1 à 0.2

| Priorité | Problème identifié | Amélioration |
|---|---|---|
| Critique | Le calcul du solde pouvait recréer une dette après remboursement | Total contractuel ajusté, ventilation et exemples chiffrés (§7.5) |
| Critique | Paiement tardif susceptible de réactiver une annulation | Distinction expiration/annulation, rapprochement et exception financière (§7.2) |
| Haute | Incohérence de capacité pour les virements en attente | Comptage unique de toutes les occupations et expiration sans cron (§7.1) |
| Haute | Occupation des intervenants d'une session vide non définie | Allocation dès publication, indépendante du nombre d'inscrits (§7.1) |
| Haute | Remboursements concurrents et paiements incertains non cadrés | Réservation des montants, déduplication et état à vérifier (§7.2–7.3) |
| Haute | Droits acheteur, participant et formateur ambigus | Matrice explicite et contrôle des champs visibles (§3) |
| Haute | Mode hybride sans capacité présentielle | Double contrôle et mode par inscription (§7.1) |
| Haute | Report collectif promis implicitement en V1 alors qu'il relevait de V2 | Dates figées après inscription et procédure V1 explicite (§7.3) |
| Moyenne | Gratuité, acompte, échéances et remboursement manuel incomplets | Limites et transitions précisées (§7.5–7.6) |
| Moyenne | Fiabilité des emails confondue avec livraison garantie | États observables, rappels versionnés et limites d'envoi (§7.7) |
| Moyenne | Performance et succès pilote insuffisamment mesurables | Protocoles proposés et recette portée de 16 à 30 scénarios |
| Moyenne | Dépendances de lancement peu visibles | Facturation externe et absence de synchronisation externe en V1 explicitées |

### Arbitrages supplémentaires avant les lots concernés

| Arbitrage | Responsable proposé | Moment limite |
|---|---|---|
| Équipe fixe des cours individuels, quota hybride et capacité des salles | Porteur du produit avec formateurs pilotes | Avant lots 1–2 |
| Conditions d'annulation, ventilation d'acompte, date du solde et durée des attentes manuelles | Porteur du produit | Avant lot 4 |
| Marché, règles de taxe simples et processus externe de facturation | Porteur du produit avec accompagnement compétent | Avant pilote payant |
| Visibilité des champs, capacités de remboursement et conservation des données | Porteur du produit | Avant lots 3–5 |
| Matrice WordPress/PHP et dimensionnement cible | Responsable technique | Avant bêta |

La revue n'engage ni achat, ni déploiement, ni choix définitif de framework ou réutilisation du code Amelia.


## 18. Contrats système — version 0.1.1

Les [contrats système](contracts/README.md) précisent API internes V1, modèles persistés, invariants et transitions. Ils restent proposés pour implémentation ; ils n’ouvrent pas l’API aux intégrateurs tiers avant V4.

Précision introduite en PRD 0.3 : le solde et le remboursement s’agrègent par ligne d’inscription, afin qu’une annulation partielle ne compense pas silencieusement la dette des participants restants. Le périmètre fonctionnel V1–V5 reste identique.


## 19. Révision 0.4 — identité et qualité de distribution

Décisions demandées le 10 novembre 2026 :

- Nom et slug : `session-flow-bookings` ; fichier principal `session-flow-bookings.php`, text domain identique, namespace `SessionFlowBookings`.
- Préfixe interne : `sfbk_` (quatre lettres distinctives), constantes `SFBK_` ; remplace la proposition `sfb_`. Aucune migration de données existantes : le code produit n'est pas encore implémenté.
- Le dossier physique reste `session-flow-booking/` ; ce chemin de travail n'est pas l'identité du plugin. L'environnement monte ce dossier sous le slug correct.
- Exigences NFR-17/18 ajoutées ; [procédure de validation WordPress.org](../docs/wordpress-org.md) et skills locaux font autorité pour les commandes et preuves.
- HTTP WordPress obligatoire dans le code propre au produit ; adaptateur WordPress prévu pour Stripe. Les fonctions PHP d'exécution dynamique/système sont interdites dans le code distribué. Les outils de développement Node/shell restent hors paquet.
- WordPress.org impose la compatibilité GPL et recommande GPLv2+ ; le projet choisit GPLv2 ou ultérieure. L'inclusion des sources dans le paquet et la porte stricte sur les avertissements sont des exigences locales plus fortes.
- Les contrats passent en 0.1.1 pour alignement documentaire ; API OpenAPI 3.1.1, chemins, opérations, DTO et invariants métier restent inchangés. Les mentions 0.2/0.3 dans les sections historiques décrivent leurs révisions respectives.
- Le PRD 0.4 local remplace la source de préparation 0.3, conservée sans modification dans `../ameliabooking/specs/`. Provenance : `specs/contract-sources.json`.

Ces demandes autorisent les mises à jour documentaires et l'outillage wp-env/Plugin Check, pas le lancement implicite du Sprint 1 ni la validation de ses autres choix ouverts.
