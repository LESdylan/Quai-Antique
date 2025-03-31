<?php
session_start();
require_once '../../utils/Database.php';
require_once '../../models/User.php';

$db = new Database();
$conn = $db->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: /restaurant-app/public/index.php");
    exit();
}

$user = new User($conn);
$userData = $user->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($user->updateProfile($_SESSION['user_id'], $name, $email, $password)) {
        $message = "Profile updated successfully!";
    } else {
        $message = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/restaurant-app/public/css/styles.css">
    <title>Mon Profil</title>
</head>
<body>
    <div class="container">
        <h1>Mon Profil</h1>
        <?php if (isset($message)) : ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="name">Nom:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>

            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" placeholder="Laissez vide pour ne pas changer">

            <button type="submit">Mettre à jour</button>
        </form>
        <a href="/restaurant-app/src/views/client/reservations.php">Voir mes réservations</a>
    </div>
</body>
</html>