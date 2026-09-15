$ErrorActionPreference='Stop'
function Invoke-LexiLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/LexiDeck.json" -Raw
$hostSeat=Invoke-LexiLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-LexiLobby 'AddBot' ($auth+@{seat=$seat;botProfile='lexi'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-LexiLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.lexi -or @($roster.roster|Where-Object botProfile -eq 'lexi').Count -ne 3){throw 'Lexi not selectable in lobby'}
$game=Invoke-LexiLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"lexi","3":"lexi","4":"lexi"\}'){throw 'Lexi profiles not persisted to the assigned seats'}
Write-Output "Lexi UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-LexiLobby 'JoinQueue' @{format='bot';botProfile='lexi';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Lexi duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'lexi' -or $frame -notmatch 'shiver' -or $frame -notmatch 'honing_hood'){throw 'Lexi duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"lexi"\}'){throw 'Duel did not select Lexi'}
Write-Output "Lexi main-menu duel route passed. Game $($duel.gameName)."

