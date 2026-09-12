$ErrorActionPreference='Stop'
function Invoke-IraLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/IraDeck.json" -Raw
$hostSeat=Invoke-IraLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-IraLobby 'AddBot' ($auth+@{seat=$seat;botProfile='ira'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-IraLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.ira -or @($roster.roster|Where-Object botProfile -eq 'ira').Count -ne 3){throw 'Ira not selectable in lobby'}
$game=Invoke-IraLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"ira","3":"ira","4":"ira"\}'){throw 'Ira profiles not persisted to the assigned seats'}
Write-Output "Ira UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-IraLobby 'JoinQueue' @{format='bot';botProfile='ira';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Ira duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'ira_crimson_haze' -or $frame -notmatch 'edge_of_autumn' -or $frame -notmatch 'blood_scent'){throw 'Ira duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"ira"\}'){throw 'Duel did not select Ira'}
Write-Output "Ira main-menu duel route passed. Game $($duel.gameName)."

