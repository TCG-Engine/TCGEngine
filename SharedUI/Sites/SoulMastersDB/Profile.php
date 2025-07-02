
<?php
include_once "MenuBar.php";
include_once "../AccountFiles/AccountSessionAPI.php";
include_once "../AccountFiles/AccountDatabaseAPI.php";
include_once '../Assets/patreon-php-master/src/OAuth.php';
include_once '../Assets/patreon-php-master/src/API.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once "../Database/ConnectionManager.php";
include_once "../APIKeys/APIKeys.php";

if (!IsUserLoggedIn()) {
    header('Location: ./MainMenu.php');
    die();
}

include_once "../APIKeys/APIKeys.php";

  /*
  $badges = LoadBadges($_SESSION['userid']);
  echo ("<div class='ContentWindow' style='position:relative; width:50%; left:20px; top:20px; height:200px;'>");
  echo ("<h1>Your Badges</h1>");
  for ($i = 0; $i < count($badges); $i += 7) {
    $bottomText = str_replace("{0}", $badges[$i + 2], $badges[$i + 4]);
    $fullText = $badges[$i + 3] . "<br><br>" . $bottomText;
    if ($badges[$i + 6] != "") echo ("<a href='" . $badges[$i + 6] . "'>");
    echo ("<img style='margin:3px; width:120px; height:120px; object-fit: cover;' src='" . $badges[$i + 5] . "'></img>");
    if ($badges[$i + 6] != "") echo ("</a>");
  }
  echo ("</div>");
  */

include_once 'Header.php';

$userData = LoadUserDataFromId(LoggedInUser());
?>



<div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>

<div class="core-wrapper">

<div class='fav-decks container bg-black'>
<h2>Welcome <?php echo $_SESSION['useruid'] ?>!</h2>
    <?php
        //include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
        //PatreonLogin($access_token, false);
        //DisplayPatreon();

        DisplayDiscordOAuth();

    ?>

</div>
<div class="team-management container bg-black">
    <h2>Team Management</h2>
        
        <?php if (empty($userData['teamID'])): ?>
            <!-- Form for creating a new team -->
            <form method="post" action="../AccountFiles/CreateTeam.php">
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
            <form method="post" action="../AccountFiles/InviteToTeam.php" id="inviteForm">
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
            <form method="post" action="../AccountFiles/LeaveTeam.php">
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
                        echo '<form method="post" action="../AccountFiles/ProcessTeamInvitation.php" style="display:inline;">';
                        echo '    <input type="hidden" name="invitationID" value="' . $inviteID . '">';
                        echo '    <input type="hidden" name="mode" value="accept">';
                        echo '    <button type="submit">Accept</button>';
                        echo '</form>';
                        echo '<form method="post" action="../AccountFiles/ProcessTeamInvitation.php" style="display:inline;">';
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

function DisplayPatreon() {
    global $patreonClientID, $patreonClientSecret;
    $client_id = $patreonClientID;
    $client_secret = $patreonClientSecret;

    $redirect_uri = "https://www.swustats.net/TCGEngine/APIs/PatreonLogin.php";
    $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($redirect_uri);
    $state = array();
    $state['usersId'] = $_SESSION['userid'];
    $state['final_page'] = 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php';
    $state_parameters = '&state=' . urlencode(json_encode($state));
    $href .= $state_parameters;
    $scope_parameters = '&scope=identity%20identity.memberships';
    $href .= $scope_parameters;

    if (!isset($_SESSION["patreonAuthenticated"])) {
        echo '<a class="containerPatreon" href="' . $href . '">';
        echo ("<img class='imgPatreon' src='../Assets/patreon-php-master/assets/images/login_with_patreon.png' alt='Login via Patreon'>");
        echo '</a>';
    } else {
        include './Patreons.php';
        echo '<a href="../AccountFiles/DisconnectOAuth.php?type=patreon" class="btn btn-secondary" style="margin-top:10px;">Disconnect Patreon</a>';
        echo("<BR><BR>");
    }
}

function DisplayDiscordOAuth() {
    global $discordClientID, $discordRedirectURI;
    // Replace with your actual Discord credentials and redirect URI.
    if(!isset($discordClientID)) $discordClientID = "1338995198730043432";
    if(!isset($discordRedirectURI)) $discordRedirectURI = "https://www.swustats.net/TCGEngine/APIs/DiscordLogin.php";
    $discordScope = "identify email";
    
    // Optional state for security.
    $state = array(
        "userId" => $_SESSION['userid'],
        "action" => "discord_oauth"
    );
    $stateParam = urlencode(json_encode($state));

    $authUrl = "https://discord.com/api/oauth2/authorize?client_id={$discordClientID}"
                . "&redirect_uri=" . urlencode($discordRedirectURI)
                . "&response_type=code"
                . "&scope=" . urlencode($discordScope)
                . "&state={$stateParam}";

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
            echo '<a class="discord-button" href="' . $authUrl . '">';
            echo '<img src="../Assets/Images/icons/discord.svg" alt="Discord" style="height:20px; width:auto; vertical-align:middle; margin-right:8px;">';
            echo 'Login via Discord';
            echo '</a>';
    } else {
        echo '<div class="container bg-black" style="margin-top: 20px;">';
        echo '<h3>Discord Account</h3>';
        echo '<p>Connected to Discord</p>';
        echo '<a href="../AccountFiles/DisconnectOAuth.php?type=discord" class="btn btn-secondary" style="margin-top:10px;">Disconnect Discord</a>';
        echo '</div>';
    }
}

require "Disclaimer.php";

?>
