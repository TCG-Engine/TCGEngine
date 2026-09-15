$ErrorActionPreference='Stop'
function Invoke-UzuriLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/UzuriDeck.json" -Raw
$hostSeat=Invoke-UzuriLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-UzuriLobby 'AddBot' ($auth+@{seat=$seat;botProfile='uzuri'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-UzuriLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.uzuri -or @($roster.roster|Where-Object botProfile -eq 'uzuri').Count -ne 3){throw 'Uzuri not selectable in lobby'}
$game=Invoke-UzuriLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"uzuri","3":"uzuri","4":"uzuri"\}'){throw 'Uzuri profiles not persisted to the assigned seats'}
Write-Output "Uzuri UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-UzuriLobby 'JoinQueue' @{format='bot';botProfile='uzuri';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Uzuri duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'uzuri' -or $frame -notmatch 'spiders_bite' -or $frame -notmatch 'mask_of_shifting_perspectives'){throw 'Uzuri duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"uzuri"\}'){throw 'Duel did not select Uzuri'}
Write-Output "Uzuri main-menu duel route passed. Game $($duel.gameName)."

