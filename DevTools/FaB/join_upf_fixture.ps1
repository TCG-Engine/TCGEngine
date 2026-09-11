param([Parameter(Mandatory=$true)][string]$InviteCode)
$fixtureNames = @('Wounding Blow','Brutal Assault','Raging Onslaught','Wounded Bull','Scar for a Scar','Snatch')
$fixtureLines = @('Hero','1 Rhinar','Weapons','1 Romping Club','Deck')
foreach ($fixtureName in $fixtureNames) {
    foreach ($fixtureColor in @('Red','Yellow','Blue')) { $fixtureLines += "2 $fixtureName ($fixtureColor)" }
}
$fixtureLines += '2 Sigil of Solace (Red)', '2 Sigil of Solace (Blue)'
foreach ($fixtureSeat in 2..4) {
    $fixtureResponse = Invoke-RestMethod 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php' -Method Post -Body @{
        rootName='FaBSim'; privateInviteCode=$InviteCode; deckLink=($fixtureLines -join "`n")
    }
    if (!$fixtureResponse.success) { throw $fixtureResponse.message }
    Write-Output "Joined test seat $($fixtureResponse.playerID)."
}
