$ErrorActionPreference = 'Stop'
$lines = @('Hero','1 Rhinar','Weapons','1 Romping Club','Deck')
foreach ($name in @('Wounding Blow','Brutal Assault','Raging Onslaught','Wounded Bull','Scar for a Scar','Snatch')) {
    foreach ($color in @('Red','Yellow','Blue')) { $lines += "2 $name ($color)" }
}
$lines += '2 Sigil of Solace (Red)', '2 Sigil of Solace (Blue)'
function CallBotLobby($endpoint, $fields) {
    $fields.rootName = 'FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$hostSeat = CallBotLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=($lines -join "`n")}
if (!$hostSeat.success) { throw $hostSeat.message }
$auth = @{lobbyID=$hostSeat.lobbyID;authKey=$hostSeat.authKey;playerID=$hostSeat.playerID;botProfile='goldfish'}
if ((CallBotLobby 'AddBot' @{lobbyID=$hostSeat.lobbyID;authKey='invalid';botProfile='goldfish'}).success) { throw 'Unauthenticated bot added' }
$auth.seat=4
$added = CallBotLobby 'AddBot' $auth
if (!$added.success) { throw $added.message }
$roster = CallBotLobby 'PollLobbyUpdates' $auth
if (($roster.roster | Where-Object botProfile -eq 'goldfish').seat -ne 4) { throw 'Bot ignored requested slot 4' }
if ((CallBotLobby 'AddBot' $auth).success) { throw 'Occupied slot accepted' }
$guest = CallBotLobby 'JoinQueue' @{privateInviteCode=$hostSeat.inviteCode;deckLink=($lines -join "`n")}
if (!$guest.success) { throw $guest.message }
$auth.seat=3
if (!(CallBotLobby 'AddBot' $auth).success) { throw 'Could not fill slot 3' }
if ((CallBotLobby 'AddBot' $auth).success) { throw 'Overfilled room' }
$roster = CallBotLobby 'PollLobbyUpdates' $auth
if (@($roster.roster | Where-Object botProfile -eq 'goldfish').Count -ne 2 -or $roster.blockers.Count -ne 0) { throw 'Bot roster not ready' }
if (($roster.roster | Where-Object playerID -eq $guest.playerID).seat -ne 2) { throw 'Human did not fill first empty slot' }
$auth.targetPlayerID=2
if (!(CallBotLobby 'KickSeat' $auth).success) { throw 'Could not remove bot' }
$roster = CallBotLobby 'PollLobbyUpdates' $auth
if (@($roster.roster | Where-Object seat -eq 4).Count -ne 0 -or ($roster.roster | Where-Object botProfile -eq 'goldfish').seat -ne 3) { throw 'Removing bot shifted other slots' }
$auth.seat=4
if (!(CallBotLobby 'AddBot' $auth).success) { throw 'Could not refill bot seat' }
$started = CallBotLobby 'StartRoom' $auth
if (!$started.success) { throw $started.message }
if ((CallBotLobby 'AddBot' $auth).success) { throw 'Bot added after start' }
$gameText = Get-Content "$PSScriptRoot/../../FaBSim/Games/$($started.gameName)/Gamestate.txt" -Raw
if ($gameText -notmatch '"gameMode":"UPF"' -or $gameText -notmatch '"passiveSeats":\[3,4\]') { throw 'Incorrect bot state after compaction' }
$guestPoll = CallBotLobby 'PollLobbyUpdates' @{lobbyID=$hostSeat.lobbyID;playerID=$guest.playerID;authKey=$guest.authKey}
if ($guestPoll.playerID -ne 2) { throw 'Human game seat differs from table slot' }
Write-Output "UPF bot lobby integration passed. Game $($started.gameName)."
