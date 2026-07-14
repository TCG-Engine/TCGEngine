<?php
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../../AccountFiles/AccountDatabaseAPI.php';
require_once __DIR__ . '/../../AccountFiles/DiscordOAuth.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';

// --- Change Password (form + handler script live at different positions, both gated on 'password') ---
function _ProfilePassword(): string {
    return <<<HTML
<div class="container bg-black" style="margin-bottom:30px;">
    <h2>Change Your Password</h2>
    <form id="selfResetPasswordForm" onsubmit="return false;">
        <label>New Password: <input type="password" id="selfNewPassword" required></label><br>
        <label>Confirm Password: <input type="password" id="selfConfirmPassword" required></label><br>
        <button id="selfResetPasswordBtn">Change Password</button>
    </form>
    <div id="selfResetPasswordResult" style="margin-top:10px;"></div>
</div>

HTML;
}

function _ProfilePasswordScript(): string {
    return <<<HTML
<script>
document.getElementById('selfResetPasswordBtn').onclick = function() {
    var newPassword = document.getElementById('selfNewPassword').value;
    var confirmPassword = document.getElementById('selfConfirmPassword').value;
    if (!newPassword || !confirmPassword) {
        document.getElementById('selfResetPasswordResult').innerText = 'Please fill out both fields.';
        return;
    }
    if (newPassword !== confirmPassword) {
        document.getElementById('selfResetPasswordResult').innerText = 'Passwords do not match.';
        return;
    }
    document.getElementById('selfResetPasswordResult').innerText = 'Processing...';
    var params = 'newPass=' + encodeURIComponent(newPassword);
    var url = 'https://www.swustats.net/TCGEngine/APIs/ResetPassword.php?' + params;
    fetch(url, {
        method: 'GET'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('selfResetPasswordResult').innerText = data.message;
        } else {
            document.getElementById('selfResetPasswordResult').innerText = data.error || 'Unknown error.';
        }
    })
    .catch(e => {
        document.getElementById('selfResetPasswordResult').innerText = 'Request failed.';
    });
};
</script>

HTML;
}

// --- Team Management (verbatim from Sites/SWUDeck/Profile.php:68-177; DB logic unchanged) ---
function _ProfileTeam(array $userData): string {
    ob_start();
    ?>
<div class="team-management container bg-black">
    <h2>Team Management</h2>

        <?php if (empty($userData['teamID'])): ?>
            <!-- Form for creating a new team -->
            <form method="post" action="/TCGEngine/AccountFiles/CreateTeam.php">
                <div>
                    <label for="teamName">Create New Team:</label>
                    <input type="text" name="teamName" id="teamName" required maxlength="64">
                    <button type="submit">Create Team</button>
                </div>
            </form>
        <?php else:
                echo("<h3>Your Team: " . $userData['team']['teamName'] . "</h3>");
            ?>
            <!-- Invite Button and Leave Button -->
            <div id="flashMessage" style="display:none; position:absolute; top:80px; right:80px; background:#283C63; color:white; padding:10px 20px; border-radius:5px; z-index:9999; box-shadow: 0 0 10px rgba(40,60,99,0.8);">
                Invite sent!
            </div>
            <form method="post" action="/TCGEngine/AccountFiles/InviteToTeam.php" id="inviteForm">
                <div>
                    <label for="username">Invite Member (Username):</label>
                    <input type="text" name="inviteeName" id="inviteeName" required>
                    <button type="submit" class="btn btn-primary">Send Invite</button>
                </div>
            </form>
            <script>
            document.getElementById('inviteForm').addEventListener('submit', function(e) {
                e.preventDefault();

                var flash = document.getElementById('flashMessage');
                flash.style.display = 'block';

                setTimeout(function(){
                    document.getElementById('inviteForm').submit();
                }, 1000);
            });
            </script>
            <?php

            // Display outstanding team invites (for the team admin)
            $teamID = $userData['teamID'];

            // Get a database connection using your ConnectionManager
            $conn = GetLocalMySQLConnection();

            // Prepare a statement to fetch invites for the team, joining with the users table to retrieve invitee details.
            $sql = "SELECT i.inviteID, i.teamID, i.userID, i.invitedBy, i.inviteTime, u.usersUid AS invitedUsername
                FROM teaminvite AS i
                JOIN users AS u ON i.userID = u.usersId
                WHERE i.teamID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $teamID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<div class='outstanding-invites' style='margin-top:20px;'>";
                echo "<h3>Outstanding Team Invites</h3>";
                while ($row = $result->fetch_assoc()) {
                    echo "User " . htmlspecialchars($row['invitedUsername']) . " invited on " . htmlspecialchars($row['inviteTime']) . "<BR>";
                }
                echo "</div>";
            } else {
                echo "<div class='outstanding-invites' style='margin-top:20px;'>";
                echo "<h3>No Outstanding Team Invites.</h3>";
                echo "</div>";
            }
            echo("<BR>");

            $stmt->close();
            $conn->close();
            ?>
            <form method="post" action="/TCGEngine/AccountFiles/LeaveTeam.php">
                <button type="submit" class="btn btn-danger">Leave Team</button>
            </form>
        <?php endif; ?>  <!-- List team invitations -->
        <div class="team-invites" style="margin-top: 20px; display: <?php echo(empty($userData['teamID']) || count($userData['teamInvites']) > 0 ? 'inline-block' : 'none'); ?>">
            <h3>Your Team Invitations</h3>
            <?php
                $invites = $userData['teamInvites'];
                if (!empty($invites) && is_array($invites)) {
                    echo "<ul>";
                    foreach ($invites as $invite) {
                        echo "<li>";
                        $teamID    = isset($invite['teamID']) ? htmlspecialchars($invite['teamID']) : '';
                        $inviteTime = isset($invite['inviteTime']) ? htmlspecialchars($invite['inviteTime']) : '';
                        $invitedBy = isset($invite['invitedBy']) ? htmlspecialchars($invite['invitedBy']) : '';
                        $inviteID  = isset($invite['inviteID']) ? urlencode($invite['inviteID']) : '';
                        echo("Invited to team " . $invite['teamName'] . " by " . $invite['invitedByUserUid'] . " &nbsp;");
                        echo '<form method="post" action="/TCGEngine/AccountFiles/ProcessTeamInvitation.php" style="display:inline;">';
                        echo '    <input type="hidden" name="invitationID" value="' . $inviteID . '">';
                        echo '    <input type="hidden" name="mode" value="accept">';
                        echo '    <button type="submit">Accept</button>';
                        echo '</form>';
                        echo '<form method="post" action="/TCGEngine/AccountFiles/ProcessTeamInvitation.php" style="display:inline;">';
                        echo '    <input type="hidden" name="invitationID" value="' . $inviteID . '">';
                        echo '    <input type="hidden" name="mode" value="decline">';
                        echo '    <button type="submit">Decline</button>';
                        echo '</form>';

                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "No team invitations at this time.";
                }
            ?>
        </div>
</div>
    <?php
    return ob_get_clean();
}

// --- Developer Options (parameterized app label) ---
function _ProfileOAuthDev(array $def): string {
    $label = $def['profile']['oauthAppLabel'];
    return "<div class=\"oauth-management container bg-black\">\n"
         . "    <h2>Developer Options</h2>\n"
         . "    <p>Create OAuth applications to allow other websites to connect to $label.</p>\n"
         . "    <a href=\"/TCGEngine/APIs/OAuth/manage_clients.php\" class=\"btn btn-primary\" style=\"display:inline-block; margin-top:10px; padding:8px 16px; background-color:#007bff; color:#fff; text-decoration:none; border-radius:4px;\">Manage OAuth Applications</a>\n"
         . "</div>";
}

// --- Patreon connect (verbatim from Profile.php:219-243; final_page parameterized) ---
function _DisplayPatreon(array $def): string {
    global $patreonClientID, $patreonClientSecret;
    $client_id = $patreonClientID;
    $client_secret = $patreonClientSecret;

    $redirect_uri = "https://www.swustats.net/TCGEngine/APIs/PatreonLogin.php";
    $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($redirect_uri);
    $state = array();
    $state['usersId'] = $_SESSION['userid'];
    $state['final_page'] = $def['profile']['patreonFinalPage'];
    $state_parameters = '&state=' . urlencode(json_encode($state));
    $href .= $state_parameters;
    $scope_parameters = '&scope=identity%20identity.memberships';
    $href .= $scope_parameters;

    ob_start();
    if (!isset($_SESSION["patreonAuthenticated"])) {
        echo '<a class="containerPatreon" href="' . $href . '">';
        echo ("<img class='imgPatreon' src='/TCGEngine/Assets/patreon-php-master/assets/images/login_with_patreon.png' alt='Login via Patreon'>");
        echo '</a>';
    } else {
        include './Patreons.php';
        echo '<a href="/TCGEngine/AccountFiles/DisconnectOAuth.php?type=patreon" class="btn btn-secondary" style="margin-top:10px;">Disconnect Patreon</a>';
        echo("<BR><BR>");
    }
    return ob_get_clean();
}

// --- Discord connect (verbatim from Profile.php:245-292; clientID parameterized) ---
function _DisplayDiscordOAuth(array $def): string {
    $site = $def['identity']['rootName'] ?? 'SWUDeck';
    $profileReturn = '/TCGEngine/SharedUI/Sites/' . $site . '/Profile.php';
    $authUrl = DiscordOAuthStartUrl('link', $site, $profileReturn);

    ob_start();
    if(!isset($_SESSION["discordID"]) || $_SESSION["discordID"] == "") {
        echo '<style>
        .discord-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        .discord-button:hover {
            background-color: #0056b3;
        }
        </style>';
            echo '<a class="discord-button" href="' . htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8') . '">';
            echo '<img src="/TCGEngine/Assets/Images/icons/discord.svg" alt="Discord" style="height:20px; width:auto; vertical-align:middle; margin-right:8px;">';
            echo 'Connect Discord';
            echo '</a>';
    } else {
        echo '<div class="container bg-black" style="margin-top: 20px;">';
        echo '<h3>Discord Account</h3>';
        echo '<p>Connected to Discord</p>';
        $disconnectUrl = '/TCGEngine/AccountFiles/DisconnectOAuth.php?' . http_build_query(['type' => 'discord', 'redirect' => $profileReturn]);
        echo '<a href="' . htmlspecialchars($disconnectUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-secondary" style="margin-top:10px;">Disconnect Discord</a>';
        echo '</div>';
    }
    return ob_get_clean();
}

// The "Welcome {user}!" panel: greeting + Patreon (when configured) + Discord (when configured).
function _ProfileWelcome(array $def, array $ctx): string {
    $out = "<div class='fav-decks container bg-black'>\n<h2>Welcome " . ($ctx['username'] ?? '') . "!</h2>\n    ";
    $parts = [];
    if (!empty($def['profile']['patreonFinalPage'])) $parts[] = _DisplayPatreon($def);
    if (!empty($def['profile']['discordOAuth']))      $parts[] = _DisplayDiscordOAuth($def);
    $out .= implode("<hr style='border:0;border-top:1px solid rgba(255,255,255,0.14);margin:18px 0;'>", $parts);
    $out .= "\n\n</div>\n";
    return $out;
}

// Ordered panel registry: key => fn($def, $ctx, $userData): string. RenderProfile renders the
// site's profile.sections in declared order by looking each key up here.
function _ProfilePanelRegistry(): array {
    return [
        'changePassword'   => function($def, $ctx, $ud) { return _ProfilePassword() . _ProfilePasswordScript(); },
        'welcome'          => function($def, $ctx, $ud) { return _ProfileWelcome($def, $ctx); },
        'team'             => function($def, $ctx, $ud) { return _ProfileTeam($ud); },
        'developerOptions' => function($def, $ctx, $ud) { return _ProfileOAuthDev($def); },
        'savedDecks'       => function($def, $ctx, $ud) {
            require_once __DIR__ . '/DeckLibrary.php';
            $cfg = DeckLibraryConfigFromSiteDef($def, ['actionButtons' => true]);
            return "<div class='savedDecks container bg-black'><h2>Saved Decks</h2>"
                 . RenderDeckLibrary((int)($ctx['userId'] ?? 0), $cfg) . "</div>";
        },
        'cosmetics'        => function($def, $ctx, $ud) {
            require_once __DIR__ . '/CosmeticsChooser.php';
            return "<div class='cosmetics container bg-black'><h2>Cosmetics</h2>"
                 . RenderCosmeticsChooser((int)($ctx['userId'] ?? 0)) . "</div>";
        },
        'blockedUsers'     => function($def, $ctx, $ud) {
            require_once __DIR__ . '/BlockedUsers.php';
            return "<div class='blockedUsers container bg-black'><h2>Blocked Users</h2>"
                 . RenderBlockedUsers((int)($ctx['userId'] ?? 0)) . "</div>";
        },
    ];
}

// CSS so two panels combined with '+' merge into a single pane (one bordered box) with a divider,
// instead of showing as two nested boxes. Injected once by RenderProfile; app-agnostic.
function _ProfilePaneStyle(): string {
    // The pane is the single box (keeps .container's own padding); the two inner panels contribute
    // only their content — no box chrome, no padding — so a+b read as ONE container, not two cards.
    return "<style>\n"
         // Even column gapping on the profile: let the flex row own the spacing (gap + edge padding)
         // instead of relying on each panel's own inconsistent side margins.
         // stretch = every column matches the tallest column; height:auto so 'tallest' is the tallest
         // column's content (not the full viewport).
         . ".core-wrapper { gap: 20px; padding: 20px; box-sizing: border-box; align-items: stretch; height: auto; }\n"
         . ".core-wrapper > .container, .core-wrapper > .profile-pane { margin: 0 !important; }\n"
         . ".profile-pane { overflow: hidden; }\n"
         . ".profile-pane > .container { background: transparent !important; backdrop-filter: none !important;"
         . " -webkit-backdrop-filter: none !important; border: 0 !important; border-radius: 0 !important;"
         . " box-shadow: none !important; margin: 0 !important; padding: 0 !important; }\n"
         . ".profile-pane > .profile-pane-sep { border: 0; border-top: 1px solid rgba(255,255,255,0.14); margin: 18px 0; }\n"
         . "</style>\n";
}

function RenderProfile(array $def, array $ctx, array $userData): string {
    $sections = $def['profile']['sections'] ?? [];
    $registry = _ProfilePanelRegistry();
    $out  = "<div id=\"cardDetail\" style=\"z-index:100000; display:none; position:fixed;\"></div>\n\n\n\n";
    $out .= _ProfilePaneStyle();
    $out .= "<div class=\"core-wrapper\">\n\n";
    foreach ($sections as $entry) {
        // An entry may combine up to 2 panels with '+' → one pane with a divider between them.
        $keys = array_slice(array_values(array_filter(array_map('trim', explode('+', (string)$entry)))), 0, 2);
        $parts = [];
        foreach ($keys as $key) {
            if (isset($registry[$key])) $parts[] = $registry[$key]($def, $ctx, $userData);
        }
        if (count($parts) === 0) continue;
        if (count($parts) === 1) { $out .= $parts[0]; continue; }
        $out .= "<div class='profile-pane container bg-black'>" . $parts[0]
              . "<div class='profile-pane-sep'></div>" . $parts[1] . "</div>";
    }
    // NOTE: core-wrapper is intentionally left open here to match the original Profile.php structure.
    return $out;
}

function RenderDisclaimer(array $def): string {
    $name = $def['branding']['disclaimerName'] ?? $def['branding']['title'];
    return "<div class=\"disclaimer\">\n    <p>$name is in no way affiliated with Disney or Fantasy Flight Games. Star Wars characters, cards, logos, and art are property of Disney and/or Fantasy Flight Games.\n        &nbsp;\n        <a href=\"/TCGEngine/SharedUI/TermsOfUse.php\">Terms of Use</a>\n        &nbsp;\n        <a href=\"/TCGEngine/SharedUI/PrivacyPolicy.php\">Privacy Policy</a>\n    </p>\n</div>";
}
