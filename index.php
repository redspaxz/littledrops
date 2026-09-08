<?php

declare(strict_types=1);

/**
 * PBMS — Property & Building Management System (Bamenda, Cameroon)
 * Single front controller for the modular monolith.
 */

define('PBMS', true);

require __DIR__ . '/core/bootstrap.php';

$router = new Core\Router();
$router->loadModules();
$router->dispatch(Core\Request::route());
