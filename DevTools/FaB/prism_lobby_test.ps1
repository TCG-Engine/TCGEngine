$ErrorActionPreference='Stop'
function Invoke-PrismLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/PrismDeck.json" -Raw
$hostSeat=Invoke-PrismLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-PrismLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-PrismLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-PrismLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "Prism UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-PrismLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'prism' -or $frame -notmatch 'iris_of_reality' -or $frame -notmatch 'spell_fray_leggings'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "Prism main-menu duel route passed. Game $($duel.gameName)."

