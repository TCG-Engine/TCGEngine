$ErrorActionPreference='Stop'
function Invoke-DromaiLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/DromaiDeck.json" -Raw
$hostSeat=Invoke-DromaiLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-DromaiLobby 'AddBot' ($auth+@{seat=$seat;botProfile='dromai'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-DromaiLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.dromai -or @($roster.roster|Where-Object botProfile -eq 'dromai').Count -ne 3){throw 'Dromai not selectable in lobby'}
$game=Invoke-DromaiLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"dromai","3":"dromai","4":"dromai"\}'){throw 'Dromai profiles not persisted to the assigned seats'}
Write-Output "Dromai UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-DromaiLobby 'JoinQueue' @{format='bot';botProfile='dromai';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Dromai duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'dromai' -or $frame -notmatch 'storm_of_sandikai' -or $frame -notmatch 'silken_form'){throw 'Dromai duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"dromai"\}'){throw 'Duel did not select Dromai'}
Write-Output "Dromai main-menu duel route passed. Game $($duel.gameName)."

