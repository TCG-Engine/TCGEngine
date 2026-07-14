-- Apply once before enabling Discord sign-in on an existing installation.
-- Discord remains stored in users.discordID so the existing bot integrations and
-- profile linking flow continue to share one source of truth.

ALTER TABLE `users`
  MODIFY `usersPwd` varchar(255) DEFAULT NULL,
  DROP INDEX `discordID`,
  ADD UNIQUE KEY `discordID` (`discordID`);

