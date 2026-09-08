<?php /** Layouts/auth.php — bare page for the sign-in screen. */ ?>
<!DOCTYPE html>
<html lang="<?= e(\Core\Auth::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? t('auth.login')) ?> · <?= e(t('app.name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="auth-body">
<?= $content ?>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
