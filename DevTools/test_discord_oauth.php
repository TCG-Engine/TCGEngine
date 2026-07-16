<?php

error_reporting(E_ALL);
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../AccountFiles/DiscordOAuth.php';
require_once __DIR__ . '/../SharedUI/Render/Auth.php';

$pass = 0;
$fail = 0;

function discordAuthCheck(string $name, bool $condition): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
    } else {
        $fail++;
        echo "FAIL: $name\n";
    }
}

discordAuthCheck('selected shared site is accepted', DiscordOAuthNormalizeSite('SWUSim') === 'SWUSim');
discordAuthCheck('CardEditor is accepted', DiscordOAuthNormalizeSite('CardEditor') === 'CardEditor');
discordAuthCheck('legacy RBSim is not opted in', DiscordOAuthNormalizeSite('RBSim') === 'SWUDeck');
discordAuthCheck('external return is rejected', DiscordOAuthSafeReturn('https://example.com/x', 'SWUDeck') === DiscordOAuthDefaultReturn('SWUDeck'));
discordAuthCheck('same-repo return is accepted', DiscordOAuthSafeReturn('/TCGEngine/Stats/Decks.php?x=1', 'SWUDeck') === '/TCGEngine/Stats/Decks.php?x=1');

$start = DiscordOAuthStartUrl('signup', 'SWUDeck', '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php');
discordAuthCheck('start URL selects signup', strpos($start, 'action=signup') !== false);
discordAuthCheck('start URL selects SWUDeck', strpos($start, 'site=SWUDeck') !== false);

$state = DiscordOAuthBeginFlow('login', 'SWUDeck', '/TCGEngine/Stats/Decks.php');
$flow = DiscordOAuthConsumeFlow($state);
discordAuthCheck('flow keeps action', ($flow['action'] ?? '') === 'login');
discordAuthCheck('flow keeps safe return', ($flow['redirect'] ?? '') === '/TCGEngine/Stats/Decks.php');
$consumedRejected = false;
try { DiscordOAuthConsumeFlow($state); } catch (RuntimeException $e) { $consumedRejected = true; }
discordAuthCheck('state is single use', $consumedRejected);

putenv('DISCORD_CLIENT_SECRET=test-secret');
$previousHost = $_SERVER['HTTP_HOST'] ?? null;
$_SERVER['HTTP_HOST'] = 'zendo.gg';
$zendoConfig = DiscordOAuthConfig();
discordAuthCheck('Zendo uses its own Discord callback', $zendoConfig['redirectUri'] === 'https://zendo.gg/TCGEngine/APIs/DiscordLogin.php');
$_SERVER['HTTP_HOST'] = 'unconfigured.example';
$fallbackConfig = DiscordOAuthConfig();
discordAuthCheck('unknown hosts retain the SWUStats callback', $fallbackConfig['redirectUri'] === 'https://www.swustats.net/TCGEngine/APIs/DiscordLogin.php');
if ($previousHost === null) unset($_SERVER['HTTP_HOST']); else $_SERVER['HTTP_HOST'] = $previousHost;
$authorizeUrl = DiscordOAuthAuthorizeUrl('state-token');
discordAuthCheck('authorization uses code flow', strpos($authorizeUrl, 'response_type=code') !== false);
discordAuthCheck('authorization requests identity and email', strpos($authorizeUrl, 'scope=identify%20email') !== false);
discordAuthCheck('authorization carries opaque state', strpos($authorizeUrl, 'state=state-token') !== false);
putenv('DISCORD_CLIENT_SECRET');

$def = require __DIR__ . '/../SharedUI/Sites/SWUDeck/SiteDef.php';
$login = RenderLoginPage($def, '/TCGEngine/Stats/Decks.php');
$signup = RenderSignup($def, '/TCGEngine/Stats/Decks.php');
discordAuthCheck('login renders Discord action', strpos($login, 'Continue with Discord') !== false && strpos($login, 'action=login') !== false);
discordAuthCheck('login links to signup', strpos($login, '/Signup.php?redirect=') !== false && strpos($login, 'Create account') !== false);
discordAuthCheck('signup renders Discord action', strpos($signup, 'Continue with Discord') !== false && strpos($signup, 'action=signup') !== false);
discordAuthCheck('Discord return is preserved', strpos($signup, rawurlencode('/TCGEngine/Stats/Decks.php')) !== false);

$withoutDiscord = $def;
unset($withoutDiscord['profile']['discordOAuth']);
discordAuthCheck('site can disable Discord', strpos(RenderLoginPage($withoutDiscord), 'Continue with Discord') === false);

$_GET['oauth_error'] = '<unsafe>';
discordAuthCheck('OAuth error is escaped', strpos(RenderLoginPage($def), '&lt;unsafe&gt;') !== false);
unset($_GET['oauth_error']);

echo "PASS=$pass FAIL=$fail\n";
exit($fail === 0 ? 0 : 1);
