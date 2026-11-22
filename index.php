<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$page = $_GET['page'] ?? 'home';
$pages = ['home','search','seats','passenger','confirmation','tickets','manage','admin','routes','help'];
if (!in_array($page, $pages, true)) { http_response_code(404); $page = 'home'; }
$titles = ['home'=>'Travel, made simple','search'=>'Find a journey','seats'=>'Choose your seats','passenger'=>'Passenger details','confirmation'=>'Booking confirmed','tickets'=>'My tickets','manage'=>'Manage a booking','admin'=>'Operations overview','routes'=>'Route management','help'=>'Travel support'];
if (in_array($page, ['admin','routes'], true) && !admin_logged_in()) { $page='admin'; }
if (isset($_GET['download'])) { download_ticket((int)$_GET['download']); }
include __DIR__ . '/includes/header.php';
include __DIR__ . '/pages/' . $page . '.php';
include __DIR__ . '/includes/footer.php';
