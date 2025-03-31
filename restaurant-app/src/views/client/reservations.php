<?php
session_start();
require_once '../../utils/Database.php';
require_once '../../models/Reservation.php';

$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: /restaurant-app/public/auth/login.php");
    exit();
}

$reservationModel = new Reservation($conn);
$reservations = $reservationModel->getReservationsByUserId($userId);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/restaurant-app/public/css/styles.css">
    <title>Mes Réservations</title>
</head>
<body>
    <div class="container">
        <h1>Mes Réservations</h1>
        <?php if (count($reservations) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Nombre de couverts</th>
                        <th>Allergies</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['date']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['time']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['covers']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['allergies']); ?></td>
                            <td>
                                <a href="modify_reservation.php?id=<?php echo $reservation['id']; ?>">Modifier</a>
                                <a href="cancel_reservation.php?id=<?php echo $reservation['id']; ?>">Annuler</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucune réservation trouvée.</p>
        <?php endif; ?>
    </div>
</body>
</html>