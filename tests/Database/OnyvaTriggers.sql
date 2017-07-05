-- Insert dummy to start with ClubId à 10000
INSERT INTO `Club` (`ClubId`, `ClubName`, `ClubPassword`) VALUES
(10000, 'FirstDummy', 'FirstDummy');

--
-- Déclencheurs `Club`
--
DROP TRIGGER IF EXISTS `Pop ClubId`;

CREATE TRIGGER `Pop ClubId` BEFORE INSERT ON `Club`
 FOR EACH ROW BEGIN
	SET @clubIdFound = (SELECT MIN(`ClubId`) FROM `ClubIdPool`);
	IF @clubIdFound is not null THEN
		SET NEW.`ClubId` = @clubIdFound;
		DELETE FROM `ClubIdPool` WHERE `ClubId` = NEW.`ClubId`;
	END IF;
END;

DROP TRIGGER IF EXISTS `Stash deleted ClubId`;

CREATE TRIGGER `Stash deleted ClubId` AFTER DELETE ON `Club`
 FOR EACH ROW INSERT INTO `ClubIdPool` (`ClubId`) VALUES (OLD.`ClubId`);