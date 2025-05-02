# Quai Antique Restaurant Database

This document describes the database structure and entity relationships for the Quai Antique restaurant application.

## Entity Diagram

```
+------------+          +------------+          +--------------+
|    User    |          |  Reservation |        |    Table     |
+------------+          +------------+          +--------------+
| id         |<---------| userId     |          | id           |
| email      |          | date       |<-------->| number       |
| password   |          | guestCount |          | seats        |
| firstName  |          | status     |          | location     |
| lastName   |          | ...        |          | isActive     |
| roles      |          +------------+          +--------------+
| ...        |                |
+------------+                |
                              v
+------------+          +------------+          +--------------+
|  Category  |          |    Dish    |          |   Allergen   |
+------------+          +------------+          +--------------+
| id         |<---------| categoryId |<-------->| id           |
| name       |          | name       |          | name         |
| description|          | description|          | description  |
| position   |          | price      |          | ...          |
| ...        |          | ...        |          +--------------+
+------------+          +------------+
                              ^
                              |
+------------+                |                 +--------------+
|    Menu    |----------------+                 |   Gallery    |
+------------+                                  +--------------+
| id         |                                  | id           |
| title      |                                  | title        |
| description|                                  | filename     |
| price      |                                  | description  |
| ...        |                                  | ...          |
+------------+                                  +--------------+

+------------+
|  Schedule  |
+------------+
| id         |
| dayOfWeek  |
| lunchOpen  |
| lunchClose |
| dinnerOpen |
| dinnerClose|
| isClosed   |
+------------+
```

## Entity Descriptions

### User
Represents users of the system, including customers and administrators.
- OneToMany relationship with Reservation

### Category
Food categories such as appetizers, main courses, and desserts.
- OneToMany relationship with Dish

### Dish
Individual menu items with pricing.
- ManyToOne relationship with Category
- ManyToMany relationship with Allergen
- ManyToMany relationship with Menu

### Allergen
Food allergens like gluten, dairy, nuts, etc.
- ManyToMany relationship with Dish

### Menu
Set menus with fixed pricing (e.g. lunch specials, tasting menus).
- ManyToMany relationship with Dish

### Schedule
Restaurant opening hours for each day of the week.

### Reservation
Table reservations made by customers.
- ManyToOne relationship with User (optional)
- ManyToMany relationship with Table

### Table
Physical tables in the restaurant.
- ManyToMany relationship with Reservation

### Gallery
Images for displaying on the website.

## Entity Relationships

Below is a breakdown of the key relationships:

1. **User to Reservation**: One-to-Many
   - A user can make multiple reservations
   - A reservation can belong to one user (or be anonymous)

2. **Category to Dish**: One-to-Many
   - A category contains multiple dishes
   - A dish belongs to one category

3. **Dish to Allergen**: Many-to-Many
   - A dish can have multiple allergens
   - An allergen can be associated with multiple dishes

4. **Menu to Dish**: Many-to-Many
   - A menu can include multiple dishes
   - A dish can be part of multiple menus

5. **Reservation to Table**: Many-to-Many
   - A reservation can involve multiple tables
   - A table can be part of multiple reservations (at different times)

## Using the Database

### Creating the Database

There are two ways to create the database:

1. Using the direct SQL approach (recommended):

```bash
php bin/create-restaurant-db.php
```

This will create exactly these tables:
- User
- Restaurant
- Gallery
- Booking
- Menu
- Category
- Messenger_messages
- Food
- Menu_Category
- Food_Category
- Restaurant_Table 
- Booking_Table
- Allergen
- Dish
- 
To completely reset the database:

```bash
php bin/create-restaurant-db.php --force
```

2. Using the ORM entity approach:

```bash
php bin/db-init.php
```

### Adding Relationships

After running the initialization script, manually add relationships between entities using the examples in `src/Entity/Relations.php`.

### Creating Migrations

After adding relationships, create and run a migration:

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Loading Test Data

After creating the database, you can load test data:

```bash
php bin/console doctrine:fixtures:load
```

### Updating the Schema Directly

If you prefer to update the schema without migrations during development:

```bash
php bin/console doctrine:schema:update --force
```

## Best Practices

1. Always use entities and repositories for database operations
2. Keep business logic in dedicated services, not in controllers
3. Use data transfer objects (DTOs) when appropriate
4. Validate all user input using Symfony's validator component
