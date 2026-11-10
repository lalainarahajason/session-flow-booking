---
name: db-migration
description: Encadrer toute création ou modification de table, index, données persistées, option de version, activation ou désinstallation du plugin avec dbDelta, migrations versionnées et tests de conservation.
---

# Migrations et désinstallation WordPress

Lire le modèle validé et architecture-guard. Examiner les migrations déjà livrées avant toute modification ; ne pas réécrire leur historique.

## Contrat dbDelta

- Charger `ABSPATH . 'wp-admin/includes/upgrade.php'` avant `dbDelta()`.
- Construire les tables avec le préfixe WordPress du site et le suffixe constant du plugin ; utiliser `$wpdb->get_charset_collate()`. Aucun nom de table fourni par le client.
- Pour le DDL géré par dbDelta : une colonne par ligne, mots-clés SQL en majuscules, types en minuscules et dimensions requises explicites ; pas de quotes/backticks autour des noms de colonnes ; `PRIMARY KEY  (id)` avec deux espaces ; clés nommées déclarées avec `KEY nom (colonne)`, pas le synonyme INDEX. Vérifier la compatibilité réelle sur les versions supportées.
- Utiliser dbDelta pour les créations/évolutions qu'il sait gérer. Ne pas supposer qu'il effectue correctement suppressions, renommages, migrations de données ou clés étrangères : utiliser des étapes explicites, contrôlées et testées pour ces opérations, sans supprimer les invariants du modèle.
- Vérifier le schéma obtenu (colonnes, index, contraintes) et les erreurs SQL après exécution. Le retour de dbDelta seul ne suffit pas à attester la migration.

## Versionnage et reprise

1. Séparer version du plugin et version du schéma. Stocker la version de BDD dans une option préfixée du projet, cohérente avec le périmètre site.
2. Définir une séquence de migrations ordonnées avec préconditions, postconditions et checkpoints ; rendre chaque étape répétable ou détectable comme déjà appliquée.
3. Déclencher l'installation à l'activation et détecter les mises à niveau aussi hors activation. Ne pas appeler dbDelta à chaque page : comparer les versions, prendre un verrou durable et exécuter uniquement les étapes nécessaires.
4. Ne publier la nouvelle version de BDD qu'après vérification de toutes ses postconditions. Une erreur conserve l'état/checkpoint utile et bloque les écritures incompatibles avec un schéma partiel.
5. Ne pas compter sur un rollback transactionnel pour annuler tout le DDL. Prévoir sauvegarde/restauration, stratégie expand/migrate/contract lorsque nécessaire et reprise après interruption.
6. Effectuer les backfills par lots bornés, conserver IDs et relations, vérifier doublons, orphelins, index et sommes financières avant finalisation.
7. Appliquer audit-wp à toutes les requêtes : `$wpdb->prepare()` pour les valeurs dynamiques, noms structurels internes autorisés, droits et portée par site.
8. Ne pas revendiquer le multisite avant implémentation et recette de son périmètre ; éviter toute migration ou suppression accidentelle des tables d'un autre site.

## uninstall.php obligatoire

- Placer `uninstall.php` à la racine du plugin et sortir immédiatement si `WP_UNINSTALL_PLUGIN` n'est pas défini. Ne pas dépendre du bootstrap normal du plugin pour fonctionner après désactivation.
- Ne jamais supprimer les données métier à la désactivation. Nettoyer les tâches planifiées et ressources temporaires propres au plugin sans effacer réservations ou paiements.
- À la désinstallation, conserver les données métier par défaut conformément au PRD. La suppression complète exige une option explicite enregistrée auparavant par un administrateur autorisé, avec protection CSRF ; ne pas chercher un nonce de formulaire inexistant dans uninstall.php.
- Énumérer précisément les tables, options, rôles/capacités propres au plugin, tâches, transients et fichiers à nettoyer selon le mode choisi. Ne jamais supprimer des utilisateurs WordPress ou lancer un DROP générique sur un préfixe partagé.
- Conserver ce qui est nécessaire à une réinstallation cohérente lorsque les données sont conservées, notamment la version de schéma. Ne pas détruire les données puis les seuls indicateurs qui permettraient de diagnostiquer un échec partiel.
- Nettoyer les Transients par leur API/registre de clés ; ne pas supposer qu'ils résident en base si un cache objet est actif. Restreindre les fichiers à des chemins appartenant au plugin et contrôlés.

## Preuves exigées avant validation

Écrire et exécuter via test-runner : installation vierge, seconde exécution sans changement, upgrade d'une base peuplée, interruption et reprise, échec sans incrément de version, contraintes et totaux conservés ; désactivation conservant les données ; uninstall avec conservation et avec suppression explicite ; accès direct à uninstall sans effet.

Faire ces essais sur une base de test isolée, jamais sur la base réelle de l'utilisateur. Vérifier aussi les objets étrangers au plugin : ils doivent rester intacts. Fournir schéma avant/après, commande, résultat et procédure de récupération. Une migration non testée sur vraie base reste non validée.

Références : https://developer.wordpress.org/plugins/creating-tables-with-plugins/ et https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
