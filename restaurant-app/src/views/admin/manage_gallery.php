<?php
// manage_gallery.php

require_once '../../utils/Database.php';
require_once '../../utils/Helpers.php';

session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /public/index.php');
    exit();
}

$db = new Database();
$galleryImages = $db->query("SELECT * FROM gallery");

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $imageTitle = $_POST['title'];
    $imageFile = $_FILES['image'];

    // Validate and upload image
    if ($imageFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../public/images/';
        $uploadFile = $uploadDir . basename($imageFile['name']);

        if (move_uploaded_file($imageFile['tmp_name'], $uploadFile)) {
            $db->query("INSERT INTO gallery (title, filename) VALUES (?, ?)", [$imageTitle, $imageFile['name']]);
            header('Location: manage_gallery.php');
            exit();
        } else {
            $error = "Failed to upload image.";
        }
    } else {
        $error = "Error uploading image.";
    }
}

// Handle image deletion
if (isset($_GET['delete'])) {
    $imageId = $_GET['delete'];
    $image = $db->query("SELECT * FROM gallery WHERE id = ?", [$imageId])[0];

    if ($image) {
        unlink('../../public/images/' . $image['filename']);
        $db->query("DELETE FROM gallery WHERE id = ?", [$imageId]);
        header('Location: manage_gallery.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/styles.css">
    <title>Gestion de la Galerie</title>
</head>
<body>
    <h1>Gestion de la Galerie</h1>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="manage_gallery.php" method="post" enctype="multipart/form-data">
        <label for="title">Titre de l'image:</label>
        <input type="text" name="title" required>
        <label for="image">Choisir une image:</label>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit">Ajouter l'image</button>
    </form>

    <h2>Galerie d'Images</h2>
    <div class="gallery">
        <?php foreach ($galleryImages as $image): ?>
            <div class="gallery-item">
                <img src="/images/<?= htmlspecialchars($image['filename']) ?>" alt="<?= htmlspecialchars($image['title']) ?>">
                <h3><?= htmlspecialchars($image['title']) ?></h3>
                <a href="?delete=<?= $image['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image ?');">Supprimer</a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>