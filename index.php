<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (isset($_GET['api'])) {
	require __DIR__ . '/app/api.php';
	exit;
}

require __DIR__ . '/app/index.php';
