# session-flow-bookings — Modèle de données logique V1

Version 0.1.1 — proposition. Références : [contrats système](contrats-systeme.md), [invariants](invariants.md), [OpenAPI](openapi.json).

## 1. Conventions de stockage

Le schéma logique est indépendant des classes PHP. Proposition physique : tables dédiées transactionnelles avec clés étrangères et index ; noms préfixés par le préfixe du site WordPress. Moteur et versions exactes à figer avant génération des migrations. Aucun DDL présenté ici n'est déjà installé.

Tous les objets métier ont `id UUID`, `site_id`, `created_at UTC`, `updated_at UTC`, `version BIGINT >= 1`. Tables append-only : pas de modification du fait enregistré, ajout d'une correction séparée. Relations intersites interdites par clé étrangère composite `(site_id, id)` ou contraintes équivalentes testées ; le site provient du contexte serveur, jamais du client.

- `money` : entier signé 64 bits en unité mineure, bornes API plus restrictives ; `currency CHAR(3)` et exposant de devise figés dans le snapshot.
- `instant` : UTC à précision seconde ; `timezone` : nom IANA, conservé séparément. Horaires hebdomadaires : heure locale, pas UTC réutilisé toute l'année.
- `json_snapshot` : structure validée et versionnée ; jamais un sac de paramètres arbitraires substituant les relations critiques.
- Pas de suppression en cascade sur commandes, mouvements ou présences. Archivage pour le métier ; anonymisation contrôlée des données personnelles selon la politique validée.
- UUID API distinct des IDs numériques WordPress. `wp_user_id` et `wp_post_id` facultatifs, liés par service applicatif et contrôlés à chaque accès ; une suppression WordPress ne doit pas effacer l'historique financier.

## 2. Catalogue, organisation et planning

| Table logique | Champs métier essentiels | Contraintes et index |
|---|---|---|
| training | kind individual/collective, state draft/published/archived, slug, title, contenus, media_id?, config_version, price/rules/form snapshots, duration_minutes?, buffers, timezone, venue_id?, room_id?, delivery | UNIQUE(site,slug) ; index(state,kind,id). kind figé après publication |
| category / training_category | name, slug / training_id, category_id | UNIQUE(site,slug) ; UNIQUE(training,category) |
| trainer | display_name, wp_user_id?, timezone, archived | UNIQUE(site,wp_user_id) pour références non nulles ; index(archived,id) |
| training_trainer | training_id, trainer_id | UNIQUE(training,trainer), équipe fixe individuelle |
| venue | name, address, timezone, archived | index(site,id) |
| room | venue_id, name, capacity, archived | FK venue ; capacity >= 1 |
| schedule | trainer_id, timezone, version | UNIQUE(site,trainer_id) ; créée vide version 1 avec le formateur |
| weekly_interval | schedule_id, weekday, start_local, end_local | weekday 1–7 ; start<end ; non-chevauchement par jour contrôlé sous verrou planning |
| day_exception | schedule_id, local_date | UNIQUE(schedule_id,local_date) |
| exception_interval | exception_id, start_local, end_local | zéro intervalle = jour fermé ; non-chevauchement |
| session | training_id, kind_snapshot, state, capacity_total, capacity_onsite, delivery, venue_id?, registration_closes_at, price_snapshot, policy_snapshot, cancellation_job_id? | index(training_id,state,registration_closes_at,id) ; capacity_onsite <= capacity_total |
| sequence | session_id, start_at, end_at, timezone, room_id?, buffers, meeting_url_encrypted?, schedule_revision | start<end ; index(session_id,start_at,id) ; la réunion n'est jamais dans une projection publique |
| assignment | sequence_id, trainer_id | UNIQUE(sequence_id,trainer_id) |
| resource_guard | resource_type trainer/room, resource_id | UNIQUE(site,resource_type,resource_id), ligne de sérialisation créée avec la ressource |
| resource_occupation | resource_type, resource_id, sequence_id, effective_start, effective_end, expires_at?, state active/released | UNIQUE(resource_type,resource_id,sequence_id) ; index(resource_type,resource_id,effective_start,effective_end) ; intervalle étendu des buffers |

Une occupation est attachée à une séquence, pas à chaque participant. La session collective publiée l'occupe même vide. En individuel, la première réservation crée session, séquence et occupation temporaire dans la même transaction.

Le modèle expose un lieu global de session mais permet une salle différente par séquence, dans ce lieu en V1. L'URL réunion peut être copiée depuis la configuration session ; sa révision est contrôlée et sa modification ne change pas les dates. En distanciel : salle null. En présentiel/hybride : salle exigée pour appliquer les garanties de capacité de salle de V1 ; une configuration sans salle ne peut pas publier.

## 3. Acheteurs, participants et inscriptions

| Table logique | Champs métier essentiels | Contraintes et index |
|---|---|---|
| buyer | contact, verified_wp_user_id?, verified_email_at?, archived | email indexé mais NON unique ; rapprochement explicite après preuve, aucun merge automatique |
| participant | buyer_scope_id, name, email? | identité limitée au périmètre acheteur ; email NON unique |
| quote | selection_snapshot, config_revision, lines_snapshot, terms_version, terms_text, payment_choice, expires_at, consumed_order_id? | unique consumed_order_id ; consommation au plus une fois ; aucune occupation de capacité |
| order | buyer_id, session_id, state, currency, currency_exponent, quote_id, terms_snapshot, form_snapshot, payment_choice, expires_at?, balance_due_at?, version | UNIQUE(site,quote_id), index(buyer_id,created_at,id), index(session_id,state,id) |
| enrollment | order_id, session_id, participant_id, status, mode, client_ref, expires_at?, answers_snapshot | UNIQUE(order_id,client_ref), UNIQUE(order_id,participant_id) ; FK session conforme à la commande |
| seat_allocation | enrollment_id, session_id, mode, state held/pending_manual/confirmed/released, expires_at? | UNIQUE(enrollment_id) ; index(session_id,state,expires_at,mode) |
| order_line | order_id, enrollment_id, original_net, original_tax, original_gross, deposit_minor, currency | UNIQUE(enrollment_id) ; original_gross = net+tax ; sommes initiales immuables |
| presence | enrollment_id, sequence_id, status, last_actor_id, updated_at, version | UNIQUE(enrollment_id,sequence_id) ; session inscription = session séquence |
| internal_note | buyer_id, text, actor_id, created_at | append-only ; accès gestionnaire uniquement |

`seat_allocation` est la source de vérité de capacité, une ligne par inscription ; `enrollment.status` doit rester cohérent dans la même transaction. Ne pas additionner allocations et inscriptions dans une requête de comptage. Une allocation held/pending consomme seulement si `expires_at > database_now`; confirmed consomme sans expiration.

Les projections de compte acheteur ne joignent que les participants des commandes autorisées. Aucun endpoint ne retourne la fiche globale d'une personne à partir d'une adresse email fournie par un tiers.

Création d'une commande : un participant déjà connu n'est réutilisé qu'après contrôle de propriété explicite. L'API publique actuelle reçoit des fiches nouvelles ; son `client_ref` est une clé de corrélation/idempotence, jamais une preuve d'identité. Une reprise de la même commande réutilise ses inscriptions, pas de nouvelles personnes.

## 4. Comptabilité opérationnelle

| Table logique | Champs métier essentiels | Contraintes et index |
|---|---|---|
| contract_adjustment | order_id, line_id, delta_minor, reason, actor_id, operation_id | append-only ; UNIQUE(operation_id,line_id) ; total contractuel ligne >= 0 |
| payment_operation | order_id, purpose initial/balance/manual, amount_minor, currency, state, provider, provider_account, provider_object_id?, operation_id | UNIQUE(site,operation_id), UNIQUE(provider_account,provider_object_id) si non null ; index(order_id,state) |
| payment_allocation | payment_id, line_id, amount_minor | UNIQUE(payment_id,line_id), somme = montant opération ; même commande/devise |
| refund_obligation | order_id, line_id, reason cancellation/commercial/external, amount_minor, source_operation_id | UNIQUE(source_operation_id,line_id), somme couverte par montants disponibles de la ligne |
| refund_operation | payment_id, order_id, amount_minor, state, operation_id, provider_object_id?, reference? | même devise que paiement ; UNIQUE(operation_id), UNIQUE(provider_object_id) contextualisé par compte |
| refund_allocation | refund_id, payment_allocation_id, line_id, amount_minor | UNIQUE(refund_id,payment_allocation_id) ; plafond par paiement ET ligne |
| manual_correction | original_operation_id, actor_id, reason, signed_amount, reference | append-only ; ne modifie pas silencieusement un encaissement déjà utilisé |
| financial_exception | order_id, operation_id, code, state open/processing/resolved, owner_role, resolution_operation_id? | UNIQUE(operation_id,code) ; index(state,created_at) |

### Calculs normatifs par ligne

Pour une ligne i :

- `T_i = original_gross_i + somme(adjustments_i)`.
- `P_i = somme(encaissements vérifiés alloués_i)` après contre-écritures justifiées.
- `R_i = somme(remboursements réussis_i)`.
- `N_i = P_i - R_i`.
- `D_i = max(T_i - N_i, 0)` : dette théorique de la ligne.
- `F_i = max(N_i - T_i, 0)` : trop-perçu de la ligne.
- `Q_i` : remboursements demandés/en cours/à vérifier, réservés mais pas encore réussis.
- `refundable_minor_i = max(F_i - Q_i, 0)` pour les remboursements dus. Un geste commercial exige d'abord un ajustement de T_i.
- `due_minor` commande = somme des D_i sur lignes actives et payables ; `refundable_minor` commande = somme des valeurs remboursables de chaque ligne. Une dette d'une ligne ne compense pas automatiquement le trop-perçu d'une autre.

Pour une ligne annulée, le solde ne peut plus être payé via checkout. Les frais d'annulation conservés ne dépassent pas les sommes déjà encaissées affectées à cette ligne ; pas de nouvelle créance de pénalité V1. Les liens de paiement ne calculent donc pas leur montant sur un simple total de commande.

Exemple de référence : cinq lignes de 100 €, acompte de 20 € chacune. Annulation sans frais de la cinquième : T=[100,100,100,100,0], P=[20,20,20,20,20]. Dette des lignes actives = 320 €, remboursement dû = 20 €, même avant exécution de ce remboursement. Après remboursement, dette toujours 320 €, remboursement restant 0 €. Le raccourci max(somme(T)-somme(N),0) donnerait à tort 300 € avant remboursement.

Un paiement du solde finance les D_i actifs dans l'ordre stable des lignes. Un acompte s'alloue proportionnellement aux montants bruts, avec arrondi à l'unité mineure ; résidu affecté à la dernière ligne non nulle. À parts égales le résultat est égal sauf le résidu. Taxe au taux en points de base : calcul rationnel, arrondi demi-supérieur par ligne ; prix HT -> taxe arrondie, prix TTC -> taxe incluse = gross - arrondi(gross/(1+taux)). Pas de calcul binaire flottant. Le snapshot conserve taux, méthode et montants afin qu'un changement de réglage ne réécrive pas le passé.

## 5. Infrastructure fiable

| Table logique | Champs / garanties |
|---|---|
| idempotency_record | site, principal_scope, operation_id, key, fingerprint, state, lease_until, result_reference, expires_at ; UNIQUE(scope,operation_id,key) |
| provider_inbox | provider, account, mode live/test, event_id, payload_protected, received_at, processed_at?, state ; UNIQUE(provider,account,mode,event_id) |
| domain_outbox | event_id, aggregate_type/id, aggregate_version, event_type, schema_version, payload_minimal, created_at, lease_until?, delivered_at? ; UNIQUE(aggregate_id,aggregate_version,event_type,event_discriminator) |
| notification | event_id, recipient_scope, template_version, sequence_revision?, scheduled_at, state, attempt_count, last_error_code ; UNIQUE(event_id,recipient_scope,kind) |
| job | kind, principal_scope, source_scope_snapshot, state, cursor, lease_until, completed_items, total_items, error_code?, file_ref?, expires_at? |
| access_secret | secret_hash, scope, order_ids/identity_ref, expires_at, consumed_at?, revoked_at? ; secret brut jamais persisté |
| browser_session | session_hash, principal_scope, csrf_hash, expires_at, revoked_at? ; aucune autorité déduite d'un email client |
| audit_event | request_id, operation_id, actor_type/id, action, subject_id, version_before/after, occurred_at ; append-only, valeurs sensibles exclues |

Les caches de compteurs et tableaux de bord ne font pas autorité. Ils sont reconstruisibles depuis allocations et opérations. Toute écriture métier produit son audit et son outbox dans sa transaction ; un transport email en panne ne bloque pas le commit.

### Rétention et migrations

- Les durées de conservation métier sont configurées après choix du marché ; aucune purge universelle de paiements n'est supposée acceptable.
- Secrets expirés, fichiers exportés et données anti-abus ont des politiques distinctes. Un audit technique ne conserve pas une copie illimitée des coordonnées.
- Export/effacement WordPress : vérifier identité, restrictions de conservation et relations ; anonymiser les attributs autorisés sans casser les montants, clés ou preuves nécessaires. Une demande client ne cascade pas vers les participants d'autres acheteurs.
- Migrations versionnées ; vérification des orphelins, doublons et totaux avant validation. Installer les lignes de garde pour toutes les ressources existantes avant d'activer les écritures concurrentes.
- Retour à une ancienne version de code interdit si le schéma n'est pas compatible ; procédure de restauration coordonnée code/base, pas simple downgrade d'un fichier PHP.

## 6. Contraintes à tester hors SQL

Une contrainte UNIQUE ne suffit pas à interdire deux intervalles temporels qui se chevauchent. Publication, inscription individuelle, déplacement, congés et changements de ressources doivent prendre les mêmes verrous décrits dans [invariants.md](invariants.md). Aucun endpoint, cron ou import ne peut écrire directement dans les tables en contournant ces commandes.

Les capacités, autorisations, plafonds financiers multi-lignes et transitions sont validés dans les services de domaine sous transaction. Les contraintes SQL constituent une seconde barrière, pas un remplacement.
