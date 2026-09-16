$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-HVYLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'hvy-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "HVY $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$ids=@('wage_might_red','wage_might_yellow','wage_might_blue','wage_gold_red','wage_gold_yellow','wage_gold_blue','clash_of_might_red','clash_of_might_yellow','clash_of_might_blue','pound_town_red','pound_town_yellow','pound_town_blue','assault_and_battery_red','assault_and_battery_yellow','assault_and_battery_blue','rawhide_rumble_red','rawhide_rumble_yellow','rawhide_rumble_blue','cast_bones_red','mighty_windup_blue')
$deck=@{hero='kayo';weapons=@('ball_breaker');equipment=@('knucklehead','raw_meat','apex_bonebreaker','flat_trackers');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-HVYLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-HVYLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-HVYLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-HVYLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "HVY deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-HVYLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'kayo' -or $frame -notmatch 'ball_breaker' -or $frame -notmatch 'raw_meat'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "HVY deck main-menu duel route passed. Game $($duel.gameName)."


