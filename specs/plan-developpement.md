# session-flow-bookings — Plan de développement V1 en cinq sprints

Date : 10 novembre 2026 — alignement PRD 0.4

Base documentaire de travail (cadrage de chaque sprint à valider explicitement) : [PRD 0.4](prd.md), [fonctionnalités](fonctionnalites.md) et [contrats système 0.1.1](contracts/README.md).

Ce plan organise la V1 uniquement. Les fonctions V2–V5 restent hors de ces cinq sprints. Les sprints correspondent à des incréments fonctionnels ; leur durée sera estimée à partir des tickets et de la capacité de l'équipe. Aucune promesse de calendrier n'est déduite du nombre de sprints.

## Vue d'ensemble

| Sprint | Objectif unique | Démonstration de fin |
|---|---|---|
| 1 | Disposer d'un socle WordPress installable et sécurisé | Installer le plugin et accéder à une administration respectant les rôles |
| 2 | Garantir un planning cohérent pour les formations | Publier une session et constater le blocage de tous ses intervenants et de sa salle |
| 3 | Permettre une inscription complète sans paiement en ligne | Réserver un cours ou cinq places à une session, puis retrouver la réservation |
| 4 | Garantir la cohérence entre réservation et argent | Encaisser un acompte, annuler une place et restituer exactement le montant dû |
| 5 | Rendre la V1 exploitable au quotidien | Gérer une session, ses communications, ses présences et ses exports avec les utilisateurs pilotes |

## Règles communes de passage

Chaque sprint dépend de la sortie du précédent. Avant de passer au suivant :

1. Les critères d'acceptation du sprint sont démontrés sur un WordPress de test, avec données fictives.
2. Les tests automatisés concernés et les régressions des sprints précédents passent.
3. Aucun défaut bloquant connu sur intégrité des réservations, autorisations, paiements ou conservation des données ne subsiste.
4. Les routes livrées respectent OpenAPI et les invariants ; les différences nécessaires sont corrigées dans les spécifications et le code ensemble.
5. Une fiche de recette indique version du code, environnement, cas exécutés, résultats et anomalies. Les captures complètent les assertions sur les données persistées.
6. La porte WordPress.org NFR-17/18 est vérifiée : Plugin Check statique/runtime, HTTP WordPress, fonctions dangereuses, préfixes, bibliothèques du cœur, versions, sources, services tiers et licences.
7. La démonstration métier est réalisée. Les défauts mineurs restants ont une priorité, un responsable et une cible ; aucun critère obligatoire ne peut être reporté implicitement.

Les tests de concurrence se font avec au moins deux connexions indépendantes à une base transactionnelle. Deux clics manuels ou un mock de dépôt ne suffisent pas à prouver l'absence de surbooking. Les tests de reprise injectent des interruptions aux frontières de transaction et de traitement externe.

Sécurité, accessibilité et tests sont intégrés à chaque sprint. Le sprint 5 consolide ces garanties ; il ne les introduit pas pour la première fois.

## Sprint 1 — Socle WordPress installable et sécurisé

**Objectif :** disposer d'une base de plugin sur laquelle les fonctionnalités peuvent être développées sans contourner les contrats.

### Travaux

- Choisir et consigner la base technique : code neuf ou réutilisation contrôlée ; fixer les versions WordPress, PHP et base de données supportées.
- Créer le dépôt du produit, son identité technique et un environnement WordPress local reproductible. Conserver Amelia comme référence, distincte du produit.
- Mettre en place bootstrap, chargement des dépendances, activation/désactivation et migrations versionnées ; prévoir conservation des données à la désinstallation par défaut.
- Créer le menu et la navigation admin : tableau de bord, calendrier, formations, sessions, clients, paiements, réglages. Les écrans non livrés sont explicitement indiqués comme tels dans l'environnement de développement.
- Définir capacités WordPress et protections des routes ; préparer les politiques de propriété et d'affectation aux objets.
- Installer le socle API : DTO fermés, erreurs uniformes, request_id, version optimiste, idempotence et redaction des journaux.
- Installer les tables/supports techniques d'audit, idempotence, inbox/outbox et jobs avec baux ; le raccordement métier se fait aux sprints suivants.
- Configurer wp-env Docker et Plugin Check officiel : installation épinglée, exécution statique/runtime, rapports conservés et blocage avant clôture.
- Ajouter CI, validation OpenAPI avec un validateur de référence, tests PHP et outil de test des interfaces. Le script Python existant reste un contrôle complémentaire.

### Critères d'acceptation

| ID | Critère |
|---|---|
| S1-A1 | Activation sur une installation vierge sans erreur fatale ; réactivation sans doublon de tables, rôles ou tâches |
| S1-A2 | Navigation admin utilisable ; un formateur n'accède pas aux réglages ou aux secrets, y compris par appel HTTP direct |
| S1-A3 | Champs inconnus et types erronés rejetés ; codes 401/403/404/412/422/428 cohérents sur les routes de démonstration |
| S1-A4 | Deux appels avec même clé/intention ne produisent qu'un effet ; une clé réutilisée avec autre corps est refusée |
| S1-A5 | Audit et outbox sont persistés avec une mutation de test ; un worker interrompu reprend sans dupliquer l'effet interne |
| S1-A6 | Pipeline CI opérationnel ; validation complète du document OpenAPI sans erreur ; désinstallation par défaut préservant les données de test ; Plugin Check et NFR-17/18 vérifiés |

### Tests avant passage

- **Unitaires :** validation DTO, enveloppes d'erreurs, empreintes d'idempotence, comparaison de versions et matrice des capacités.
- **Intégration WordPress/base :** activation répétée, migration interrompue/reprise, refus d'accès et transaction mutation/audit/outbox.
- **Contrats :** CT-06, CT-15 et CT-16 sur le socle, puis nouvelle exécution sur les routes métier lors de leur livraison.
- **Manuels :** installer/activer/désactiver ; naviguer au clavier ; tester les comptes administrateur, gestionnaire et formateur ; vérifier l'absence de perturbation des autres écrans WordPress.

**Passage :** tous les S1-A sont satisfaits et les primitives techniques ont des preuves automatisées. Aucun paiement ou réservation réelle n'est attendu à ce stade.

## Sprint 2 — Planning et disponibilités fiables

**Objectif :** garantir qu'une formation n'utilise que des ressources réellement disponibles.

### Travaux

- Développer catalogue et réglages des offres : catégories, descriptifs, prérequis, objectifs, tarifs, taxes simples, règles de réservation et champs personnalisés simples.
- Créer les écrans et API de formateurs, lieux, salles, horaires hebdomadaires, congés et exceptions.
- Créer sessions collectives et séquences multi-dates avec plusieurs intervenants, capacité totale et quota présentiel.
- Développer publication, fermeture, annulation de sessions sans inscriptions et archivage contrôlé.
- Développer le calcul des créneaux individuels : fuseaux, buffers, horizon, délai minimal et pas de réservation.
- Implémenter lignes de garde des ressources, allocation atomique et vérification des conflits ; ajouter l'allocation de places comme service interne testable.
- Créer calendrier administratif jour/semaine/mois, filtres et fiches de session ; pages publiques du catalogue et insertion Gutenberg/shortcodes en lecture.

### Critères d'acceptation

| ID | Critère |
|---|---|
| S2-A1 | Créer une formation et une session collective de deux jours, avec deux intervenants et une salle, sans manipulation directe en base |
| S2-A2 | Publier la session bloque les ressources même sans inscrit ; un brouillon ne les bloque pas ; une publication partiellement conflictuelle ne bloque rien |
| S2-A3 | Aucun cours individuel ne peut utiliser les intervalles déjà occupés, buffers inclus |
| S2-A4 | Les exceptions et fuseaux sont appliqués correctement ; un congé recouvrant une session est refusé avec une explication |
| S2-A5 | Capacités totale/présentielle cohérentes avec la salle ; archivage interdit s'il masque des engagements futurs |
| S2-A6 | Le catalogue est consultable via page, bloc et shortcode ; aucun participant, secret ou lien privé de réunion n'y apparaît |
| S2-A7 | Les mutations simultanées du planning ne produisent ni chevauchement ni allocation partielle |

### Tests avant passage

- **Unitaires :** intervalles semi-ouverts, buffers, priorités des exceptions, conversions de fuseaux, changements d'heure, clôture et horizon ; calculs tarifaires et arrondis avec valeurs de référence.
- **Intégration concurrente :** deux publications sur la même salle ; deux allocations individuelles sur une ressource vide ; congé concurrent à publication ; rollback d'une session dont une seule séquence échoue.
- **Contrats :** CT-02, CT-04, CT-23 et CT-26 sur les services disponibles. CT-23 est repris via les parcours publics au sprint 3.
- **Manuels :** créer deux journées avec pauses ; vérifier les vues du calendrier ; ajouter un jour de congé ; vérifier que les cours individuels disparaissent sur les périodes occupées ; naviguer dans le catalogue sur mobile.

**Passage :** tous les S2-A sont satisfaits et les tests de concurrence utilisent la vraie base. Le moteur peut alors être exposé au parcours d'inscription.

## Sprint 3 — Inscriptions individuelles et collectives

**Objectif :** permettre à un acheteur d'obtenir et de gérer une réservation complète, avant activation des cartes bancaires.

### Travaux

- Développer les formulaires publics individuels/collectifs, champs requis et récapitulatif ; brancher blocs et shortcodes sur ces parcours.
- Implémenter devis serveur, versions de conditions, snapshots tarifaires et correspondance exacte entre participants du devis et de la commande.
- Créer acheteur, participants distincts, commande, inscriptions et occupations dans une transaction.
- Livrer gratuité, attente de virement et confirmation manuelle autorisée ; les écritures d'encaissement et remboursements sont livrées au sprint 4.
- Implémenter expiration logique des blocages et protections anti-abus.
- Livrer accès acheteur WordPress/invité, cookie checkout limité, récupération après perte de réponse et protection contre le rapprochement abusif par email.
- Livrer fiche client, notes internes, inscription par téléphone et espace client.
- Implémenter annulations sans fonds encaissés et déplacement individuel atomique ; enregistrer les événements nécessaires aux futurs traitements financiers.
- Brancher les emails minimaux d'accès, attente, confirmation et annulation sur l'outbox. Les convocations/rappels et outils opérateur seront complétés au sprint 5.

### Critères d'acceptation

| ID | Critère |
|---|---|
| S3-A1 | Un particulier réserve un cours gratuit ou en paiement manuel et retrouve son inscription |
| S3-A2 | Un acheteur inscrit cinq personnes à une session de deux jours : une commande, cinq inscriptions, liens vers toutes les séquences |
| S3-A3 | Si quatre places restent, demander cinq places ne crée aucune inscription ni occupation partielle |
| S3-A4 | Deux acheteurs sur la dernière place produisent une seule attribution ; une attente expirée cesse de bloquer même sans cron |
| S3-A5 | Un email partagé entre participants ne donne aucun accès à l'historique d'un autre acheteur ; un formateur ne voit que son périmètre |
| S3-A6 | Inscription manuelle et création client s'effectuent dans un seul parcours ; l'espace client permet l'annulation autorisée et le déplacement individuel |
| S3-A7 | Un déplacement échoué laisse l'ancien rendez-vous intact ; la répétition d'une commande ne crée pas de doublon |
| S3-A8 | Le paiement carte indisponible n'est pas présenté comme fonctionnel ; aucune confirmation gratuite ou manuelle ne crée de faux encaissement |

### Tests avant passage

- **Unitaires :** états inscription, expiration, droits de propriété, consentement et version devis, validation des champs et quotas.
- **Intégration :** gratuité sans appel Stripe ; publication/annulation concurrente à une inscription ; atomicité groupe ; expiration avec horloge contrôlée ; liens expirés/révoqués/usagés ; réponse checkout perdue.
- **Contrats :** CT-01, CT-03, CT-05, CT-06, CT-14, CT-15, CT-16, CT-17, CT-22, CT-23, CT-25 et CT-27 ; compléter les variantes financières au sprint 4.
- **Manuels :** exécuter P1 sans carte et P2 du PRD ; créer un groupe par téléphone ; utiliser deux navigateurs avec deux acheteurs ; demander un nouveau lien ; tester erreurs de formulaire, navigation clavier et mobile.

**Passage :** tous les S3-A sont satisfaits. Les parcours d'inscription sont démontrables en test, sans les présenter comme une V1 commercialisable complète.

## Sprint 4 — Cohérence des paiements et remboursements

**Objectif :** garantir que chaque mouvement d'argent correspond à un état de réservation explicable et récupérable.

### Travaux

- Figer le provisioning Stripe, la version fournisseur et les politiques commerciales/fiscales nécessaires ; utiliser exclusivement le mode test pour la recette.
- Livrer Stripe intégral/acompte, authentification complémentaire du paiement si nécessaire, solde, lien sécurisé et paiement manuel constaté.
- Implémenter allocations financières par ligne, taxe/arrondi définitifs, ajustements de dette, remboursements Stripe et enregistrements de remboursements manuels.
- Livrer inbox fournisseur, validation signature/compte/devise/live-test, déduplication, rapprochement et états incertains.
- Compléter annulations partielles et annulation de session par lots avec compensation financière.
- Livrer liste des paiements, soldes, remboursements et exceptions, avec opérations limitées aux capacités financières.
- Brancher confirmations de paiement et notifications financières sur les événements durables.

### Critères d'acceptation

| ID | Critère |
|---|---|
| S4-A1 | Le montant du paiement vient du serveur ; la page de retour seule ne confirme jamais une réservation |
| S4-A2 | Acompte puis solde donnent les montants exacts, même après fermeture du navigateur ou notification retardée |
| S4-A3 | Répétitions de webhook et demandes concurrentes ne doublent ni encaissement ni inscription |
| S4-A4 | Un paiement tardif après annulation ne réactive pas la place ; l'encaissement est tracé et le remboursement dû apparaît |
| S4-A5 | Cinq places à 100 €, acompte de 20 € chacune, annulation sans frais d'une place : dette des quatre places = 320 € et remboursement dû = 20 € |
| S4-A6 | Deux remboursements concurrents respectent ensemble le plafond par paiement et par ligne ; un timeout conserve la réserve |
| S4-A7 | Une annulation de session interrompue reprend sans double ajustement ; aucun solde de cette session ne peut être payé pendant le traitement |
| S4-A8 | Une opération incertaine est rapprochée avant une nouvelle tentative ; les erreurs et anomalies sont visibles et assignables à un opérateur autorisé |

### Tests avant passage

- **Unitaires :** calcul par ligne, ventilation des acomptes, arrondis, plafonds, transitions financières et règles d'annulation.
- **Intégration avec simulateur :** succès/échec/timeout ; événement reçu plusieurs fois ou dans le désordre ; interruption après encaissement ; concurrence paiement/annulation ; annulation collective reprise.
- **Contrats :** CT-07 à CT-13, CT-18, CT-20 et CT-21 ; exécuter à nouveau CT-06, CT-12 et CT-27 avec les vrais adaptateurs test.
- **Manuels en environnement Stripe test :** payer intégralement, payer un acompte puis le solde, refuser une carte, fermer le navigateur, rembourser partiellement, enregistrer un virement et tenter un remboursement avec un compte sans capacité dédiée.

**Passage :** tous les S4-A sont satisfaits, aucun écart comptable inexpliqué dans la recette. Les tests Stripe seuls ne remplacent pas les scénarios de panne déterministes du simulateur.

## Sprint 5 — Exploitation quotidienne et livraison V1

**Objectif :** rendre le produit exploitable par les formateurs pilotes dans les conditions de sortie du PRD.

### Travaux

- Compléter convocations et rappels par séquence, journal d'envoi, suppression des rappels obsolètes, relances et diagnostic des tâches en retard.
- Livrer liste des participants et présence par séquence, avec accès formateur limité aux affectations.
- Livrer exports CSV, fichiers temporaires protégés, contrôle des droits au téléchargement et neutralisation des formules.
- Finaliser tableau de bord, filtres, liens vers les listes, états vides et messages d'erreur utiles.
- Finaliser confidentialité WordPress, conservation/anonymisation, procédure de désinstallation et documentation opérateur.
- Exécuter tests de migration avec vraies données métier de test, restauration coordonnée, compatibilité, charge et accessibilité.
- Réaliser les essais pilotes, corriger les blocages et préparer le paquet installable versionné.
- Documenter configuration, sauvegarde, mise à jour, cron serveur, incidents de paiement et processus externe de facturation V1.

### Critères d'acceptation

| ID | Critère |
|---|---|
| S5-A1 | Une session de deux séquences avec cinq participants permet dix présences indépendantes ; un formateur non affecté est refusé |
| S5-A2 | Emails attendus tracés, échecs relançables ; aucun rappel ancien n'est soumis après invalidation connue ; les limites de livraison email sont explicites |
| S5-A3 | Exports complets dans la portée autorisée ; perte de droits après génération interdit le téléchargement ; aucun fichier privé publiquement accessible |
| S5-A4 | Tableau de bord cohérent avec inscriptions et opérations persistées ; les totaux ne masquent pas les remboursements en cours |
| S5-A5 | Mise à jour sur installation peuplée et restauration testées sans perte de relations, montants ou présences |
| S5-A6 | Parcours clés terminables au clavier et dès 360 px ; tests d'accessibilité et matrice WordPress/PHP/thèmes documentés |
| S5-A7 | Objectif performance NFR-08 mesuré sur l'environnement de référence ; pas de ralentissement bloquant sur les opérations de réservation |
| S5-A8 | Au moins trois formateurs pilotes et dix acheteurs testent les parcours prévus ; au moins huit acheteurs sur dix réussissent chacun P1 et P2 sans assistance |
| S5-A9 | Tous les critères de sortie V1 du PRD sont remplis ; paquet installable, documentation et fiche de recette disponibles |

### Tests avant livraison

- **Unitaires :** projections par rôle, calculs du tableau de bord, CSV, sélection des destinataires, présence et invalidation des rappels.
- **Intégration :** CT-19, CT-24 et CT-28 ; reprise worker, export après révocation, purge des fichiers, tâches sur site sans trafic, anonymisation sans corruption financière.
- **Régression finale :** tous les CT-01 à CT-28 et REC-01 à REC-30 ; aucune réussite antérieure n'est présumée suffisante après intégration finale.
- **Manuels :** journée complète d'exploitation — préparer une formation, inscrire, encaisser, convoquer, pointer, annuler et exporter ; tests lecteurs d'écran et navigation mobile ; mise à jour et restauration suivies par la procédure.
- **Charge :** recherche sur 30 jours, 50 intervenants, 10 000 réservations, 20 recherches concurrentes pendant cinq minutes après préchauffage ; consigner p95, erreurs, CPU/RAM et configuration.

**Sortie :** V1 livrable lorsque tous les S5-A et critères communs passent. Le déploiement public n'est pas inclus automatiquement dans la validation du sprint.

## Matrice de couverture V1

| Exigences PRD | Sprint principal | Compléments |
|---|---|---|
| WP-02, socle NFR-01/02/05/10/11/12/14 | 1 | Contrôles objets et preuves enrichis à chaque sprint |
| CAT-01/02, DIS-01/02, IND-01, COL-01, RES-01, ADM-01 | 2 | Réservation réelle et concurrence complète au sprint 3 |
| TAR-01 | 2 | Snapshot au sprint 3, encaissement/arrondi final au sprint 4 |
| WP-01 | 2 | Formulaires interactifs au sprint 3 |
| INS-01/02/03, CLI-01, ADM-02 | 3 | Scénarios financiers au sprint 4 |
| CAN-01/02 | 3 | Remboursements et annulation collective compensée au sprint 4 |
| PAY-02 | 3 | Constatation d'encaissement et corrections au sprint 4 |
| PAY-01/03, REF-01 | 4 | Régression générale au sprint 5 |
| NOT-01 | 3 | Emails financiers au sprint 4 ; convocations/rappels/journal au sprint 5 |
| PRE-01, EXP-01, TAB-01 | 5 | S'appuient sur modèles et permissions des premiers sprints |
| NFR-03/15/16 | 3 | Contrôles checkout/paiement au sprint 4, export au sprint 5 |
| NFR-04/06/07/08/09/13 | Transversal, clôture 5 | Accessibilité dès les premiers écrans ; mesures finales au sprint 5 |

## Préparation des tickets et preuves

Pour chaque ticket : objectif observable, IDs PRD/contrat, dépendances, routes/schémas concernés, stratégie de transaction, critères d'acceptation et tests. Un ticket n'est terminé qu'avec code, migrations éventuelles, tests et démonstration de son comportement.

Les choix opérationnels encore ouverts dans les documents précédents sont traités comme tâches de cadrage du sprint concerné : base technique au sprint 1 ; horaires et ressources au sprint 2 ; accès/quotas au sprint 3 ; paramètres financiers et provisioning au sprint 4 ; politique de conservation et processus d'exploitation finalisés avant la sortie du sprint 5. Aucun choix n'est laissé à une valeur implicite en production.

Ce fichier est un plan de réalisation : les tests listés sont à effectuer pendant les sprints, pas des résultats déjà obtenus.
