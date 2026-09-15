$ErrorActionPreference='Stop'
function Invoke-ArakniLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/ArakniDeck.json" -Raw
$hostSeat=Invoke-ArakniLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-ArakniLobby 'AddBot' ($auth+@{seat=$seat;botProfile='arakni'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-ArakniLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.arakni -or @($roster.roster|Where-Object botProfile -eq 'arakni').Count -ne 3){throw 'Arakni not selectable in lobby'}
$game=Invoke-ArakniLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"arakni","3":"arakni","4":"arakni"\}'){throw 'Arakni profiles not persisted to the assigned seats'}
Write-Output "Arakni UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-ArakniLobby 'JoinQueue' @{format='bot';botProfile='arakni';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Arakni duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'arakni' -or $frame -notmatch 'spiders_bite' -or $frame -notmatch 'starting_point'){throw 'Arakni duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"arakni"\}'){throw 'Duel did not select Arakni'}
Write-Output "Arakni main-menu duel route passed. Game $($duel.gameName)."

