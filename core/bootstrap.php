<?php

declare(strict_types=1);

/**
 * PBMS bootstrap — the shared kernel every request flows through.
 */

defined('PBMS') || exit('No direct access');

require __DIR__ . '/autoloader.php';
require __DIR__ . '/helpers.php';

date_default_timezone_set(\Core\Config::get('app.timezone', 'Africa/Douala'));
mb_internal_encoding('UTF-8');

\Core\Auth::boot();
