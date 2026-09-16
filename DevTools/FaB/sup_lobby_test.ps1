$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-SUPLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'sup-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "SUP $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$ids=@('comeback_kid_red','comeback_kid_blue','fight_from_behind_red','fight_from_behind_blue','heroic_pose_red','empowering_ruckus_yellow','tough_smashup_blue','humble_entrance_blue','cheers_blue','dig_in_red','act_of_glory_blue','tension_in_the_air_blue','full_of_bravado_red','story_beats_blue','short_shrift_yellow','wee_wrecking_ball_yellow','bluster_buff_red','look_tuff_red','chest_puff_red','punch_above_your_weight_red')
$deck=@{hero='pleiades';weapons=@();equipment=@('helm_of_the_adored','ironrot_plate','punching_gloves','toby_jugs');mainDeck=@($ids+$ids)} | ConvertTo-Json -Compress
$hostSeat=Invoke-SUPLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$deck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
foreach($seat in @(4,2,3)){
    $added=Invoke-SUPLobby 'AddBot' ($auth+@{seat=$seat;botProfile='prism'})
    if(!$added.success){throw $added.message}
}
$roster=Invoke-SUPLobby 'PollLobbyUpdates' $auth
if(!$roster.botProfiles.prism -or @($roster.roster|Where-Object botProfile -eq 'prism').Count -ne 3){throw 'Prism not selectable in lobby'}
$game=Invoke-SUPLobby 'StartRoom' $auth
if(!$game.success){throw $game.message}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism","3":"prism","4":"prism"\}'){throw 'Prism profiles not persisted to the assigned seats'}
Write-Output "SUP deck UPF lobby passed. Game $($game.gameName)."
$duel=Invoke-SUPLobby 'JoinQueue' @{format='bot';botProfile='prism';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Prism duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'pleiades' -or $frame -notmatch 'helm_of_the_adored' -or $frame -notmatch 'ironrot_plate'){throw 'Prism duel board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"prism"\}'){throw 'Duel did not select Prism'}
Write-Output "SUP deck main-menu duel route passed. Game $($duel.gameName)."


