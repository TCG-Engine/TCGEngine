<?php
include_once './AccountSessionAPI.php';

ClearLoginSession();

header("location: ../SharedUI/MainMenu.php");
exit;
?>
