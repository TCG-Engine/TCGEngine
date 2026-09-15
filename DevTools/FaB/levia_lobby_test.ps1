$ErrorActionPreference='Stop'
function Invoke-LeviaLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/LeviaDeck.json" -Raw
$hostSeat=Invoke-LeviaLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-LeviaLobby 'AddBot' ($auth+@{seat=$seat;botProfile='levia'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-LeviaLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.levia -or @($roster.roster|Where-Object botProfile -eq 'levia').Count -ne 3){throw 'Levia not selectable in lobby'}
$game=Invoke-LeviaLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"levia","3":"levia","4":"levia"\}'){throw 'Levia profiles not persisted to the assigned seats'}
Write-Output "Levia UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-LeviaLobby 'JoinQueue' @{format='bot';botProfile='levia';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Levia duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'levia' -or $frame -notmatch 'ravenous_meataxe' -or $frame -notmatch 'spell_fray_cloak'){throw 'Levia duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"levia"\}'){throw 'Duel did not select Levia'}
Write-Output "Levia main-menu duel route passed. Game $($duel.gameName)."

