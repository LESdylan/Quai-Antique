# Entities in Symfony

This directory contains the entity classes for the Quai Antique project.

## What are entities?

Entities are PHP classes that represent database tables. Each property in an entity class typically maps to a column in the database.

## Creating Entities

You can create a new entity using the Symfony console command:

```bash
php bin/console make:entity
```

This will prompt you for:
- The entity name (e.g., `User`, `Dish`, `Reservation`)
- Properties for the entity and their types
- Relationships to other entities

## Entity Examples

For a restaurant application, you might have entities like:
- `User` - For authentication and user profiles
- `Dish` - Menu items offered by the restaurant
- `Category` - Categories for dishes (appetizers, main courses, etc.)
- `Reservation` - Table reservations made by users
- `Schedule` - Restaurant opening hours

Each entity will automatically get a corresponding repository class in the `src/Repository` directory.
