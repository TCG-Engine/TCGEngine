$ErrorActionPreference='Stop'
function Invoke-ELELobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('explosive_growth_red','explosive_growth_blue','arcanic_shockwave_red','arcanic_shockwave_blue','rites_of_lightning_red','rites_of_lightning_blue','stir_the_wildwood_red','stir_the_wildwood_blue','bramble_spark_red','bramble_spark_blue','autumns_touch_red','autumns_touch_blue','heavens_claws_red','heavens_claws_blue','lightning_surge_red','lightning_surge_blue','weave_earth_red','weave_earth_blue','weave_lightning_red','weave_lightning_blue')
$deck=@{hero='briar';weapons=@('rosetta_thorn');equipment=@('spellbound_creepers');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-ELELobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-ELELobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-ELELobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-ELELobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "ELE deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-ELELobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'briar' -or $frame -notmatch 'rosetta_thorn' -or $frame -notmatch 'spellbound_creepers'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "ELE deck main-menu duel route passed. Game $($duel.gameName)."

