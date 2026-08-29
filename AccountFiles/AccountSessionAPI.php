<?php
  function IsUserLoggedIn()
  {
    CheckSession();
    return isset($_SESSION['useruid']);
  }

  function LoggedInUser()
  {
    CheckSession();
    if(!isset($_SESSION["userid"])) return "";
    return $_SESSION["userid"];
  }

  function LoggedInUserName()
  {
    CheckSession();
    return $_SESSION["useruid"];
  }

  // Developer-only tools may bypass account auth when explicitly opted in through DEVENV, or
  // when the HTTP request is loopback at both ends. Requiring both REMOTE_ADDR and Host to be
  // local prevents a forged Host header from enabling the bypass on a hosted environment.
  function IsLocalDevelopmentRequest()
  {
    if (strtolower(trim(strval(getenv('DEVENV')))) === 'true') return true;

    return IsStrictLoopbackRequest();
  }

  // Unlike IsLocalDevelopmentRequest, this never trusts DEVENV. Security-sensitive admin
  // endpoints use it so a production box accidentally carrying DEVENV=true does not become public.
  function IsStrictLoopbackRequest()
  {

    $remoteAddr = strtolower(trim(strval($_SERVER['REMOTE_ADDR'] ?? '')));
    $host = strtolower(trim(strval($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
    if (substr($host, 0, 1) === '[') {
      $closeBracket = strpos($host, ']');
      if ($closeBracket !== false) $host = substr($host, 0, $closeBracket + 1);
    } else {
      $host = preg_replace('/:\d+$/', '', $host);
    }

    return in_array($remoteAddr, ['127.0.0.1', '::1'], true)
      && in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
  }

  function ApprovedModeratorUserNames()
  {
    return ["ninin", "OotTheMonk"];
  }

  function CheckLoggedInUserMod()
  {
    if (IsLocalDevelopmentRequest()) return '';

    if(!IsUserLoggedIn()) {
      return "You must be logged in to use this";
    }

    $userName = LoggedInUserName();
    $mods = ApprovedModeratorUserNames();

    if(!in_array($userName, $mods)) {
      return "Error: You must be an approved user to use this";
    }

    return "";
  }

  function CheckLoggedInUserModStrict()
  {
    if (IsStrictLoopbackRequest()) return '';
    if(!IsUserLoggedIn()) return "You must be logged in to use this";
    if(!in_array(LoggedInUserName(), ApprovedModeratorUserNames(), true)) {
      return "Error: You must be an approved user to use this";
    }
    return '';
  }

  function IsLoggedInUserPatron()
  {
    return (isset($_SESSION["isPatron"]) ? "1" : "0");
  }

  function SessionLastGameName()
  {
    CheckSession();
    if(!isset($_SESSION["lastGameName"])) return "";
    return $_SESSION["lastGameName"];
  }

  function SessionLastGamePlayerID()
  {
    CheckSession();
    return $_SESSION["lastPlayerId"];
  }

  function SessionLastAuthKey()
  {
    CheckSession();
    return $_SESSION["lastAuthKey"];
  }

  function ClearLoginSession()
  {
    //First clear the session
    session_start();
    session_unset();
    session_destroy();

    //Also delete cookies
    if (isset($_COOKIE["rememberMeToken"])) setcookie("rememberMeToken", "", time() + 1, "/");
    if (isset($_COOKIE["lastAuthKey"])) setcookie("lastAuthKey", "", time() + 1, "/");
  }

  function CheckSession()
  {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
      session_start();
    }
  }
?>
