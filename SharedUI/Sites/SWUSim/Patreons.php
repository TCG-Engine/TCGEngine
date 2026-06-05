<?php


$userName = LoggedInUserName();

$conn = GetLocalMySQLConnection();
$sql = "SELECT * FROM users where usersUid='$userName'";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  echo ("ERROR");
  exit();
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_close($conn);
$access_token = $row["patreonAccessToken"];

try {
  PatreonLogin($access_token, false, false);
} catch (\Exception $e) { }

?>