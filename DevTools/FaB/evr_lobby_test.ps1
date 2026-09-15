$ErrorActionPreference='Stop'
function Invoke-EVRLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('pulverize_red','thunder_quake_red','thunder_quake_blue','macho_grande_red','macho_grande_blue','seismic_stir_red','seismic_stir_blue','steadfast_red','steadfast_blue','imposing_visage_blue','bingo_red','life_of_the_party_red','life_of_the_party_blue','smashing_good_time_red','smashing_good_time_blue','high_striker_red','high_striker_blue','healing_potion_blue','amulet_of_intervention_blue','this_rounds_on_me_blue')
$deck=@{hero='valda_brightaxe';weapons=@('anothos');equipment=@('earthlore_bounty');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-EVRLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-EVRLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-EVRLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-EVRLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "EVR deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-EVRLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'valda_brightaxe' -or $frame -notmatch 'anothos' -or $frame -notmatch 'earthlore_bounty'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "EVR deck main-menu duel route passed. Game $($duel.gameName)."

