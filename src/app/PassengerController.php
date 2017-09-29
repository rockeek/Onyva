<?php

namespace App;

use Exception;

class PassengerController extends Controller
{
    /**
     * Via a POST, get all passengers for a given identifier.
     *
     *  {
     *    "identifier":"aaabbb"
     *  }
     *
     * @return All passengers for given identifier
     */
    public function getPassengers($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestPassengers = $request->getParsedBody();

        // device should already be registered to create or update passengers
        $identifier = $requestPassengers['identifier'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        $passengers = $this->internalGetPassengers($identifier);

        return $response->withJson($passengers, 200);
    }

    /**
     * Update passengers.
     *
     * Json body request like:
     *
     *{
     *    "identifier":"47gV61SUd3AraOCkNIvPwKDoQZynzJjFt9qXW0i8mLfMHxe5buhYEsR2GBcTpl",
     *    "passengers":[
     *      {
     *          "passengerId": "1",
     *          "name": "Rémy"
     *      },
     *      {
     *          "passengerId": "2",
     *          "name": "Emilie"
     *      },
     *      {
     *          "name":"Nouveau passager"
     *      }
     *     ]
     *}
     *
     * @return \Slim\Http\Response
     */
    public function updatePassengers($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestPassengers = $request->getParsedBody();

        // device should already be registered to create or update passengers
        $identifier = $requestPassengers['identifier'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        foreach ($requestPassengers['passengers'] as $passenger) {
            if (isset($passenger['name'])) {
                if (isset($passenger['passengerId'])) {
                    $this->info($this->updatePassenger($deviceId, $passenger['passengerId'], $passenger['name']));
                } else {
                    $this->info($this->createPassenger($deviceId, $passenger['name']));
                }
            }
        }

        $passengers = $this->internalGetPassengers($identifier);

        return $response->withJson($passengers, 200);
    }

    public function deletePassenger($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestPassengers = $request->getParsedBody();

        // device should already be registered to create or update passengers
        $identifier = $requestPassengers['identifier'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        $this->info($this->internalDeletePassenger($deviceId, $requestPassengers['passengerId']));

        $passengers = $this->internalGetPassengers($identifier);

        return $response->withJson($passengers, 200);
    }

    private function internalGetPassengers($identifier)
    {
        $getQuery = $this->db->prepare('
            SELECT PassengerId as passengerId, PassengerName as name
            FROM Passenger Pa
            INNER JOIN Device De ON Pa.DeviceId = De.DeviceId
            WHERE De.Identifier = :identifier
            ');
        $getQuery->bindParam(':identifier', $identifier);
        $getQuery->execute();

        // $passengers = array();

        return $getQuery->fetchAll(\PDO::FETCH_CLASS, 'App\\Passenger');
    }

    private function updatePassenger($deviceId, $passengerId, $name)
    {
        try {
            $query = $this->db->prepare('UPDATE Passenger SET
                                    PassengerName= :name
                                    WHERE PassengerId = :passengerId
                                    AND DeviceId = :deviceId');
            $query->bindParam(':name', $name);
            $query->bindParam(':passengerId', $passengerId);
            $query->bindParam(':deviceId', $deviceId);
            $query->execute();

            return "Passenger updated: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function createPassenger($deviceId, $name)
    {
        try {
            $query = $this->db->prepare('INSERT INTO Passenger
                                     (PassengerName, DeviceId)
                                     VALUES
                                     (:name, :deviceId)                                        
                                    ');
            $query->bindParam(':name', $name);
            $query->bindParam(':deviceId', $deviceId);
            $query->execute();

            $isSuccess = $query->rowCount() == 1;

            return "Passenger created: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Delete Passenger.
     * Security: The deviceId must be correct to be able to modify a passenger.
     */
    private function internalDeletePassenger($deviceId, $passengerId)
    {
        try {
            $query = $this->db->prepare('DELETE from Passenger
                                    WHERE PassengerId = :passengerId
                                    AND DeviceId = :deviceId');
            $query->bindParam(':passengerId', $passengerId);
            $query->bindParam(':deviceId', $deviceId);

            $query->execute();

            return "Passenger deleted: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get whether passenger exists for the given device identifier.
     *
     * @param device identifier
     * @param passenger name
     *
     * @return whether passenger exists
     */
    private function isPassengerExist($identifier, $passengerId)
    {
        $query = $this->db->prepare('SELECT PassengerId as passengerId 
                                        FROM Passenger Pa
                                        INNER JOIN Device De ON Pa.DeviceId = De.DeviceId
                                        WHERE De.Identifier = :identifier
                                        AND Pa.PassengerId = :passengerId');
        $query->bindParam(':identifier', $identifier);
        $query->bindParam(':passengerId', $passengerId);
        $query->execute();

        return $query->fetch() != null;
    }
}
