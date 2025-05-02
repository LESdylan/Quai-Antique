# Repositories in Symfony

This directory contains repository classes for the entities in the Quai Antique project.

## What are repositories?

Repositories are classes that handle database operations for specific entities. They provide methods for querying, finding, and manipulating entities.

## Repository-Entity Relationship

Each entity in `src/Entity` typically has a corresponding repository in this directory:
- `UserEntity` → `UserRepository`
- `DishEntity` → `DishRepository`
- etc.

## Creating Repositories

When you create an entity with the `make:entity` command, Symfony automatically creates a repository class for it.

You can also create a repository manually using:

```bash
php bin/console make:repository EntityName
```

## Example Usage

Repositories can be used in controllers or services to fetch data:

```php
// In a controller
public function showMenu(DishRepository $dishRepository)
{
    $dishes = $dishRepository->findAllActive();
    
    return $this->render('menu/index.html.twig', [
        'dishes' => $dishes,
    ]);
}
```

## Custom Query Methods

You can add custom query methods to repositories to encapsulate business logic:

```php
// In DishRepository.php
public function findDailySpecials()
{
    return $this->createQueryBuilder('d')
        ->where('d.isSpecial = :special')
        ->setParameter('special', true)
        ->orderBy('d.price', 'ASC')
        ->getQuery()
        ->getResult();
}
```
