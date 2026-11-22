<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Lax']);
    session_start();
}
const DB_HOST='127.0.0.1'; const DB_NAME='crs_railway'; const DB_USER='root'; const DB_PASS='';
function db(): PDO { static $pdo=null; if (!$pdo) { $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
function h(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.h(csrf()).'">'; }
function verify_csrf(): void { if (!hash_equals(csrf(),(string)($_POST['csrf']??''))) { http_response_code(419); exit('Session expired. Refresh and try again.'); } }
function flash(string $msg, string $type='success'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function money(float $n): string { return 'RM '.number_format($n,2); }
function admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function admin_required(): void { if (!admin_logged_in()) { header('Location: ?page=admin'); exit; } }
function booking_ref(): string { return strtoupper(bin2hex(random_bytes(4))); }
function trip_by_id(int $id): ?array { $s=db()->prepare('SELECT t.*,r.base_fare,r.from_station_id,r.to_station_id, a.name origin,b.name destination,a.code origin_code,b.code destination_code FROM trips t JOIN routes r ON r.id=t.route_id JOIN stations a ON a.id=r.from_station_id JOIN stations b ON b.id=r.to_station_id WHERE t.id=?'); $s->execute([$id]); return $s->fetch()?:null; }
function fare_for(array $trip,string $concession,int $qty): float { $base=(float)$trip['base_fare']; $rate=match($concession){'student'=>.85,'senior'=>.8,default=>1}; return round($base*$rate*$qty,2); }
function download_ticket(int $id): never { $s=db()->prepare('SELECT b.*,p.full_name,t.depart_at,t.arrive_at,r.train_code,a.name origin,c.name destination,bs.seat_code FROM bookings b JOIN passengers p ON p.booking_id=b.id JOIN trips t ON t.id=b.trip_id JOIN routes r ON r.id=t.route_id JOIN stations a ON a.id=r.from_station_id JOIN stations c ON c.id=r.to_station_id JOIN booking_seats bs ON bs.booking_id=b.id WHERE b.id=? AND (b.email=? OR ?=1)'); $admin=admin_logged_in()?1:0; $s->execute([$id,$_SESSION['booking_email']??'', $admin]); $rows=$s->fetchAll(); if(!$rows){http_response_code(404);exit('Ticket not found. Sign in or use the booking email first.');} $b=$rows[0]; header('Content-Type: text/plain; charset=utf-8'); header('Content-Disposition: attachment; filename="CRS-ticket-'.$b['reference'].'.txt"'); echo "CRS RAILWAY - E-TICKET\nBooking: {$b['reference']}\nStatus: {$b['status']}\nTrain: {$b['train_code']}\nJourney: {$b['origin']} to {$b['destination']}\nDeparture: {$b['depart_at']}\nArrival: {$b['arrive_at']}\nPassenger: {$b['full_name']}\nSeat: ".implode(', ',array_column($rows,'seat_code'))."\nTotal: RM {$b['total_fare']}\n\nDemo ticket - payment is simulated.\n"; exit; }
