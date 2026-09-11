$ErrorActionPreference = 'Stop'
$fixtureLines = @('Hero','1 Rhinar','Weapons','1 Romping Club','Deck')
foreach ($fixtureName in @('Wounding Blow','Brutal Assault','Raging Onslaught','Wounded Bull','Scar for a Scar','Snatch')) {
    foreach ($fixtureColor in @('Red','Yellow','Blue')) { $fixtureLines += "2 $fixtureName ($fixtureColor)" }
}
$fixtureLines += '2 Sigil of Solace (Red)', '2 Sigil of Solace (Blue)'
function Invoke-FaBTestEndpoint($endpoint, $fields) {
    $fields.rootName = 'FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/" + $endpoint + '.php') -Method Post -Body $fields
}
$fixtureHost = Invoke-FaBTestEndpoint 'JoinQueue' @{createPrivate='1';format='upf';deckLink=($fixtureLines -join "`n")}
if (!$fixtureHost.success) { throw $fixtureHost.message }
$fixturePlayers = @($fixtureHost)
$fixtureHeroes = @('rhinar','bravo','katsu','dorinthea')
$fixtureWeapons = @('Romping Club','Anothos','Harmonized Kodachi','Dawnblade')
$fixtureStart = @{lobbyID=$fixtureHost.lobbyID;playerID=$fixtureHost.playerID;authKey=$fixtureHost.authKey}
foreach ($fixtureSeat in 2..4) {
    $earlyStart = Invoke-FaBTestEndpoint 'StartRoom' $fixtureStart
    if ($earlyStart.success) { throw "UPF started before seat $fixtureSeat joined." }
    $guestDeck = @($fixtureLines)
    $guestDeck[1] = '1 ' + $fixtureHeroes[$fixtureSeat - 1]
    $guestDeck[3] = '1 ' + $fixtureWeapons[$fixtureSeat - 1]
    $fixtureJoined = Invoke-FaBTestEndpoint 'JoinQueue' @{privateInviteCode=$fixtureHost.inviteCode;deckLink=($guestDeck -join "`n")}
    if (!$fixtureJoined.success) { throw $fixtureJoined.message }
    $fixturePlayers += $fixtureJoined
}
$fixtureAuth4 = @{lobbyID=$fixtureHost.lobbyID;playerID=$fixtureJoined.playerID;authKey=$fixtureJoined.authKey;ready='0'}
$unready = Invoke-FaBTestEndpoint 'SetReady' $fixtureAuth4
if (!$unready.success) { throw $unready.message }
if ((Invoke-FaBTestEndpoint 'StartRoom' $fixtureStart).success) { throw 'Unready fourth seat did not block start.' }
$fixtureAuth4.ready='1'
$ready = Invoke-FaBTestEndpoint 'SetReady' $fixtureAuth4
if (!$ready.success) { throw $ready.message }
$fixtureStarted = Invoke-FaBTestEndpoint 'StartRoom' $fixtureStart
if (!$fixtureStarted.success) { throw $fixtureStarted.message }
$fixtureZoneNames = @(Get-Content "$PSScriptRoot/../../Schemas/FaBSim/GameSchema.txt" | ForEach-Object {
    if ($_ -match '^(\w+) - ') { $Matches[1] }
})
$fixtureStride = $fixtureZoneNames.Count
foreach ($fixtureViewer in $fixturePlayers) {
    $fixtureUri = 'http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=' + $fixtureStarted.gameName + '&playerID=' + $fixtureViewer.playerID + '&authKey=' + $fixtureViewer.authKey + '&lastUpdate=-1'
    $fixtureBody = (Invoke-WebRequest $fixtureUri).Content -split '<~>'
    foreach ($fixtureSeat in 1..4) {
        $fixtureOffset = ($fixtureSeat - 1) * $fixtureStride + 1
        $heroPiece = $fixtureBody[$fixtureOffset + [array]::IndexOf($fixtureZoneNames,'Hero')]
        if ($heroPiece -notmatch ('^' + $fixtureHeroes[$fixtureSeat - 1] + ' ')) { throw "Seat $fixtureSeat hero offset is wrong." }
        $handPiece = $fixtureBody[$fixtureOffset + [array]::IndexOf($fixtureZoneNames,'Hand')]
        if (($handPiece -split '<\|>').Count -ne 4) { throw "Seat $fixtureSeat did not draw four opening cards." }
        if ($fixtureSeat -ne $fixtureViewer.playerID -and $handPiece -match 'wounding|raging|snatch|sigil|brutal|scar|wounded') { throw 'Private hand leaked to another seat.' }
        if ($fixtureSeat -eq $fixtureViewer.playerID -and $handPiece -match '^CardBack') { throw 'Own hand was masked.' }
    }
}
Write-Output "UPF lobby integration passed. Game $($fixtureStarted.gameName)."
