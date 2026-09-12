$ErrorActionPreference='Stop'
function Invoke-ARCQueue($endpoint,$fields){
    $fields.rootName='FaBSim'
    Invoke-RestMethod ("http://localhost/TCGEngine/APIs/Lobbies/$endpoint.php") -Method Post -Body $fields
}
$heroes=@('Kano','Azalea','Viserai','Dash')
$weapons=@('Crucible of Aetherweave','Death Dealer','Nebula Blade','Teklo Plasma Pistol')
$players=@()
foreach($seat in 1..4){
    $lines=@('Hero',"1 $($heroes[$seat-1])",'Weapons',"1 $($weapons[$seat-1])",'Equipment','1 Nullrune Hood','Deck')
    foreach($name in @('Come to Fight','Fate Foreseen','Fervent Forerunner','Ravenous Rabble','Sun Kiss','Whisper of the Oracle')){
        foreach($pitch in @('Red','Yellow','Blue')){$lines+="2 $name ($pitch)"}
    }
    $lines+='2 Life for a Life (Red)'
    $lines+=if($seat -eq 4){'2 Hyper Driver (Red)'}else{'2 Vigor Rush (Blue)'}
    $fields=@{deckLink=($lines -join "`n")}
    if($seat -eq 1){$fields.createPrivate='1';$fields.format='upf'}else{$fields.privateInviteCode=$players[0].inviteCode}
    $joined=Invoke-ARCQueue 'JoinQueue' $fields
    if(!$joined.success){throw $joined.message}
    $players+=$joined
}
$hostSeat=$players[0]
$start=Invoke-ARCQueue 'StartRoom' @{lobbyID=$hostSeat.lobbyID;playerID=$hostSeat.playerID;authKey=$hostSeat.authKey}
if(!$start.success){throw $start.message}
$zones=@(Get-Content "$PSScriptRoot/../../Schemas/FaBSim/GameSchema.txt" | ForEach-Object {if($_ -match '^(\w+) - '){$Matches[1]}})
function Read-ARCFrame($viewer){
    $uri="http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($start.gameName)&playerID=$($viewer.playerID)&authKey=$($viewer.authKey)&lastUpdate=-1"
    (Invoke-WebRequest $uri).Content -split '<~>'
}
function Get-ARCZone($frame,$seat,$zone){$frame[1+($seat-1)*$zones.Count+[array]::IndexOf($zones,$zone)]}
$dash=$players[3];$frame=Read-ARCFrame $dash
$dq=Get-ARCZone $frame 4 'DecisionQueue'
if($dq -notmatch 'Start_with_a_Mechanologist_item'){throw 'Dash setup choice was not queued before play'}
$temp=Get-ARCZone $frame 4 'Temp'
if($temp -notmatch 'hyper_driver_red'){throw 'Dash setup did not offer its item'}
$hostFrame=Read-ARCFrame $hostSeat
if((Get-ARCZone $hostFrame 4 'Temp') -match 'hyper_driver'){throw 'Dash setup leaked to another player'}
$uri="http://localhost/TCGEngine/ProcessInput.php?gameName=$($start.gameName)&playerID=4&authKey=$($dash.authKey)&folderPath=FaBSim&mode=DECISION&responseFormat=json&decisionIndex=0&cardID=p4Temp-0"
$reply=Invoke-RestMethod $uri
if($reply.success -eq $false){throw ("Setup decision failed: " + $reply.message)}
foreach($viewer in $players){
    $frame=Read-ARCFrame $viewer
    foreach($seat in 1..4){
        $hand=Get-ARCZone $frame $seat 'Hand'
        if(($hand -split '<\|>').Count -ne 4){throw "Seat $seat did not draw four cards after setup"}
        if($seat -ne $viewer.playerID -and $hand -notmatch '^CardBack'){throw "Seat $seat hand leaked"}
    }
    $arena=Get-ARCZone $frame 4 'Arena'
    if($arena -notmatch 'hyper_driver_red' -or $arena -notmatch 'STEAM[^0-9]*3'){throw 'Dash starting item/counters not public or not initialized'}
}
Write-Output "ARC four-hero lobby/setup/privacy integration passed. Game $($start.gameName)."

# Exercise the main-menu duel route with the same Dash deck, against Fai.
$start=Invoke-ARCQueue 'JoinQueue' @{format='bot';deckLink=($lines -join "`n")}
if(!$start.success -or !$start.gameName){throw 'ARC duel creation failed'}
$frame=Read-ARCFrame $start
if((Get-ARCZone $frame 1 'DecisionQueue') -notmatch 'Start_with_a_Mechanologist_item'){throw 'Duel Dash setup missing'}
$uri="http://localhost/TCGEngine/ProcessInput.php?gameName=$($start.gameName)&playerID=1&authKey=$($start.authKey)&folderPath=FaBSim&mode=DECISION&responseFormat=json&decisionIndex=0&cardID=p1Temp-0"
$reply=Invoke-RestMethod $uri
if($reply.success -eq $false){throw 'Duel Dash setup failed'}
$frame=Read-ARCFrame $start
if((Get-ARCZone $frame 1 'Arena') -notmatch 'hyper_driver_red'){throw 'Duel Dash item missing'}
if(((Get-ARCZone $frame 1 'Hand') -split '<\|>').Count -ne 4){throw 'Duel opening hand missing'}
if((Get-ARCZone $frame 2 'Hero') -notmatch 'fai'){throw 'Duel opponent missing'}
Write-Output "ARC duel against Fai setup integration passed. Game $($start.gameName)."
