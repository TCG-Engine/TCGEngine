$ErrorActionPreference='Stop'
function CallFaiLobby($endpoint,$fields) {
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$deck=& C:\xampp\php\php.exe -r 'require "FaBSim/BotDeck.php"; echo json_encode(FaBFaiBotDeck());'
$hostSeat=CallFaiLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;authKey=$hostSeat.authKey;playerID=$hostSeat.playerID}
foreach($seat in @(4,2,3)) {
    $added=CallFaiLobby 'AddBot' ($auth+@{seat=$seat;botProfile='fai'})
    if(!$added.success){throw $added.message}
}
$roster=CallFaiLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.fai -or @($roster.roster|Where-Object botProfile -eq 'fai').Count -ne 3){throw 'Fai missing from lobby'}
$started=CallFaiLobby 'StartRoom' $auth
if(!$started.success){throw $started.message}
$gameText=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($started.gameName)/Gamestate.txt" -Raw
if($gameText -notmatch '"botProfiles":\{"2":"fai","3":"fai","4":"fai"\}'){throw 'Bot seats were not persisted'}
Write-Output "Fai UPF lobby integration passed. Game $($started.gameName)."
