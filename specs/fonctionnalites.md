# session-flow-bookings — Fonctionnalités et feuille de route

Date : 9 novembre 2026

Statut : proposition de périmètre, à préciser avant réalisation.

## Objectif

Développer un plugin WordPress pour les formateurs et organismes de formation, couvrant les cours individuels et les formations collectives dans une administration commune.

Chaque version complète la précédente. Les versions sont des étapes de livraison, pas des formules tarifaires. Cette liste décrit les fonctionnalités prévues pour session-flow-bookings, et non celles déjà implémentées ou disponibles dans Amelia.

## Principes métier

- Distinguer la formation (programme, objectifs, prérequis et tarifs) de ses sessions (dates, intervenants, lieux et capacités).
- Pour un cours individuel, le client choisit un créneau disponible.
- Pour une formation collective, les participants s'inscrivent à une session dont les dates sont publiées.
- Partager les disponibilités entre les deux formats : un intervenant affecté à une session collective n'est pas disponible pour un cours individuel sur la même période.
- Distinguer l'acheteur, le payeur et les participants.
- Prévoir plusieurs intervenants pour une même session dès le modèle de données.

## V1 — Réserver, encaisser et gérer les formations

Objectif : permettre à un formateur de gérer ses premières réservations et inscriptions de bout en bout.

| Domaine | Fonctionnalités |
|---|---|
| Catalogue | Création de cours individuels et formations collectives ; catégories ; descriptions ; programmes ; prérequis ; objectifs ; images |
| Pages publiques | Une page par formation, avec URL propre, présentation et prochaines sessions |
| Cours individuels | Durée, tarif, disponibilités hebdomadaires, congés, exceptions et temps de préparation avant/après |
| Sessions collectives | Dates fixes, sessions sur plusieurs jours, capacité maximale et fermeture des inscriptions |
| Intervenants | Un ou plusieurs formateurs par session ; blocage de leurs disponibilités sur tous les formats |
| Lieux | Présentiel, distanciel ou hybride ; adresses et lien de visioconférence saisi manuellement |
| Salles | Affectation d'une salle et prévention des conflits de réservation |
| Réservation | Parcours adapté aux cours individuels et aux sessions collectives ; réservation sans compte obligatoire |
| Inscriptions groupées | Un acheteur peut inscrire plusieurs personnes ; fiche distincte pour chaque participant |
| Données participants | Coordonnées, informations utiles et questions personnalisées simples |
| Tarification | Prix par séance ou par participant ; devise du site ; configuration des taxes |
| Paiement | Stripe, paiement intégral ou acompte ; paiement manuel/virement avec validation administrative |
| Suivi financier | Montant dû, payé et restant ; lien pour régler le solde ; rapprochement entre paiement et inscription |
| Annulation | Règles et délais configurables ; annulation administrative ; remboursement intégral ou partiel via Stripe |
| Espace client | Accès aux réservations, dates, participants et paiements ; annulation ou déplacement selon les règles |
| Administration | Calendrier jour/semaine/mois ; filtres par formation, intervenant et lieu ; création manuelle de réservations |
| Fiche client | Historique des inscriptions, paiements et notes internes |
| Présences | Liste des participants et statut présent, absent ou excusé pour chaque date |
| Emails | Confirmation, convocation, rappel, annulation et confirmation de paiement |
| Export | Clients, inscriptions et listes de participants en CSV |
| WordPress | Blocs Gutenberg et shortcodes ; comptes WordPress réutilisés ; rôles administrateur, gestionnaire, formateur et client |
| Tableau de bord | Prochaines sessions, places restantes, inscriptions et paiements en attente |

### Exigences obligatoires dès la V1

- Prévention des doubles réservations.
- Gestion des fuseaux horaires.
- Protection des données et des accès.
- Traitement fiable des notifications de paiement.
- Journal des opérations.
- Interface mobile accessible.
- Mises à jour préservant les données.

## V2 — Fidéliser et automatiser

Objectif : réduire le travail manuel et faciliter les réservations régulières.

| Domaine | Fonctionnalités |
|---|---|
| Forfaits | Packs de cours individuels, crédits disponibles, expiration et restitution selon les règles d'annulation |
| Parcours mixtes | Une offre combinant séances individuelles et formations collectives |
| Récurrence | Cours individuels récurrents et génération de sessions collectives répétées |
| Reports | Déplacement d'une séance ou d'une session entière avec notification des participants |
| Listes d'attente | Inscription en attente, proposition automatique d'une place et délai pour l'accepter |
| Calendriers externes | Synchronisation Google Calendar et Outlook ; prise en compte des indisponibilités externes |
| Visioconférence | Création automatique des réunions Zoom et Google Meet |
| Formulaires avancés | Questions conditionnelles, pièces jointes, champs obligatoires selon la formation |
| Promotions | Codes promotionnels, tarifs anticipés et réductions selon le nombre de participants |
| Paiements | PayPal, échéanciers et relances de solde |
| Facturation | Factures, avoirs et numérotation ; adaptation aux exigences du marché ciblé |
| Communications | Modèles personnalisables, rappels multiples, journal d'envoi et relance des échecs |
| Évaluations | Questionnaire de satisfaction après formation et synthèse des réponses |
| Personnalisation | Couleurs, typographies, textes et modèles de pages |
| Langues | Traduction du catalogue, des formulaires et des communications |
| Migration | Assistant d'import clients, formations et réservations ; premier connecteur Amelia |
| Diagnostic | État des connexions, erreurs de synchronisation et outils de réparation |

## V3 — Gérer un organisme de formation

Objectif : couvrir les besoins des équipes et des clients professionnels.

| Domaine | Fonctionnalités |
|---|---|
| Entreprises clientes | Fiche entreprise, contacts, acheteurs et salariés participants |
| Espace entreprise | Un responsable inscrit ses collaborateurs et suit leurs inscriptions |
| Formations privées | Sessions réservées à une entreprise ou accessibles sur invitation |
| Devis | Demande de devis, proposition commerciale et conversion en inscription |
| Documents | Modèles de convocations, conventions, contrats et attestations de participation |
| Émargement | Feuilles de présence par demi-journée ou séquence ; collecte de signatures |
| Suivi pédagogique | Prérequis, objectifs, évaluations avant/après et progression des participants |
| Documents participants | Dépôt et consultation de supports, exercices et justificatifs |
| Organisation | Plusieurs établissements, salles, équipements et équipes |
| Permissions avancées | Accès limité par établissement, équipe ou périmètre de formations |
| Intervenants externes | Affectations, disponibilités et suivi de leur rémunération |
| Facturation entreprise | Un payeur pour plusieurs participants ; regroupement des inscriptions sur une facture |
| Reporting | Taux de remplissage, présence, annulation, chiffre d'affaires encaissé et soldes dus |
| Qualité | Historique des évaluations, réclamations et actions d'amélioration |
| Traçabilité | Historique détaillé des modifications et exports documentaires |

Les fonctions documentaires et qualité seront adaptées au pays ciblé. Leur présence ne constituera pas, à elle seule, une garantie de conformité ou de certification.

## V4 — Connecter le plugin à l'écosystème métier

Objectif : permettre aux organismes et aux développeurs d'intégrer le plugin à leurs outils.

| Domaine | Fonctionnalités |
|---|---|
| API | API REST documentée avec permissions et clés à portée limitée |
| Webhooks | Événements d'inscription, paiement, annulation, présence et fin de formation |
| Automatisation | Connexions Make, Zapier et n8n |
| WooCommerce | Achat de formations avec les produits d'une boutique ; synchronisation des commandes |
| LMS | Connexion à un premier LMS choisi selon la demande, puis extension à d'autres |
| CRM | Synchronisation des contacts, entreprises et inscriptions |
| Comptabilité | Exports comptables et connexion à une première solution cible |
| Messagerie | SMS et WhatsApp avec consentement et suivi des coûts |
| Marketing | Mesure des conversions et origine des inscriptions, selon les choix de consentement |
| Constructeurs de pages | Widgets Elementor et Divi |
| Développeurs | Hooks, filtres, composants extensibles, documentation et exemples |
| Migration avancée | Connecteurs supplémentaires et rapports de contrôle des imports |
| Multisite | Compatibilité WordPress Multisite et administration adaptée |

## V5 — Mobilité et exploitation avancée

Objectif : faciliter le travail sur le terrain et la gestion à plus grande échelle.

| Domaine | Fonctionnalités |
|---|---|
| Application mobile | Planning, participants, présences et notifications pour les formateurs |
| Accueil | Pointage par QR code et recherche rapide d'un participant |
| Mode hors connexion | Consultation des listes et saisie des présences, avec synchronisation ultérieure |
| Abonnements | Accès récurrent à un quota de cours ou de formations |
| Portefeuille de crédits | Crédits partagés entre plusieurs offres ou collaborateurs d'une entreprise |
| Réseau d'établissements | Supervision consolidée de plusieurs sites ou organismes |
| Marque blanche | Personnalisation avancée pour les agences et réseaux |
| Analyse avancée | Rentabilité par formation, occupation des ressources et comparaison des périodes |
| Assistance intelligente | Aide à la rédaction des fiches et suggestions de planning, soumises à validation humaine |

## Ordre de développement recommandé pour la V1

1. Modèle de données : formations, sessions, intervenants, participants et réservations.
2. Disponibilités communes et prévention des conflits.
3. Administration et création manuelle des inscriptions.
4. Parcours public individuel et collectif.
5. Paiements, annulations et remboursements.
6. Emails, espace client et suivi des présences.
7. Tests avec des formateurs pilotes, puis stabilisation.

## Scénarios de référence

- Un particulier réserve et paie un cours individuel sur un créneau disponible.
- Une entreprise inscrit plusieurs salariés à une formation collective sur deux jours.
- Un formateur affecté à une session collective ne peut pas être réservé simultanément pour un cours individuel.

La priorité est de livrer une V1 cohérente et fiable, puis de choisir les ajouts suivants selon les usages des premiers clients.
