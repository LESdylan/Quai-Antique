<?php
require_once '../utils/Database.php';
require_once '../models/Menu.php';

$db = new Database();
$menuModel = new Menu($db->getConnection());
$menus = $menuModel->getAllMenus(); // Assuming this method exists in the Menu model

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/styles.css">
    <title>Menu - Quai Antique</title>
</head>
<body>
    <header>
        <h1>Menu du Quai Antique</h1>
    </header>
    <main>
        <section class="menu-section">
            <?php foreach ($menus as $menu): ?>
                <div class="menu-item">
                    <h2><?php echo htmlspecialchars($menu['title']); ?></h2>
                    <p><?php echo htmlspecialchars($menu['description']); ?></p>
                    <p>Prix: <?php echo htmlspecialchars($menu['price']); ?> €</p>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Quai Antique. Tous droits réservés.</p>
    </footer>
    <script src="/js/scripts.js"></script>
</body>
</html>