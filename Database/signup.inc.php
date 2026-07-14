<?php

include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/API.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once './ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';

function signup_safe_redirect($redirect, $fallback) {
  if ($redirect == null || $redirect == "") return $fallback;
  $parts = parse_url($redirect);
  if ($parts === false) return $fallback;
  if (isset($parts["scheme"]) || isset($parts["host"])) return $fallback;
  $path = isset($parts["path"]) ? $parts["path"] : "";
  if (strpos($path, "/TCGEngine/") !== 0) return $fallback;
  return $redirect;
}

function signup_page_redirect($redirect, $error = "", $returnPage = "") {
  $location = signup_safe_redirect($returnPage, "/TCGEngine/SharedUI/Signup.php");
  $params = [];
  if ($error != "") $params["error"] = $error;
  $safeRedirect = signup_safe_redirect($redirect, "");
  if ($safeRedirect != "") $params["redirect"] = $safeRedirect;
  if (count($params) > 0) $location .= "?" . http_build_query($params);
  return $location;
}

if (isset($_POST["submit"])) {

  // First we get the form data from the URL
  $username = $_POST["uid"];
  $email = $_POST["email"];
  $pwd = $_POST["pwd"];
  $pwdRepeat = $_POST["pwdrepeat"];
  $redirect = $_POST["redirect"] ?? "";
  $signupReturn = $_POST["signup_return"] ?? "";

  // Then we run a bunch of error handlers to catch any user mistakes we can (you can add more than I did)
  // These functions can be found in functions.inc.php

  require_once "./ConnectionManager.php";
  require_once 'functions.inc.php';

	$conn = GetLocalMySQLConnection();
  // We set the functions "!== false" since "=== true" has a risk of giving us the wrong outcome
  if (emptyInputSignup($username, $email, $pwd, $pwdRepeat) !== false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "emptyinput", $signupReturn));
		exit();
  }

	// Proper username chosen
  if (invalidUid($username) !== false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "invaliduid", $signupReturn));
		exit();
  }
  // Proper email chosen
  if (invalidEmail($email) !== false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "invalidemail", $signupReturn));
		exit();
  }
  // Do the two passwords match?
  if (pwdMatch($pwd, $pwdRepeat) !== false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "passwordsdontmatch", $signupReturn));
		exit();
  }
  // Is the username taken already
  if (uidExists($conn, $username) !== false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "usernametaken", $signupReturn));
		exit();
  }

  // If we get to here, it means there are no user errors

  // Now we insert the user into the database
  $status = createUser($conn, $username, $email, $pwd);
  if($status == false) {
    mysqli_close($conn);
    header("location: " . signup_page_redirect($redirect, "stmtfailed", $signupReturn));
    exit();
  }

  mysqli_close($conn);
  AttemptPasswordLogin($username, $pwd, true, $redirect);
  header("location: " . signup_safe_redirect($redirect, "/TCGEngine/SharedUI/MainMenu.php"));
  exit();

} else {
  header("location: " . signup_page_redirect(""));
  exit();
}
