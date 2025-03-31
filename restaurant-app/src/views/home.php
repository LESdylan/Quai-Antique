<?php
// home.php - Homepage of the restaurant application

// Include necessary files
require_once '../utils/Database.php';
require_once '../models/Menu.php';
require_once '../models/Dish.php';

// Initialize database connection
$db = new Database();
$menu = new Menu($db);
$dishes = $menu->getAllDishes(); // Fetch all dishes for the homepage

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quai Antique - Accueil</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <h1>Bienvenue au Quai Antique</h1>
        <nav>
            <ul>
                <li><a href="home.php">Accueil</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="reservation_form.php">Réserver une table</a></li>
                <li><a href="auth/login.php">Connexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Nos Plats</h2>
            <div class="dish-gallery">
                <?php foreach ($dishes as $dish): ?>
                    <div class="dish-item">
                        <h3><?php echo htmlspecialchars($dish['title']); ?></h3>
                        <p><?php echo htmlspecialchars($dish['description']); ?></p>
                        <p>Prix: <?php echo htmlspecialchars($dish['price']); ?> €</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Réservez votre table</h2>
            <p>Profitez d'une expérience gastronomique unique. Réservez dès maintenant!</p>
            <a href="reservation_form.php" class="btn">Réserver une table</a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Quai Antique. Tous droits réservés.</p>
    </footer>

    <script src="../js/scripts.js"></script>
</body>
</html>