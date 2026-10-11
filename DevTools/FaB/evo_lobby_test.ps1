$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-EVOLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'evo-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "EVO $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$ids=@('zero_to_fifty_red','zero_to_fifty_yellow','zero_to_fifty_blue','full_tilt_red','full_tilt_yellow','full_tilt_blue','big_bertha_red','big_bertha_yellow','big_bertha_blue','gas_guzzler_red','gas_guzzler_yellow','gas_guzzler_blue','twin_drive_red','boom_grenade_red','boom_grenade_yellow','boom_grenade_blue','mini_forcefield_red','mini_forcefield_yellow','mini_forcefield_blue','fuel_injector_blue')
$deck=@{hero='dash_database';weapons=@('symbiosis_shot');equipment=@('cogwerx_base_head','cogwerx_base_chest','cogwerx_base_arms','cogwerx_base_legs');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-EVOLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-EVOLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-EVOLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-EVOLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "EVO deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-EVOLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'dash_database' -or $frame -notmatch 'symbiosis_shot' -or $frame -notmatch 'cogwerx_base_chest'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "EVO deck main-menu duel route passed. Game $($duel.gameName)."


