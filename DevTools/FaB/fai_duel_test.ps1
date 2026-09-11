$ErrorActionPreference='Stop'
$deck=& C:\xampp\php\php.exe -r 'require "FaBSim/BotDeck.php"; echo json_encode(FaBFaiBotDeck());'
$game=Invoke-RestMethod 'http://localhost/TCGEngine/APIs/Lobbies/JoinQueue.php' -Method Post -Body @{rootName='FaBSim';format='bot';deckLink=$deck}
if(!$game.success -or !$game.ready -or $game.playerID -ne 1){throw 'Fai duel did not start immediately'}
$state=Get-Content "$PSScriptRoot/../../FaBSim/Games/$($game.gameName)/Gamestate.txt" -Raw
if($state -notmatch '"gameMode":"BOT"' -or $state -notmatch '"botProfiles":\{"2":"fai"\}' -or $state -notmatch '"passiveSeats":\[\]'){throw 'Fai duel did not persist an active seat-two bot'}
$poll=Invoke-WebRequest "http://localhost/TCGEngine/FaBSim/GetNextTurn.php?gameName=$($game.gameName)&playerID=1&authKey=$($game.authKey)"
if($poll.Content -notmatch 'BOTCONTROLLER:.*"players":\[2\]'){throw 'Duel did not publish the bot controller'}
Write-Output "Fai 1v1 integration passed. Game $($game.gameName)."
