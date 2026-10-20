<?php
// Shared entry point for application pages: start a secure session, then load
// the helper modules, domain classes, and the database connection.
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/html.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/LevelRules.php';
require_once __DIR__ . '/Gamification.php';
require_once __DIR__ . '/UserRepository.php';
require_once __DIR__ . '/BookingRepository.php';
require_once __DIR__ . '/BookingService.php';
require_once __DIR__ . '/ProgressRepository.php';
require_once __DIR__ . '/MovieRepository.php';
require_once __DIR__ . '/ContactMessageRepository.php';
require_once __DIR__ . '/ShowtimeRepository.php';

$pdo = require __DIR__ . '/db.php';
