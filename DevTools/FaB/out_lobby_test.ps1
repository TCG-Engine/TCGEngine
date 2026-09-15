$ErrorActionPreference='Stop'
function Invoke-OUTLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('infecting_shot_red','infecting_shot_blue','withering_shot_red','withering_shot_blue','sedation_shot_red','sedation_shot_blue','falcon_wing_red','falcon_wing_blue','skybound_shot_red','skybound_shot_blue','murkmire_grapnel_red','murkmire_grapnel_blue','spire_sniping_red','spire_sniping_blue','bloodrot_trap_red','frailty_trap_red','inertia_trap_red','boulder_trap_yellow','pendulum_trap_yellow','tarpit_trap_yellow')
$deck=@{hero='riptide';weapons=@('barbed_castaway');equipment=@('wayfinders_crest','trench_of_sunken_treasure','toxic_tips','driftwood_quiver');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-OUTLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-OUTLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-OUTLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-OUTLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "OUT deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-OUTLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'riptide' -or $frame -notmatch 'barbed_castaway' -or $frame -notmatch 'driftwood_quiver'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "OUT deck main-menu duel route passed. Game $($duel.gameName)."

