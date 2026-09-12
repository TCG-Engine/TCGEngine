$ErrorActionPreference='Stop'
function Invoke-ProfessorLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/ProfessorDeck.json" -Raw
$hostSeat=Invoke-ProfessorLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-ProfessorLobby 'AddBot' ($auth+@{seat=$seat;botProfile='professor'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-ProfessorLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.professor -or @($roster.roster|Where-Object botProfile -eq 'professor').Count -ne 3){throw 'Professor not selectable in lobby'}
$game=Invoke-ProfessorLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"professor","3":"professor","4":"professor"\}'){throw 'Professor profiles not persisted to the assigned seats'}
Write-Output "Professor UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-ProfessorLobby 'JoinQueue' @{format='bot';botProfile='professor';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Professor duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest $uri).Content
if($frame -notmatch 'professor_teklovossen' -or $frame -notmatch 'teklo_blaster' -or $frame -notmatch 'proto_base_chest'){throw 'Professor duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"professor"\}'){throw 'Duel did not select Professor'}
Write-Output "Professor main-menu duel route passed. Game $($duel.gameName)."
