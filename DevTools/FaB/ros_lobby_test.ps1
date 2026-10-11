$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-ROSLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'ros-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "ROS $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$ids=@('fry_red','fry_yellow','fry_blue','second_strike_red','second_strike_yellow','second_strike_blue','flittering_charge_red','flittering_charge_yellow','flittering_charge_blue','hocus_pocus_red','hocus_pocus_yellow','hocus_pocus_blue','arcanic_spike_red','arcanic_spike_yellow','arcanic_spike_blue','runerager_swarm_red','runerager_swarm_yellow','runerager_swarm_blue','high_voltage_blue','arc_lightning_yellow')
$deck=@{hero='aurora';weapons=@('star_fall');equipment=@('hood_of_second_thoughts','bruised_leather','four_finger_gloves','twinkle_toes');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-ROSLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-ROSLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-ROSLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-ROSLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "ROS deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-ROSLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'aurora' -or $frame -notmatch 'star_fall' -or $frame -notmatch 'bruised_leather'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "ROS deck main-menu duel route passed. Game $($duel.gameName)."


