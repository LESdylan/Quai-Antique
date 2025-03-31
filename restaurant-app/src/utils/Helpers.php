<?php
// src/utils/Helpers.php

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date("d-m-Y", strtotime($date));
}

function formatTime($time) {
    return date("H:i", strtotime($time));
}

function checkAvailability($reservations, $date, $time, $maxGuests) {
    $count = 0;
    foreach ($reservations as $reservation) {
        if ($reservation['date'] === $date && $reservation['time'] === $time) {
            $count += $reservation['guests'];
        }
    }
    return ($count < $maxGuests);
}

function redirect($url) {
    header("Location: $url");
    exit();
}
?>