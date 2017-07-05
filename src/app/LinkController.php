<?php

namespace App;

class LinkController extends Controller
{
    /**
     * Get all links between vehicules and passengers for a given club and travel.
     *
     * Request body is like:
     *
     * {
     *  "identifier":"aaabbb",
     *  "clubId":"10001",
     *  "clubPassword":"boing",
     *  "travelId":"1"
     * }
     *
     * @return List of links
     */
    public function getLinks($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestLinks = $request->getParsedBody();

        // device should already be registered to create or update vehicules
        $identifier = $requestLinks['identifier'];
        $clubId = $requestLinks['clubId'];
        $clubPassword = $requestLinks['clubPassword'];
        $travelId = $requestLinks['travelId'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null || !isset($clubId) || !isset($clubPassword) || !isset($travelId)) {
            return $response->withStatus(406);
        }

        $v = $this->internalGetVehicules($clubId, $clubPassword, $travelId);
        $lonelyPassengers = $this->internalGetLonelyPassengers($clubId, $clubPassword, $travelId);

        $vehicules = array();
        foreach ($v as $vehicule) {
            $vehicule->passengers = $this->internalGetPassengersForVehicule($vehicule->vehiculeId, $travelId);
            $vehicules[] = $vehicule;
        }

        $links = ['vehicules' => $vehicules, 'passengers' => $lonelyPassengers];

        return $response->withJson($links, 200);
    }

    /**
     * Create a link between a vehicule and a passenger.
     * The passenger should either be of the correct deviceId or be waiting in the link table with vehiculeId=NULL.
     *
     * Request body is like:
     *
     * {
     *  "identifier":"aaabbb",
     *  "clubId":"10001",
     *  "clubPassword":"boing",
     *  "travelId":"1",
     *  "link":{
     *  	"passengerId":"5",
     *  	"vehiculeId":"1"
     *   }
     * }
     *
     * Returns result of the link creation:
     * {"result":"new vehicule with a passenger linked successfully"}
     * or a validation:
     * {"validation":"passenger must belong to the commanding device"}
     */
    public function updateLinks($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestLinks = $request->getParsedBody();

        // device should already be registered to create a link
        $identifier = $requestLinks['identifier'];
        $clubId = $requestLinks['clubId'];
        $clubPassword = $requestLinks['clubPassword'];
        $travelId = $requestLinks['travelId'];
        $link = $requestLinks['link'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null || !isset($clubId) || !isset($clubPassword) || !isset($travelId)) {
            return $response->withStatus(406);
        }

        // Check club is correct to access travelId
        if (!$this->isTravelIdAccessible($clubId, $clubPassword, $travelId)) {
            return $response->withStatus(406);
        }

        $linkCreationResult = $this->internalCreateLink($link['vehiculeId'], $link['passengerId'], $travelId, $deviceId);

        return $response->withJson($linkCreationResult, 200);
    }

    private function getLinkedVehicule($vehiculeId, $travelId)
    {
        $q = 'SELECT 
                     Ve.VehiculeId as vehiculeId, 
                     Ve.Trademark as trademark,
                     Ve.Color as color,
                     Ve.Seats as seats,
                     count(Lk.PassengerId) as nbPassengers,
                     Ve.Seats - count(Lk.PassengerId) as freeSeats,
                     Ve.VehiculeName as vehiculeName
                         FROM Link Lk
                         INNER JOIN Travel Tr ON Lk.TravelId = Tr.TravelId
                         INNER JOIN Vehicule Ve ON Lk.VehiculeId = Ve.VehiculeId
                             WHERE Tr.TravelId = :travelId
                             AND Lk.VehiculeId = :vehiculeId
                             GROUP BY VehiculeId';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':vehiculeId', $vehiculeId);
        $query->execute();

        $vehicule = $query->fetchObject('App\\Vehicule');
        $passengers = $this->internalGetPassengersForVehicule($vehicule->vehiculeId, $travelId);
        if ($passengers != null) {
            $vehicule->passengers = $passengers;
        }

        return $vehicule;
    }

    private function getLinkedPassenger($passengerId, $travelId)
    {
        $q = 'SELECT 
                     Pa.PassengerId as passengerId,
                     Pa.PassengerName as passengerName
                         FROM Link Lk
                         INNER JOIN Travel Tr ON Lk.TravelId = Tr.TravelId
                         INNER JOIN Passenger Pa ON Lk.PassengerId = Pa.PassengerId
                             WHERE Tr.TravelId = :travelId
                             AND Lk.PassengerId = :passengerId
                             GROUP BY PassengerId';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':passengerId', $passengerId);
        $query->execute();

        return $query->fetchObject('App\\Passenger');
    }

    private function internalCreateLink($vehiculeId, $passengerId, $travelId, $deviceId)
    {
        // TODO: if passenger and vehicule are set, check whether the vehicule belongs to the device.
        // if it belongs to the device, add the passenger to the vehicule
        // if it does not belong, the vehicule must already exist and have free seats: result: the vehicule is not available

        $device = new Device($this->db, $deviceId);

        // CASE 1: the device's user offers his vehicule V and a passenger P
        // Situation: passenger is set. vehicule is set.
        //            vehicule does not exist in Link
        // Conditions: the device must own both vehicule V and passenger P
        // Action: insert vehicule V with passenger P

        // CASE 2: the device's user offers his vehicule alone
        // Situation: vehicule only is set
        //            vehicule does not exist in Link
        // Conditions: the device must own vehicule
        // Action: 'validation': 'a vehicule needs a driver'. Do nothing.

        // CASE 3: the device's user offers a passenger P to a waiting vehicule V
        // Situation: passenger is set. vehicule is set.
        //            vehicule V exists in Link
        //            vehicule V has free seats
        // Conditions: the device must own passenger P
        // Action: insert vehicule V with passenger P

        // CASE 4: the device's user offers a passenger to the sidewalk
        // Situation: passenger only is set
        //            passenger does not exist in Link
        // Conditions: the device must own passenger P
        // Action: insert passenger P with vehicule=null

        // CASE 5: the device's user moves his sidewalk passenger into a vehicule or
        //      5b: the device's vehicule moves a sidewalk passenger into his vehicule
        //      5c: the passenger exits the vehicule to the sidewalk
        //      5d: the vehicule owner kicks out the passenger to the sidewalk.
        //          the vahicule owner cannot move the passenger in another car.
        // Situation: passenger is set. vehicule is set.
        //            vehicule V exists in Link
        //            vehicule V has free seats
        //            passenger P exists in Link
        // Conditions: the device must own passenger P or must own vehicule V
        // Action: update link vehicule V with passenger P

        // CASE 6: the device's user moves his passenger P from vehicule V1 to vehicule V2
        // Situation: passenger is set. vehicule is set.
        //            vehicule V2 exists in Link
        //            vehicule V2 has free seats
        //            vehicule V1 still has a driver if P leaves
        //            passenger P exists in Link
        // Conditions: the device must own passenger P
        // Actions: 1) update passenger P with vehicule=null
        //          2) update passenger with vehicule=V2
        // If step 2 fails, the passenger P finds himself on the sidewalk.

        $linkedVehicule = $this->getLinkedVehicule($vehiculeId, $travelId);
        $linkedPassenger = $this->getLinkedPassenger($passengerId, $travelId);

        if ($passengerId != null && $vehiculeId != null) {
            if ($linkedVehicule == null) {
                // CASE 1
                // Conditions: the device must own both vehicule V and passenger P
                if (!($device->isOwnPassengerId($passengerId) && $device->isOwnVehiculeId($vehiculeId))) {
                    return ['validation' => 'vehicule and passenger must belong to the commanding device'];
                }
                // Action: insert vehicule V with passenger P
                // If there is no waiting passenger and link does not exist yet, insert it
                if ($this->linkPassengerWithVehicule($vehiculeId, $passengerId, $travelId)) {
                    return ['result' => 'new vehicule with a passenger linked successfully'];
                }
            } elseif ($linkedVehicule->freeSeats() > 0) {
                if ($linkedPassenger != null) {
                    // CASE 5
                    // Conditions: the device must own passenger P or must own vehicule V
                    if (!($device->isOwnPassengerId($passengerId) || $device->isOwnVehiculeId($vehiculeId))) {
                        return ['validation' => 'vehicule or passenger must belong to the commanding device'];
                    }
                    // Other condition: passenger should not already be in that vehicule!
                    if ($linkedVehicule->hasPassenger($passengerId)) {
                        return ['validation' => 'the passenger is already in that vehicule'];
                    }
                    // Action: update link vehicule V with passenger P
                    // CASE 5:
                    if ($device->isOwnPassengerId($passengerId) && $this->linkPassengerWithVehicule($vehiculeId, $passengerId, $travelId)) {
                        return ['result' => 'lonely passenger went to waiting vehicule successfully'];
                    }
                    // CASE 5b:
                    if ($device->isOwnVehiculeId($vehiculeId) && $this->linkPassengerWithVehicule($vehiculeId, $passengerId, $travelId)) {
                        return ['result' => 'vehicule owner picked the lonely passenger up'];
                    }
                } elseif (!$device->isOwnPassengerId($passengerId)) {
                    return ['validation' => 'vehicule owner cannot pick up a passenger who is not lonely waiting'];
                } else {
                    // CASE 3
                    // Conditions: the device must own passenger P
                    // Action: insert vehicule V with passenger P
                    if ($this->linkPassengerWithVehicule($vehiculeId, $passengerId, $travelId)) {
                        return ['result' => 'new passenger linked to existing vehicule successfully'];
                    }
                }
            }
        } elseif ($passengerId == null && $vehiculeId != null) {
            // CASE 2
            // Conditions: the device must own vehicule
            if (!$device->isOwnVehiculeId($vehiculeId)) {
                return ['validation' => 'the vehicule must belong to the commanding device'];
            }
            if ($linkedVehicule == null) {
                return ['validation' => 'a vehicule needs a driver'];
            }
        } elseif ($passengerId != null && $vehiculeId == null) {
            if ($linkedPassenger == null) {
                // Conditions: the device must own passenger P
                if (!$device->isOwnPassengerId($passengerId)) {
                    return ['validation' => 'passenger must belong to the commanding device'];
                }
                // CASE 4
                // Action: insert passenger P with vehicule=null
                if ($this->linkPassengerWithVehicule(null, $passengerId, $travelId)) {
                    return ['result' => 'lonely passenger linked to existing vehicule successfully'];
                }
            } else {
                // Passenger exists of the car
                // CASE 4, 5c or 5d
                if ($device->isOwnPassengerId($passengerId)) {
                    if ($this->linkPassengerWithVehicule(null, $passengerId, $travelId)) {
                        return ['result' => 'passenger went lonely waiting to the sidewalk'];
                    }
                } else {
                    $vId = $this->getVehiculeIdOfTheVehiculeThePassengerSeats($passengerId, $travelId);
                    if ($vId != null && $device->isOwnVehiculeId($vId)) {
                        // Case 5d: the driver kicks the passenger out
                        if ($this->linkPassengerWithVehicule(null, $passengerId, $travelId)) {
                            return ['result' => 'vehicule driver kicked the passenger to the sidewalk'];
                        }
                    } else {
                        return ['validation' => 'passenger must belong to the commanding device'];
                    }
                }
            }
        }

        return ['validation' => 'Error: something went wrong while linking'];
    }

    private function getVehiculeIdOfTheVehiculeThePassengerSeats($passengerId, $travelId)
    {
        $q = 'SELECT VehiculeId as vehiculeId
                FROM Link
                WHERE VehiculeId is not null
                AND PassengerId = :passengerId
                AND TravelId = :travelId';

        $query = $this->db->prepare($q);
        $query->bindParam(':passengerId', $passengerId);
        $query->bindParam(':travelId', $travelId);

        $query->execute();

        $vehicule = $query->fetchObject('App\\Vehicule');

        return $vehicule->vehiculeId;
    }

    private function linkPassengerWithVehicule($vehiculeId, $passengerId, $travelId)
    {
        // If the passenger is not lonely waiting, insert a new line
        // Else, update existing passenger with vehiculeId set
        $linkedPassenger = $this->getLinkedPassenger($passengerId, $travelId);

        // TODO To enhance. The try catch does not seem to work, when SQL request fails, it's not caught
        try {
            if ($linkedPassenger != null) {
                $q = 'UPDATE Link SET VehiculeId = :vehiculeId
                        WHERE TravelId = :travelId
                        AND PassengerId = :passengerId';
            } else {
                $q = 'INSERT INTO Link (TravelId, VehiculeId, PassengerId)
                        VALUES (:travelId, :vehiculeId, :passengerId)';
            }

            $query = $this->db->prepare($q);
            $query->bindParam(':vehiculeId', $vehiculeId);
            $query->bindParam(':passengerId', $passengerId);
            $query->bindParam(':travelId', $travelId);

            $query->execute();

            return true;
        } catch (Exception $e) {
            // TODO Warning: This does not seem to catch anything
            return $query->errorCode();
        }
    }

    /**
     * For security. Returns true if travelId is accessible through the club.
     */
    private function isTravelIdAccessible($clubId, $clubPassword, $travelId)
    {
        $q = 'SELECT
                Tr.TravelId as travelId
                FROM Travel Tr
                INNER JOIN Club Cl ON Tr.ClubId = Cl.ClubId
                WHERE Tr.TravelId = :travelId
                     AND Cl.ClubId = :clubId
                     AND Cl.ClubPassword = :clubPassword';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();

        return $query->fetch() != null;
    }

    private function internalGetPassengersForVehicule($vehiculeId, $travelId)
    {
        // Get passengers for a given vehicule and travel
        $q = 'SELECT 
             Pa.PassengerId as passengerId,
             Pa.PassengerName as passengerName
                 FROM Link Lk
                 INNER JOIN Passenger Pa ON Lk.PassengerId = Pa.PassengerId
                     WHERE Lk.VehiculeId = :vehiculeId
                     AND Lk.TravelId = :travelId;';

        $query = $this->db->prepare($q);
        $query->bindParam(':vehiculeId', $vehiculeId);
        $query->bindParam(':travelId', $travelId);

        $query->execute();

        $passengers = array();
        while ($passenger = $query->fetchObject('App\\Passenger')) {
            $passengers[] = $passenger;
        }

        return $passengers;
    }

    private function internalGetLonelyPassengers($clubId, $clubPassword, $travelId)
    {
        // Get passengers that are not in a vehicule for a given travel
        $q = 'SELECT 
             Pa.PassengerId as passengerId,
             Pa.PassengerName as passengerName
                 FROM Link Lk
                 INNER JOIN Travel Tr ON Lk.TravelId = Tr.TravelId
                 INNER JOIN Club Cl ON Tr.ClubId = Cl.ClubId
                 INNER JOIN Passenger Pa ON Lk.PassengerId = Pa.PassengerId
                     WHERE Tr.TravelId = :travelId
                     AND Cl.ClubId = :clubId
                     AND Cl.ClubPassword = :clubPassword
                     AND Lk.VehiculeId is NULL';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();

        $passengers = array();
        while ($passenger = $query->fetchObject('App\\Passenger')) {
            $passengers[] = $passenger;
        }

        return $passengers;
    }

/*    private function internalGetPassengers($clubId, $clubPassword, $travelId)
    {
        // Get passengers for a given travel
        $q = 'SELECT
             Lk.VehiculeId as vehiculeId,
             Pa.PassengerId as passengerId,
             Pa.PassengerName as passengerName
                 FROM Link Lk
                 INNER JOIN Travel Tr ON Lk.TravelId = Tr.TravelId
                 INNER JOIN Club Cl ON Tr.ClubId = Cl.ClubId
                 INNER JOIN Passenger Pa ON Lk.PassengerId = Pa.PassengerId
                     WHERE Tr.TravelId = :travelId
                     AND Cl.ClubId = :clubId
                     AND Cl.ClubPassword = :clubPassword';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();

        return $query->fetchAll();
    }*/

    private function internalGetVehicules($clubId, $clubPassword, $travelId)
    {
        // Get vehicules for a given travel
        $q = 'SELECT 
             Ve.VehiculeId as vehiculeId, 
             Ve.Trademark as trademark,
             Ve.Color as color,
             Ve.Seats as seats,
             count(Lk.PassengerId) as nbPassengers,
             Ve.Seats - count(Lk.PassengerId) as freeSeats,
             Ve.VehiculeName as vehiculeName
                 FROM Link Lk
                 INNER JOIN Travel Tr ON Lk.TravelId = Tr.TravelId
                 INNER JOIN Club Cl ON Tr.ClubId = Cl.ClubId
                 INNER JOIN Vehicule Ve ON Lk.VehiculeId = Ve.VehiculeId
                     WHERE Tr.TravelId = :travelId
                     AND Cl.ClubId = :clubId
                     AND Cl.ClubPassword = :clubPassword
                     GROUP BY VehiculeId';

        $query = $this->db->prepare($q);
        $query->bindParam(':travelId', $travelId);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();
        $vehicules = array();
        while ($vehicule = $query->fetchObject('App\\Vehicule')) {
            $vehicules[] = $vehicule;
        }

        return $vehicules;
    }
}
