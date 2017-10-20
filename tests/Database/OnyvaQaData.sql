--
-- Contenu de la table `Device`
--
INSERT INTO `Device` (`Os`, `Version`, `Ip`, `Identifier`) VALUES
('Android', '1', '10.1.1.2', 'aaabbb'),     -- 1
('Windows', '1', '192.168.4.5', 'kkkooo'),  -- 2
('Android', '1', '172.1.2.3', 'abcdef'),    -- 3
('Android', '2', '172.1.50.20', 'poulet'),  -- 4
('Windows', '1', '92.15.4.5', 'joujou'),    -- 5
('Android', '1', '5.6.7.8', 'youhou'),      -- 6
('Android', '1', '9.9.9.9', 'cahier');      -- 7

--
-- Contenu de la table `Club`
--
INSERT INTO `Club` (`ClubName`, `ClubPassword`) VALUES
('Réunion St Sever', 'boing44'),   -- 1
('Basket', 'popo123'),             -- 2
('Cinema', 'Wayne2'),              -- 3
('Théâtre', 'Juan3%44');           -- 4

--
-- Contenu de la table `Vehicule`
--
INSERT INTO `Vehicule` (`Trademark`, `Color`, `Seats`, `VehiculeName`, `DeviceId`) VALUES
('Citroën Xsara', 'Grise', 5, 'Xsara Rémy', 1),         -- 1
('Opel', 'Bleue', 4, 'Opel Olivier', 2),                -- 2
('Renault', 'Rouge', 4, 'Le voiture rouge à Alex', 4),  -- 3
('Polo', 'Verte', 3, 'La grenouille de Kostia', 3),     -- 4
('Fiat', 'Noire', 4, 'Fiat 500 de Noémie', 7);          -- 5

--
-- Contenu de la table `Passenger`
--
INSERT INTO `Passenger` (`PassengerName`, `DeviceId`) VALUES
('Rémy', 1),        -- 1
('Emilie', 1),      -- 2
('Olivier', 2),     -- 3
('Mélanie', 2),     -- 4
('Kostia', 3),      -- 5
('Alexandra', 4),   -- 6
('Abigail', 5),     -- 7
('Deborah', 5),     -- 8
('Lisa', 6),        -- 9
('Noémie', 7);      -- 10

--
-- Contenu de la table `Travel`
--
INSERT INTO `Travel` (`Date`, `DepartureTime`, `Day`, `ClubId`) VALUES
('2015-04-14', '20:00:00', NULL, 10001),    -- 1
(NULL, '09:00:00', 'FRIDAY', 10001),        -- 2
(NULL, '18:15:00', 'FRIDAY', 10001),        -- 3
(NULL, '08:00:00', 'MONDAY', 10001),        -- 4
(NULL, '08:10:00', 'TUESDAY', 10002),       -- 5
('2017-01-25', '07:15:00', NULL, 10002);    -- 6

--
-- Contenu de la table `Link`
--
INSERT INTO `Link` (`TravelId`, `VehiculeId`, `PassengerId`) VALUES
(1, 1, 1),      -- 1
(1, 1, 2),      -- 2
(1, 2, 3),      -- 3
(1, 2, 4),      -- 4
(1, NULL, 6),   -- 5
(1, 4, 5),      -- 6
(1, NULL, 7),   -- 7
(2, 1, 1),      -- 8
(2, NULL, 10);  -- 9
