// FR3: log out
<?php
session_start();
session_destroy();
header('Location: /kelma/kelma/web/index.php');
