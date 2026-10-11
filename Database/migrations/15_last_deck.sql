-- Last deck used, per account (SWUSim main menu, owner 2026-09-25).
--
-- The menu auto-fills the Deck Link box with the deck you last STARTED A GAME with. Guests keep
-- theirs in localStorage; a signed-in player keeps theirs here as well, so it follows them to
-- another device, and the account copy is the authoritative one when both exist.
--
-- ONE ROW PER ACCOUNT — usersId is the primary key, so a new game replaces the previous deck
-- rather than accumulating history. This is a convenience pointer, not a log; matchhistory is
-- where games are recorded.
--
-- Only deck LINKS are stored (SWUDeckInputIsLink): a pasted JSON blob or free-text list has no
-- source to return to and nothing meaningful to put in a link box. 512 chars is comfortably
-- above the longest melee.gg/SWUDB URL.
--
-- Applies to every app database that serves SWUSim logins.

CREATE TABLE IF NOT EXISTS `lastdeck` (
  `usersId`   int(11)      NOT NULL,
  `deckInput` varchar(512) NOT NULL,
  `format`    varchar(32)  NOT NULL DEFAULT '',
  `leaders`   tinyint(4)   NOT NULL DEFAULT 1,
  `deckName`  varchar(128) NOT NULL DEFAULT '',
  `usedAt`    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usersId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
