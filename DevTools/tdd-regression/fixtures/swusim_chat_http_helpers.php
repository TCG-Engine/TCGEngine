<?php
// Shared helpers for the whisper-chat end-to-end tests. Lives in fixtures/ so the regression runner
// (which globs DevTools/tdd-regression/*.php) never runs it as a test.
// These run INSIDE the container via CLI and talk to Apache on port 80 there.
const SWUCHAT_BASE = 'http://localhost/TCGEngine/';

const SWUCHAT_SCHEMA_TWINSUNS = <<<'MD'
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
MD;

const SWUCHAT_SCHEMA_TEAMSUNS = <<<'MD'
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
MD;

const SWUCHAT_SCHEMA_PREMIER = <<<'MD'
## GIVEN
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN

## EXPECT
TURNPLAYER:1
MD;

function swuchat_http(string $path, ?array $post = null): string
{
    $opts = ['http' => ['timeout' => 60, 'ignore_errors' => true]];
    if ($post !== null) {
        $opts['http']['method'] = 'POST';
        $opts['http']['header'] = "Content-Type: application/x-www-form-urlencoded\r\n";
        $opts['http']['content'] = http_build_query($post);
    }
    $r = @file_get_contents(SWUCHAT_BASE . $path, false, stream_context_create($opts));
    return $r === false ? '' : $r;
}

function swuchat_make_game(string $schema): string
{
    $j = json_decode(swuchat_http('SWUSim/TestSchemaSetup.php', ['schema' => $schema]), true);
    return strval($j['gameName'] ?? '');
}

function swuchat_send(string $gn, string $pid, string $text, ?string $to = null): string
{
    $q = ['gameName' => $gn, 'playerID' => $pid, 'authKey' => 'testschema', 'folderPath' => 'SWUSim', 'chatText' => $text];
    if ($to !== null) $q['whisperTo'] = $to;
    return trim(swuchat_http('SubmitChat.php?' . http_build_query($q)));
}

function swuchat_getchat(string $gn): array
{
    $j = json_decode(swuchat_http('GetChat.php?' . http_build_query(['gameName' => $gn, 'lastChatID' => 0])), true);
    return is_array($j) ? $j : [];
}

// CHATONLY branch of the poll: lastUpdate far in the future (board "unchanged"), chat version 0.
function swuchat_poll(string $gn, string $pid): array
{
    $raw = swuchat_http('SWUSim/GetNextTurn.php?' . http_build_query([
        'gameName' => $gn, 'playerID' => $pid, 'authKey' => 'testschema',
        'lastUpdate' => 999999999, 'lastChatVersion' => 0, 'lastChatID' => 0,
    ]));
    if (strpos($raw, 'CHATONLY<~>') !== 0) return ['__raw' => substr($raw, 0, 200)];
    $j = json_decode(substr($raw, strlen('CHATONLY<~>')), true);
    return is_array($j) ? $j : ['__raw' => substr($raw, 0, 200)];
}

// Full-board branch (lastUpdate=0): the whole response string, for substring-absence checks.
function swuchat_poll_full_raw(string $gn, string $pid): string
{
    return swuchat_http('SWUSim/GetNextTurn.php?' . http_build_query([
        'gameName' => $gn, 'playerID' => $pid, 'authKey' => 'testschema',
        'lastUpdate' => 0, 'lastChatVersion' => 0, 'lastChatID' => 0,
    ]));
}

// The inactivity clock is OFF in the dev environment by default (user request 2026-09-17). A test that
// exercises it must opt ITS OWN game in; nothing else on the dev box is affected.
function swuchat_clock_on(string $gn): bool
{
    $j = json_decode(swuchat_http('SWUSim/DevTools/zz_presence_poke.php?'
        . http_build_query(['gameName' => $gn, 'clock' => 'on'])), true);
    return is_array($j) && !empty($j['clockEnabledInDev']);
}
