<?php
session_start();

// Check if the user is logged in as an administrator
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /public/index.php'); // Redirect to home if not admin
    exit();
}

// Include necessary files
require_once '../../utils/Database.php';
require_once '../../controllers/AdminController.php';

// Initialize the AdminController
$adminController = new AdminController();

// Fetch necessary data for the dashboard
$galleryCount = $adminController->getGalleryCount();
$menuCount = $adminController->getMenuCount();
$reservationCount = $adminController->getReservationCount();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/styles.css">
    <title>Dashboard - Quai Antique</title>
</head>
<body>
    <header>
        <h1>Tableau de bord</h1>
        <nav>
            <ul>
                <li><a href="manage_gallery.php">Gérer la galerie</a></li>
                <li><a href="manage_menu.php">Gérer le menu</a></li>
                <li><a href="manage_reservations.php">Gérer les réservations</a></li>
                <li><a href="/public/index.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Statistiques</h2>
            <ul>
                <li>Nombre d'images dans la galerie : <?php echo $galleryCount; ?></li>
                <li>Nombre de plats au menu : <?php echo $menuCount; ?></li>
                <li>Nombre de réservations : <?php echo $reservationCount; ?></li>
            </ul>
        </section>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Quai Antique. Tous droits réservés.</p>
    </footer>
</body>
</html>