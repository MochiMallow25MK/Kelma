<?php
session_start();
session_destroy();
header('Location: /kelma/kelma/web/index.php');
