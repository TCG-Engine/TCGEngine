<?php

include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/API.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once './ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';

if (isset($_POST["submit"])) {

  // First we get the form data from the URL
  $username = $_POST["uid"];
  $email = $_POST["email"];
  $pwd = $_POST["pwd"];
  $pwdRepeat = $_POST["pwdrepeat"];

  // Then we run a bunch of error handlers to catch any user mistakes we can (you can add more than I did)
  // These functions can be found in functions.inc.php

  require_once "./ConnectionManager.php";
  require_once 'functions.inc.php';

	$conn = GetLocalMySQLConnection();
  // We set the functions "!== false" since "=== true" has a risk of giving us the wrong outcome
  if (emptyInputSignup($username, $email, $pwd, $pwdRepeat) !== false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=emptyinput");
		exit();
  }

	// Proper username chosen
  if (invalidUid($username) !== false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=invaliduid");
		exit();
  }
  // Proper email chosen
  if (invalidEmail($email) !== false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=invalidemail");
		exit();
  }
  // Do the two passwords match?
  if (pwdMatch($pwd, $pwdRepeat) !== false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=passwordsdontmatch");
		exit();
  }
  // Is the username taken already
  if (uidExists($conn, $username) !== false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=usernametaken");
		exit();
  }

  // If we get to here, it means there are no user errors

  // Now we insert the user into the database
  $status = createUser($conn, $username, $email, $pwd);
  if($status == false) {
    mysqli_close($conn);
    header("location: /TCGEngine/SharedUI/Signup.php?error=stmtfailed");
    exit();
  }

  mysqli_close($conn);
  AttemptPasswordLogin($username, $pwd, true);
  header("location: /TCGEngine/SharedUI/MainMenu.php");
  exit();

} else {
  header("location: /TCGEngine/SharedUI/Signup.php");
  exit();
}
