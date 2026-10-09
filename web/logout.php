<?php
session_start();
session_destroy();
header('Location: /kelma/web/index.php');
