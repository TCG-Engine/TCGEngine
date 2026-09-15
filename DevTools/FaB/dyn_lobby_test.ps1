$ErrorActionPreference='Stop'
function Invoke-DYNLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$ids=@('annihilate_the_armed_red','annihilate_the_armed_blue','fleece_the_frail_red','fleece_the_frail_blue','nix_the_nimble_red','nix_the_nimble_blue','plunder_the_poor_red','plunder_the_poor_blue','rob_the_rich_red','rob_the_rich_blue','sack_the_shifty_red','sack_the_shifty_blue','slay_the_scholars_red','slay_the_scholars_blue','shred_red','shred_blue','cut_to_the_chase_red','cut_to_the_chase_blue','eradicate_yellow','leave_no_witnesses_red')
$deck=@{hero='arakni';weapons=@('spiders_bite','spiders_bite');equipment=@('mask_of_perdition','blacktek_whisperers');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-DYNLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-DYNLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-DYNLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-DYNLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "DYN deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-DYNLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'arakni' -or $frame -notmatch 'spiders_bite' -or $frame -notmatch 'mask_of_perdition'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "DYN deck main-menu duel route passed. Game $($duel.gameName)."

