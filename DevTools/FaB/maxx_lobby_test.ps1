$ErrorActionPreference='Stop'
[System.Net.WebRequest]::DefaultWebProxy=$null
function Invoke-MaxxLobby($endpoint,$fields){
    $fields.rootName='FaBSim'
    $body=($fields.GetEnumerator() | ForEach-Object { [Uri]::EscapeDataString([string]$_.Key)+'='+[Uri]::EscapeDataString([string]$_.Value) }) -join '&'
    $bodyFile=Join-Path $env:TEMP 'maxx-lobby-body.txt'
    [IO.File]::WriteAllText($bodyFile,$body)
    try { $response=curl.exe --noproxy '*' -sS --max-time 30 --data-binary "@$bodyFile" "http://127.0.0.1/TCGEngine/APIs/Lobbies/$endpoint.php"
    if($LASTEXITCODE -ne 0){throw "Maxx $endpoint HTTP request failed"} } finally { Remove-Item -LiteralPath $bodyFile -ErrorAction SilentlyContinue }
    $response | ConvertFrom-Json
}
$deck=Get-Content "$PSScriptRoot/../../FaBSim/MaxxDeck.json" -Raw
$duel=Invoke-MaxxLobby 'JoinQueue' @{format='bot';botProfile='maxx';deckLink=$deck}
if(!$duel.success -or !$duel.gameName){throw 'Maxx duel creation failed'}
$uri="http://127.0.0.1/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($duel.gameName)&playerID=1&authKey=$($duel.authKey)&lastUpdate=-1"
$frame=(Invoke-WebRequest -UseBasicParsing $uri).Content
if($frame -notmatch 'maxx_the_hype_nitro' -or $frame -notmatch 'banksy' -or $frame -notmatch 'puffer_jacket'){throw 'Maxx board missing deck identity'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($duel.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"botProfiles":\{"2":"maxx"\}'){throw 'Duel did not select Maxx'}
Write-Output "Maxx main-menu duel route passed. Game $($duel.gameName)."
$upfDeck=Get-Content "$PSScriptRoot/../../FaBSim/UzuriDeck.json" -Raw
$hostSeat=Invoke-MaxxLobby 'JoinQueue' @{createPrivate='1';format='upf';deckLink=$upfDeck}
if(!$hostSeat.success){throw $hostSeat.message}
$auth=@{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
$roster=Invoke-MaxxLobby 'PollLobbyUpdates' $auth
if($roster.botProfiles.maxx){throw 'Duel-only Maxx is offered in UPF'}
$added=Invoke-MaxxLobby 'AddBot' ($auth+@{seat=2;botProfile='maxx'})
if($added.success){throw 'UPF accepted duel-only Maxx'}
Write-Output 'Maxx is unavailable in UPF, including direct AddBot requests.'
