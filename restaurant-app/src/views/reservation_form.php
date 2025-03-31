<?php
session_start();
require_once '../utils/Database.php';
require_once '../models/Reservation.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $number_of_guests = $_POST['number_of_guests'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $allergies = $_POST['allergies'];

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /restaurant-app/src/views/auth/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $reservation = new Reservation($conn);
    $available = $reservation->checkAvailability($reservation_date, $reservation_time, $number_of_guests);

    if ($available) {
        $reservation->createReservation($user_id, $reservation_date, $reservation_time, $number_of_guests, $allergies);
        echo "<script>alert('Reservation successful!');</script>";
    } else {
        echo "<script>alert('Sorry, not enough seats available.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/restaurant-app/public/css/styles.css">
    <title>Réserver une table</title>
</head>
<body>
    <h1>Réserver une table</h1>
    <form action="" method="POST">
        <label for="number_of_guests">Nombre de convives:</label>
        <input type="number" id="number_of_guests" name="number_of_guests" required>

        <label for="reservation_date">Date:</label>
        <input type="date" id="reservation_date" name="reservation_date" required>

        <label for="reservation_time">Heure:</label>
        <select id="reservation_time" name="reservation_time" required>
            <option value="12:00">12:00</option>
            <option value="12:15">12:15</option>
            <option value="12:30">12:30</option>
            <option value="12:45">12:45</option>
            <option value="13:00">13:00</option>
            <option value="13:15">13:15</option>
            <option value="13:30">13:30</option>
            <option value="13:45">13:45</option>
            <option value="14:00">14:00</option>
            <option value="19:00">19:00</option>
            <option value="19:15">19:15</option>
            <option value="19:30">19:30</option>
            <option value="19:45">19:45</option>
            <option value="20:00">20:00</option>
            <option value="20:15">20:15</option>
            <option value="20:30">20:30</option>
            <option value="20:45">20:45</option>
            <option value="21:00">21:00</option>
        </select>

        <label for="allergies">Allergies:</label>
        <textarea id="allergies" name="allergies"></textarea>

        <button type="submit">Réserver</button>
    </form>
</body>
</html>