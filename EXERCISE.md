# Exercice technique — Réservation d'inventaire

## Durée

Cet exercice est conçu pour être réalisé en un **maximum de deux heures**.

Nous vous demandons de respecter cette limite. Nous évaluons principalement votre capacité à prioriser, à prendre de bonnes décisions techniques et à produire une solution cohérente dans un temps limité.

Il est acceptable de documenter certains éléments que vous n'avez pas eu le temps de terminer.

## Contexte

Une entreprise gère des commandes clients et possède de l'inventaire dans plusieurs entrepôts.

Chaque ligne de commande contient :

- un produit;
- une quantité demandée;
- une quantité déjà réservée.

L'utilisateur doit pouvoir réserver une quantité d'inventaire pour une ligne de commande dans un entrepôt donné.

## Projet fourni

Un projet Laravel et Vue fonctionnel vous est fourni. Consultez le [README](README.md) pour l'installation.

Il comprend notamment :

- Laravel;
- Vue 3 et TypeScript;
- Tailwind CSS;
- MySQL;
- une authentification minimale;
- les principales tables et données de démonstration;
- une page permettant de consulter une commande;
- une page permettant de consulter et d'ajuster l'inventaire;
- les outils de tests et de formatage.

Les entités suivantes existent déjà :

- produits;
- entrepôts;
- le stock physique par produit et entrepôt (`inventory_balances`), accompagné d'un journal complet des mouvements (`inventory_movements`);
- commandes;
- lignes de commande.

Vous êtes responsable de concevoir la structure nécessaire pour gérer les réservations.

## Feature demandée

À partir de la page d'une commande, l'utilisateur doit pouvoir sélectionner une ligne de commande et réserver une quantité dans un entrepôt.

L'interface doit lui permettre de voir :

- la quantité demandée;
- la quantité déjà réservée;
- la quantité restant à réserver;
- la quantité disponible dans chaque entrepôt.

Après une réservation réussie, les données affichées doivent être mises à jour.

`inventory_balances.quantity` représente le stock physique. Une réservation ne modifie ni cette balance ni le journal des mouvements. La quantité disponible correspond au stock physique moins la somme des réservations existantes pour le produit et l'entrepôt.

Une réservation n'a pas de statut dans le cadre de cet exercice : elle existe ou elle n'existe pas.

## Règles fonctionnelles

- La quantité réservée doit être supérieure à zéro.
- La quantité réservée ne peut pas dépasser la quantité restant à réserver sur la ligne.
- La quantité réservée ne peut pas dépasser l'inventaire disponible dans l'entrepôt.
- La somme des réservations ne peut jamais dépasser l'inventaire physique disponible.
- Deux requêtes exécutées simultanément ne doivent pas permettre une surréservation.
- Une erreur métier compréhensible doit être retournée lorsque la réservation est impossible.

Vous pouvez documenter vos hypothèses lorsqu'un comportement n'est pas précisé.

## Backend

Vous devez notamment :

- concevoir la structure de données des réservations;
- ajouter les migrations nécessaires;
- créer l'API permettant d'effectuer une réservation;
- assurer l'intégrité transactionnelle;
- prendre en compte les accès concurrents;
- retourner les informations nécessaires à l'interface.

## Frontend

Vous devez ajouter une interaction permettant :

- de sélectionner un entrepôt;
- de saisir une quantité;
- de soumettre la réservation;
- de voir un état de chargement;
- de comprendre une erreur;
- de voir les quantités mises à jour après l'opération.

Nous évaluons davantage la clarté et la facilité d'utilisation que la complexité visuelle.

## Tests

Ajoutez les tests que vous jugez prioritaires.

Nous nous attendons minimalement à voir des tests couvrant :

- une réservation valide;
- une réservation dépassant l'inventaire disponible;
- une réservation dépassant la quantité demandée.

**Concurrence** : aucun test automatisé ne vous est fourni pour le scénario de requêtes simultanées. Ce requis doit néanmoins être respecté. Il sera évalué manuellement lors de la revue de la solution et discuté pendant la rencontre de suivi.

## Performance

Le jeu de données fourni contient plusieurs lignes de commande et entrepôts.

Nous porterons notamment attention :

- au nombre de requêtes;
- aux problèmes de type N+1;
- aux index ajoutés;
- à la manière de calculer les quantités disponibles.

Nous ne nous attendons pas à une optimisation exhaustive.

## Livrables

Votre soumission doit comprendre :

- votre code;
- vos migrations;
- vos tests;
- un court README (ou une section ajoutée au README existant).

Le README doit indiquer :

- vos principales hypothèses;
- votre stratégie de gestion de la concurrence;
- ce que vous amélioreriez avec davantage de temps;
- les éléments incomplets, le cas échéant.

## Utilisation d'outils d'IA

L'utilisation d'outils d'IA est permise.

Vous devez cependant être en mesure d'expliquer l'ensemble de votre solution, de justifier vos décisions et d'apporter une modification à votre code lors de la rencontre de suivi.
