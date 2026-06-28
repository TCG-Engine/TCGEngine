<?php
// Login + Signup body renderers (chrome — MenuBar/Header/Disclaimer — supplied by the shim).
// Verbatim from Sites/SWUDeck/LoginPage.php:14-49 and Signup.php:9-67.

function RenderLoginPage(array $def, string $safeRedirect = ''): string {
    $esc = htmlspecialchars($safeRedirect, ENT_QUOTES);
    return <<<HTML
<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
  <div class="login container bg-black">
    <h2>Log In</h2>
    <p class="login-message">Make sure to use your username, not your email!</i></p>

    <form action="/TCGEngine/AccountFiles/AttemptPasswordLogin.php" method="post" class="LoginForm">
      <input type="hidden" name="redirect" value="$esc">
      <label>Username</label>
      <input class="username" type="text" name="userID">
      <label>Password</label>
      <input class="password" type="password" name="password">
      <div class="remember-me">
      <input type="checkbox" checked='checked' id="rememberMe" name="rememberMe" value="rememberMe">
      <label for="rememberMe">Remember Me</label>
      </div>
      <button type="submit" name="submit">Submit</button>
    </form>
    <!--
    <form action="ResetPassword.php" method="post" style='text-align:center;'>
      <button type="submit" name="reset-password">Forgot Password?</button>
    </form>
    -->
  </div>

  <div class="container bg-blue">
    <p>By using the Remember Me function, you consent to a cookie being stored in your browser for the purpose of identifying
      your account on future visits.</p>
    <a href='/TCGEngine/SharedUI/MenuFiles/PrivacyPolicy.php'>Privacy Policy</a>
  </div>

</div>

<div class="flex-padder"></div>
</div>
HTML;
}

function RenderSignup(array $def, string $safeRedirect = ''): string {
    ob_start();
    ?>
<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
<div class='signup-wrapper container bg-black'>

<section class="signup-form">
  <h2>Sign Up</h2>
  <div class="container bg-blue">
    <p>By creating an account, you consent to the use of cookies on the site. Check the Privacy Policy for details on how cookies are used on the site.</p>
    <a href='/TCGEngine/SharedUI/PrivacyPolicy.php'>Privacy Policy</a>
  </div>
  <div class="container bg-blue">
    <p>By using this site, you agree to comply by the terms of use. Check the terms of use for details.</p>
    <a href='/TCGEngine/SharedUI/TermsOfUse.php'>Terms of Use</a>
  </div>
  <div class="signup-form-form">
    <form action="/TCGEngine/Database/signup.inc.php" method="post">
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($safeRedirect, ENT_QUOTES); ?>">
      <label for="uid">Username</label>
        <input type="text" name="uid">
      <label for="email">Email</label>
        <input type="text" name="email" placeholder="name@example.com">
      <label for="pwd">Password</label>
        <input type="password" name="pwd">
      <label for="pwdrepeat">Repeat Password</label>
        <input type="password" name="pwdrepeat">
      <div style="text-align:center;">
        <button type="submit" name="submit">Sign Up</button>
      </div>
    </form>
  </div>

  <?php
  // Error messages
  if (isset($_GET["error"])) {
    if ($_GET["error"] == "emptyinput") {
      echo "<p>Fill in all fields!</p>";
    } else if ($_GET["error"] == "invaliduid") {
      echo "<p>Choose a username without any special characters</p>";
    } else if ($_GET["error"] == "invalidemail") {
      echo "<p>Choose a valid email</p>";
    } else if ($_GET["error"] == "passwordsdontmatch") {
      echo "<p>Passwords doesn't match!</p>";
    } else if ($_GET["error"] == "stmtfailed") {
      echo "<p>Something went wrong!</p>";
    } else if ($_GET["error"] == "usernametaken") {
      echo "<p>Username already taken!</p>";
    } else if ($_GET["error"] == "none") {
      echo "<h2>You've signed up!</h2>";
    }
  }
  ?>
</section>

</div>
</div>

<div class="flex-padder"></div>
</div>
    <?php
    return ob_get_clean();
}
