$ErrorActionPreference='Stop'
function Invoke-DTDLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('wartune_herald_red','wartune_herald_yellow','wartune_herald_blue','herald_of_protection_red','herald_of_protection_yellow','herald_of_protection_blue','herald_of_ravages_red','herald_of_ravages_yellow','herald_of_ravages_blue','herald_of_rebirth_red','herald_of_rebirth_yellow','herald_of_rebirth_blue','herald_of_tenacity_red','herald_of_tenacity_yellow','herald_of_tenacity_blue','herald_of_triumph_red','herald_of_triumph_yellow','herald_of_triumph_blue')
$deck=@{hero='prism_advent_of_thrones';weapons=@('luminaris_celestial_fury');equipment=@('empyrean_rapture','diadem_of_dreamstate','radiant_flow','radiant_touch');mainDeck=@($ids+$ids+@('figment_of_protection_yellow','figment_of_erudition_yellow','figment_of_war_yellow','figment_of_tenacity_yellow'))} | ConvertTo-Json -Compress
$hostSeat=Invoke-DTDLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-DTDLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-DTDLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-DTDLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "DTD deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-DTDLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'prism_advent_of_thrones' -or $frame -notmatch 'luminaris_celestial_fury' -or $frame -notmatch 'empyrean_rapture'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "DTD deck main-menu duel route passed. Game $($duel.gameName)."

