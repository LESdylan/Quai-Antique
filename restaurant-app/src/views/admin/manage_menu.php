<?php
require_once '../../utils/Database.php';
require_once '../../models/Menu.php';

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /restaurant-app/public/index.php');
    exit();
}

$db = new Database();
$menuModel = new Menu($db->getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_menu'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $price = $_POST['price'];

        $menuModel->addMenu($title, $description, $price);
    } elseif (isset($_POST['edit_menu'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $price = $_POST['price'];

        $menuModel->editMenu($id, $title, $description, $price);
    } elseif (isset($_POST['delete_menu'])) {
        $id = $_POST['id'];
        $menuModel->deleteMenu($id);
    }
}

$menus = $menuModel->getAllMenus();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/restaurant-app/public/css/styles.css">
    <title>Gestion du Menu</title>
</head>
<body>
    <h1>Gestion du Menu</h1>

    <form method="POST">
        <h2>Ajouter un Menu</h2>
        <input type="text" name="title" placeholder="Titre" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="number" name="price" placeholder="Prix" required>
        <button type="submit" name="add_menu">Ajouter</button>
    </form>

    <h2>Menus Existants</h2>
    <table>
        <tr>
            <th>Titre</th>
            <th>Description</th>
            <th>Prix</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($menus as $menu): ?>
        <tr>
            <td><?php echo htmlspecialchars($menu['title']); ?></td>
            <td><?php echo htmlspecialchars($menu['description']); ?></td>
            <td><?php echo htmlspecialchars($menu['price']); ?> €</td>
            <td>
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                    <input type="text" name="title" value="<?php echo htmlspecialchars($menu['title']); ?>" required>
                    <textarea name="description" required><?php echo htmlspecialchars($menu['description']); ?></textarea>
                    <input type="number" name="price" value="<?php echo htmlspecialchars($menu['price']); ?>" required>
                    <button type="submit" name="edit_menu">Modifier</button>
                    <button type="submit" name="delete_menu">Supprimer</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>