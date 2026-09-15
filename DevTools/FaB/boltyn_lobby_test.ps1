$ErrorActionPreference='Stop'
function Invoke-BoltynLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/BoltynDeck.json" -Raw
$hostSeat=Invoke-BoltynLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-BoltynLobby 'AddBot' ($auth+@{seat=$seat;botProfile='boltyn'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-BoltynLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.boltyn -or @($roster.roster|Where-Object botProfile -eq 'boltyn').Count -ne 3){throw 'Boltyn not selectable in lobby'}
$game=Invoke-BoltynLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"boltyn","3":"boltyn","4":"boltyn"\}'){throw 'Boltyn profiles not persisted to the assigned seats'}
Write-Output "Boltyn UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-BoltynLobby 'JoinQueue' @{format='bot';botProfile='boltyn';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Boltyn duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'boltyn' -or $frame -notmatch 'raydn_duskbane' -or $frame -notmatch 'helm_of_unity'){throw 'Boltyn duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"boltyn"\}'){throw 'Duel did not select Boltyn'}
Write-Output "Boltyn main-menu duel route passed. Game $($duel.gameName)."

