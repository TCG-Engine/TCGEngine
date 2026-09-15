$ErrorActionPreference='Stop'
function Invoke-MonLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$cards=@()
foreach($name in @('herald_of_erudition','herald_of_protection','herald_of_ravages','herald_of_rebirth','herald_of_tenacity','herald_of_triumph','herald_of_judgment')){
    # Erudition and Judgment are yellow-only; the common Heralds have three pitches.
    if($name -in @('herald_of_erudition','herald_of_judgment')){continue}
    foreach($pitch in @('red','yellow','blue')){$cards+=@("${name}_${pitch}","${name}_${pitch}")}
}
foreach($id in @('herald_of_erudition_yellow','herald_of_judgment_yellow','wartune_herald_red','wartune_herald_yellow','wartune_herald_blue')){$cards+=@($id,$id)}
$deck=@{hero='prism';weapons=@('luminaris');equipment=@('halo_of_illumination','vestige_of_sol','dream_weavers','phantasmal_footsteps');mainDeck=$cards}|ConvertTo-Json -Compress
$room=Invoke-MonLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$room.success){throw $room.message}
$auth=@{lobbyID=$room.lobbyID;playerID=$room.playerID;authKey=$room.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-MonLobby 'AddBot' ($auth+@{seat=$seat;botProfile='ira'})
    if(!$added.success){throw $added.message}
}
$upf=Invoke-MonLobby 'StartRoom' $auth
if(!$upf.success){throw $upf.message}
$duel=Invoke-MonLobby 'JoinQueue' @{format='bot';botProfile='ira';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw $duel.message}
foreach($test in @(@{game=$upf;key=$room.authKey;seats=4},@{game=$duel;key=$duel.authKey;seats=2})){
    $uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($test.game.gameName)&playerID=1&authKey=$($test.key)&lastUpdate=-1"
    $frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
    if($frame -match '(?i)(<b>Warning|<b>Fatal error)' -or $frame -notmatch 'prism' -or $frame -notmatch 'luminaris'){throw 'MON board did not render cleanly'}
    $state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($test.game.gameName)/Gamestate.txt" -Raw
    if($state -notmatch 'wartune_herald'){throw 'MON deck was not imported'}
    Write-Output "$($test.seats)-player MON lobby and game response passed. Game $($test.game.gameName)."
}
