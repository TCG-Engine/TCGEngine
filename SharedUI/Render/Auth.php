<?php
// Login + Signup body renderers (chrome — MenuBar/Header/Disclaimer — supplied by the shim).
// Verbatim from Sites/SWUDeck/LoginPage.php:14-49 and Signup.php:9-67.

require_once __DIR__ . '/../../AccountFiles/DiscordOAuth.php';

function RenderDiscordAuthButton(array $def, string $action, string $safeRedirect = ''): string {
    if (empty($def['profile']['discordOAuth'])) return '';
    $site = $def['identity']['rootName'] ?? 'SWUDeck';
    $redirect = $safeRedirect !== '' ? $safeRedirect : ($def['branding']['homeHref'] ?? '');
    $href = htmlspecialchars(DiscordOAuthStartUrl($action, $site, $redirect), ENT_QUOTES, 'UTF-8');
    return '<div class="oauth-auth-block">'
         . '<a class="discord-auth-button" href="' . $href . '">'
         . '<img src="/TCGEngine/Assets/Images/icons/discord.svg" alt="" aria-hidden="true">'
         . '<span>Continue with Discord</span></a>'
         . '<div class="oauth-separator"><span>or</span></div>'
         . '</div>';
}

function RenderOAuthError(): string {
    $message = trim((string)($_GET['oauth_error'] ?? ''));
    if ($message === '') return '';
    return '<p class="oauth-auth-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
}

function RenderSignupError(): string {
    $error = (string)($_GET['error'] ?? '');
    $messages = [
        'emptyinput' => 'Fill in all fields!',
        'invaliduid' => 'Choose a username using only letters and numbers.',
        'invalidemail' => 'Choose a valid email address.',
        'passwordsdontmatch' => 'Passwords do not match!',
        'stmtfailed' => 'Something went wrong. Please try again.',
        'usernametaken' => 'That username is already taken.',
    ];
    if (!isset($messages[$error])) return '';
    return '<p class="signup-error" role="alert">' . htmlspecialchars($messages[$error], ENT_QUOTES, 'UTF-8') . '</p>';
}

function RenderSignupFields(array $def, string $safeRedirect = '', string $errorReturn = ''): string {
    $redirect = htmlspecialchars($safeRedirect, ENT_QUOTES, 'UTF-8');
    $returnInput = $errorReturn === '' ? '' : '<input type="hidden" name="signup_return" value="' . htmlspecialchars($errorReturn, ENT_QUOTES, 'UTF-8') . '">';
    $discordButton = RenderDiscordAuthButton($def, 'signup', $safeRedirect);
    return <<<HTML
<div class="signup-form-form">
  $discordButton
  <form action="/TCGEngine/Database/signup.inc.php" method="post">
    <input type="hidden" name="redirect" value="$redirect">
    $returnInput
    <label for="uid">Username</label>
    <input id="uid" type="text" name="uid" autocomplete="username" required>
    <label for="email">Email</label>
    <input id="email" type="email" name="email" placeholder="name@example.com" autocomplete="email" inputmode="email" required>
    <label for="pwd">Password</label>
    <input id="pwd" type="password" name="pwd" autocomplete="new-password" required>
    <label for="pwdrepeat">Repeat Password</label>
    <input id="pwdrepeat" type="password" name="pwdrepeat" autocomplete="new-password" required>
    <div class="signup-submit">
      <button type="submit" name="submit">Sign Up</button>
    </div>
  </form>
</div>
HTML;
}

function RenderEmbeddedSignup(array $def, string $safeRedirect): string {
    $error = RenderSignupError();
    $fields = RenderSignupFields($def, $safeRedirect, $safeRedirect);
    $loginHref = '/TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php?redirect=' . rawurlencode($safeRedirect);
    return <<<HTML
<section class="swu-inline-signup" aria-labelledby="swu-inline-signup-title">
  <div class="swu-inline-signup-copy">
    <p class="swu-inline-signup-eyebrow">Save and manage your decks</p>
    <h2 id="swu-inline-signup-title">Create your account</h2>
    <p>Sign up to build decks, save your collection, and access them from any device.</p>
  </div>
  $error
  $fields
  <p class="swu-inline-signup-legal">By creating an account, you agree to the <a href="/TCGEngine/SharedUI/TermsOfUse.php">Terms of Use</a> and acknowledge the <a href="/TCGEngine/SharedUI/PrivacyPolicy.php">Privacy Policy</a>.</p>
  <p class="swu-inline-login-link">Already have an account? <a href="$loginHref">Log in</a></p>
</section>
HTML;
}

function RenderLoginPage(array $def, string $safeRedirect = ''): string {
    $esc = htmlspecialchars($safeRedirect, ENT_QUOTES);
    $discordButton = RenderDiscordAuthButton($def, 'login', $safeRedirect);
    $oauthError = RenderOAuthError();
    $site = rawurlencode((string)($def['identity']['rootName'] ?? 'SWUDeck'));
    $signupHref = '/TCGEngine/SharedUI/Sites/' . $site . '/Signup.php';
    if ($safeRedirect !== '') $signupHref .= '?redirect=' . rawurlencode($safeRedirect);
    $signupHref = htmlspecialchars($signupHref, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="core-wrapper auth-page login-page">
<div class="flex-padder"></div>

<div class="flex-wrapper">
  <div class="login container bg-black">
    <h2>Log In</h2>
    <p class="login-message">Make sure to use your username, not your email!</i></p>
    $oauthError
    $discordButton

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
    <p class="login-signup-link">Need an account? <a href="$signupHref">Create account</a></p>
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
    $oauthError = RenderOAuthError();
    $signupError = RenderSignupError();
    $signupFields = RenderSignupFields($def, $safeRedirect);
    ob_start();
    ?>
<div class="core-wrapper auth-page signup-page">
<div class="flex-padder"></div>

<div class="flex-wrapper">
<div class='signup-wrapper container bg-black'>

<section class="signup-form">
  <h2>Sign Up</h2>
  <?php echo $oauthError; ?>
  <?php echo $signupError; ?>
  <div class="signup-disclosures">
    <div class="signup-disclosure container bg-blue">
      <p>Creating an account uses essential cookies to keep you signed in. Learn how we handle your information in our privacy policy.</p>
      <a href='/TCGEngine/SharedUI/PrivacyPolicy.php'>Privacy Policy</a>
    </div>
    <div class="signup-disclosure container bg-blue">
      <p>By creating an account, you agree to follow the site terms.</p>
      <a href='/TCGEngine/SharedUI/TermsOfUse.php'>Terms of Use</a>
    </div>
  </div>
  <?php echo $signupFields; ?>
</section>

</div>
</div>

<div class="flex-padder"></div>
</div>
    <?php
    return ob_get_clean();
}
