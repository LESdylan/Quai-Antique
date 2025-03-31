# Restaurant App

## Overview
The Restaurant App is a web application designed to provide a seamless experience for both clients and administrators of the Quai Antique restaurant. It allows users to manage reservations, view menus, and interact with the restaurant's offerings while providing administrators with tools to manage the restaurant's operations effectively.

## Features
- **User Authentication**: Clients can register and log in to manage their reservations and profiles.
- **Reservation Management**: Clients can reserve tables, view their reservations, and modify or cancel them.
- **Menu Management**: Administrators can manage the restaurant's menu, including adding, editing, and deleting dishes and categories.
- **Gallery Management**: Administrators can manage a gallery of images showcasing the restaurant's dishes.
- **Admin Dashboard**: A centralized dashboard for administrators to oversee restaurant operations.

## Project Structure
```
restaurant-app
├── public
│   ├── css
│   │   └── styles.css
│   ├── js
│   │   └── scripts.js
│   ├── images
│   └── index.php
├── src
│   ├── controllers
│   │   ├── AdminController.php
│   │   ├── ClientController.php
│   │   └── ReservationController.php
│   ├── models
│   │   ├── User.php
│   │   ├── Reservation.php
│   │   ├── Menu.php
│   │   └── Dish.php
│   ├── views
│   │   ├── admin
│   │   │   ├── dashboard.php
│   │   │   ├── manage_gallery.php
│   │   │   ├── manage_menu.php
│   │   │   └── manage_reservations.php
│   │   ├── client
│   │   │   ├── reservations.php
│   │   │   └── profile.php
│   │   ├── auth
│   │   │   ├── login.php
│   │   │   └── register.php
│   │   ├── home.php
│   │   ├── menu.php
│   │   └── reservation_form.php
│   └── utils
│       ├── Database.php
│       └── Helpers.php
├── config
│   └── config.php
├── migrations
│   └── create_tables.sql
├── .htaccess
├── README.md
└── composer.json
```

## Installation
1. Clone the repository:
   ```
   git clone <repository-url>
   ```
2. Navigate to the project directory:
   ```
   cd restaurant-app
   ```
3. Install dependencies using Composer:
   ```
   composer install
   ```
4. Set up the database by running the SQL migration:
   ```
   mysql -u <username> -p < database_name < migrations/create_tables.sql
   ```
5. Configure your database settings in `config/config.php`.

## Usage
- Access the application by navigating to `http://localhost/restaurant-app/public/index.php` in your web browser.
- Clients can register, log in, and manage their reservations.
- Administrators can log in to manage the restaurant's operations through the admin dashboard.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.