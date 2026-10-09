<?php /* Usage: $title = 'Page title'; require 'includes/header.php'; */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Kelma') ?> | Kelma</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/kelma/kelma/web/assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="logo" href="/kelma/kelma/web/index.php">Kelma 🎈</a>
  <div class="nav-links">
    <a href="/kelma/kelma/web/index.php">Home</a>
    <a href="/kelma/kelma/web/index.php#modules">Modules</a>
    <a href="/kelma/kelma/web/index.php#about">About</a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a class="btn btn-yellow" href="/kelma/kelma/web/<?= $_SESSION['role'] ?>_dashboard.php">Dashboard</a>
      <a class="btn btn-coral" href="/kelma/kelma/web/logout.php">Logout</a>
    <?php else: ?>
      <a class="btn btn-coral" href="/kelma/kelma/web/login.php">Login Portal</a>
    <?php endif; ?>
  </div>
</nav>
