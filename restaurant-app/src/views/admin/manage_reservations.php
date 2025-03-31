<?php
require_once '../../utils/Database.php';
require_once '../../models/Reservation.php';

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /restaurant-app/public/index.php');
    exit();
}

$db = new Database();
$reservationModel = new Reservation($db->getConnection());

$reservations = $reservationModel->getAllReservations();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/restaurant-app/public/css/styles.css">
    <title>Gestion des Réservations</title>
</head>
<body>
    <div class="container">
        <h1>Gestion des Réservations</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom du Client</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Nombre de Couverts</th>
                    <th>Allergies</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reservation['id']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['date']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['time']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['covers']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['allergies']); ?></td>
                        <td>
                            <a href="edit_reservation.php?id=<?php echo htmlspecialchars($reservation['id']); ?>">Modifier</a>
                            <a href="delete_reservation.php?id=<?php echo htmlspecialchars($reservation['id']); ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>