# Linvara — Réservation d'inventaire

Bienvenue dans Linvara, une application Laravel + Vue 3 de gestion des commandes, des entrepôts et des réservations d'inventaire.

**L'énoncé complet de l'exercice se trouve dans [EXERCISE.md](EXERCISE.md).**

## Prérequis

- PHP 8.4 et Composer
- Node.js 20+
- Docker (pour MySQL)

## Installation

```bash
docker compose up -d
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
composer run dev
```

L'application est ensuite accessible à [http://localhost:8000](http://localhost:8000).

MySQL écoute sur le port `3307` (pour éviter les conflits avec une instance locale). Le conteneur crée deux bases : `takehome` (développement) et `takehome_testing` (tests).

## Compte de démonstration

| Champ        | Valeur             |
| ------------ | ------------------ |
| Courriel     | `demo@example.com` |
| Mot de passe | `password`         |

## Tour du projet

- **Orders** : liste des commandes et page de détail d'une commande avec ses lignes (quantités demandées, réservées, restantes, prix). C'est à partir de cette page que la feature demandée s'intègre.
- **Warehouses** : liste des entrepôts avec un résumé de leur stock (produits en stock, unités totales).
- **Inventory** : stock physique par produit et par entrepôt. Deux tables travaillent ensemble : `inventory_balances` stocke la quantité physique courante par paire produit/entrepôt (lecture rapide), et `inventory_movements` garde l'historique complet. Les deux sont écrites dans la même transaction — **une balance doit toujours être égale à la somme de ses mouvements**. Une réservation ne modifie aucune de ces tables. L'ajustement de stock (ajout/retrait) sert d'exemple de bout en bout : contrôleur mince, FormRequest, Action, transaction, page Inertia, dialogue, tests.
- **Architecture** : la logique métier vit dans `app/Actions` (une classe = une opération, méthode `handle()`, interface `App\Actions\Action`).

## Commandes utiles

```bash
php artisan test --compact   # Tests (utilise la base takehome_testing)
composer run lint            # Formatage PHP (Pint)
composer run types:check     # Analyse statique PHP (PHPStan)
npm run lint                 # ESLint
npm run types:check          # TypeScript (vue-tsc)
npm run format               # Prettier
```

## Notes

- Les routes frontend sont générées par [Wayfinder](https://github.com/laravel/wayfinder) : après avoir ajouté des routes Laravel, elles sont régénérées automatiquement par Vite (ou via `php artisan wayfinder:generate`).
- Les tests s'exécutent contre MySQL : le conteneur Docker doit être démarré.

## Implémentation des réservations

### Fonctionnement

- Depuis une commande, l'utilisateur peut choisir une ligne, un entrepôt et saisir une quantité à réserver.
- Une nouvelle réservation complète la réservation existante lorsqu'elle concerne la même ligne de commande.
- La quantité demandée ne peut pas dépasser le stock disponible ni la quantité restant à réserver sur la commande.
- Une réservation ne retire pas physiquement le stock. Elle réduit uniquement la quantité encore disponible pour les autres commandes.
- Le stock affiché dans l'inventaire et dans le formulaire d'ajustement est calculé à partir de l'historique des mouvements.
- L'application utilise actuellement un seul état de commande : `confirmed`.

### Fiabilité et expérience utilisateur

- Les réservations sont protégées afin que deux demandes simultanées ne puissent pas réserver plus de stock que disponible. En cas de conflit temporaire, l'opération est réessayée automatiquement jusqu'à trois fois.
- Les ajustements de stock ne peuvent pas rendre le stock négatif ni le faire descendre sous la quantité déjà réservée.
- Les erreurs sont affichées directement dans les formulaires avec des messages compréhensibles. Les refus et les problèmes importants sont également ajoutés aux logs de l'application.
- La page d'une commande affiche les quantités commandées, réservées et restantes, ainsi que le stock disponible dans chaque entrepôt. Les informations sont actualisées après chaque réservation réussie.
- Le dashboard présente les principaux indicateurs : stock physique, stock réservé, stock disponible, commandes récentes, entrepôts et alertes de stock faible.
- Les listes d'inventaire, de commandes et d'entrepôts affichent 12 éléments par page. Les lignes d'une commande restent toutes visibles pour conserver un récapitulatif complet.
- La logique métier est séparée des contrôleurs et les principaux scénarios sont couverts par des tests automatisés sur MySQL.

### Avec davantage de temps

- Ajouter un test utilisant deux opérations réellement simultanées pour vérifier automatiquement le comportement en cas de forte concurrence.
- Ajouter des rôles et permissions différents pour la consultation, la réservation et l'ajustement du stock.
- Permettre de libérer, annuler ou déplacer une réservation vers un autre entrepôt.
- Ajouter d'autres états de commande et définir clairement leurs règles de transition.
- Ajouter un veritable dashboard avec probablement des rappels 
- Ajouter des champs de filtre et recherche sur les Orders, Inventories, Warehouses et dans le details d'une commande pour fluidifier l'experience utilisateur. 

La fonctionnalité demandée est complète dans le périmètre actuel. Le principal ajout souhaitable serait un test automatisé avec plusieurs réservations exécutées exactement au même moment.
