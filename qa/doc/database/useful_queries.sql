-- All vehicules for a given device
SET @deviceIdentifier = 'aaabbb';

SELECT * FROM Vehicule Ve
INNER JOIN Device De ON Ve.DeviceId = De.DeviceId
WHERE De.Identifier = @deviceIdentifier;

-- All passengers for a given device
SET @deviceIdentifier2 = 'kkkooo';

SELECT * FROM Passenger Pa
INNER JOIN Device De ON Pa.DeviceId = De.DeviceId
WHERE De.Identifier = @deviceIdentifier2;

-- Get Travels from same Club for a 1 hour around my departure time
SET @clubCode = '01a';
SET @clubPassword = 'boing';
SET @myDepartureTime = '18:30';
SET @myDay = 'FRIDAY';
SET @clubId = 1;

-- System set:
SET @departureBeforeRange = '-0:30';
SET @departureAfterRange = '0:30';

select CONCAT('Départ entre ', AddTime(@myDepartureTime, @departureBeforeRange), ' et ', AddTime(@myDepartureTime, @departureAfterRange))  as Description, Tr.* from Travel Tr
INNER JOIN Club Cl ON Cl.ClubId=Tr.ClubId
Where Tr.DepartureTime >= AddTime(@myDepartureTime, @departureBeforeRange) 
	AND Tr.DepartureTime <= AddTime(@myDepartureTime, @departureAfterRange) 
AND Tr.Day=@myDay
AND Cl.ClubCode = @clubCode
AND Cl.ClubPassword = @clubPassword
