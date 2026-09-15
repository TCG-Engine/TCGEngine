$ErrorActionPreference='Stop'
function Invoke-UPRLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('sweeping_blow_red','sweeping_blow_blue','invoke_yendurai_red','invoke_cromai_red','invoke_azvolai_red','invoke_kyloria_red','rake_the_embers_red','rake_the_embers_blue','billowing_mirage_red','billowing_mirage_blue','dustup_red','dustup_blue','skittering_sands_red','skittering_sands_blue','embermaw_cenipai_red','embermaw_cenipai_blue','dunebreaker_cenipai_red','dunebreaker_cenipai_blue','sand_cover_red','sand_cover_blue')
$deck=@{hero='dromai';weapons=@('storm_of_sandikai');equipment=@('flamescale_furnace');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-UPRLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-UPRLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-UPRLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-UPRLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "UPR deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-UPRLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'dromai' -or $frame -notmatch 'storm_of_sandikai' -or $frame -notmatch 'flamescale_furnace'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "UPR deck main-menu duel route passed. Game $($duel.gameName)."

