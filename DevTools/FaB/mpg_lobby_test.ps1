$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-MPGLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'mpg-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "MPG $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$ids=@('aftershock_red','aftershock_blue','clash_of_mountains_blue','clash_of_bravado_yellow','test_of_iron_grip_red','pec_perfect_red','little_big_foot_red','grind_them_down_blue','overswing_blue','rubble_raiser_blue','hostile_encroachment_red','tectonic_instability_blue','promising_terrain_blue','geyser_of_seismic_stirrings_blue','seismic_shelter_blue','daily_grind_blue','call_for_backup_red','draw_a_crowd_blue','crash_and_bash_blue','seismic_eruption_yellow')
$deck=@{hero='valda_brightaxe';weapons=@('titans_fist');equipment=@('testament_of_valahai','richter_scale','gauntlet_of_boulderhold','craterhoof');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-MPGLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-MPGLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-MPGLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-MPGLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "MPG deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-MPGLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'valda_brightaxe' -or $frame -notmatch 'testament_of_valahai' -or $frame -notmatch 'richter_scale'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "MPG deck main-menu duel route passed. Game $($duel.gameName)."


