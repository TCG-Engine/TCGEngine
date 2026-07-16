-- Apply once to existing installations before storing deck key indicators whose
-- canonical card IDs exceed the legacy 16-character limit (for example Azuki).

ALTER TABLE `ownership`
  MODIFY `keyIndicator1` varchar(64) DEFAULT NULL,
  MODIFY `keyIndicator2` varchar(64) DEFAULT NULL;
