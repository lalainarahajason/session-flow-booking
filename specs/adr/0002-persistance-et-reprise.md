# ADR 0002 — Persistance et reprise

Cinq tables techniques, version de schéma 0001 distincte du plugin 0.1.0.
dbDelta + vérification physique des colonnes/index/moteur ; verrou de connexion GET_LOCK,
libéré automatiquement après crash. Checkpoint conservé en cas d'échec, étapes répétables.
Le DDL n'est pas supposé transactionnel ; restaurer code et base ensemble après sauvegarde.

Idempotence, mutation, audit et outbox partagent la transaction. Une intention abandonnée avant
commit disparaît au rollback de connexion ; aucune suppression de clé complétée pour réessayer.
Les jobs ont un bail et un jeton ; effet interne et acquittement sont atomiques sous verrou.
Aucun appel externe sous transaction, aucun transient de permission ou verrouillage.
La colonne logique cursor est stockée sous job_cursor pour éviter un mot réservé SQL.
