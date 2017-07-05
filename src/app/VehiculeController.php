<?php

namespace App;

class VehiculeController extends Controller
{
    /**
     * Via a POST, get all vehicules for a given identifier.
     *
     * {
     *   "identifier":"aaabbb"
     * }
     *
     * @return All vehicules for given identifier
     */
    public function getVehicules($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestVehicules = $request->getParsedBody();

        // device should already be registered to create or update vehicules
        $identifier = $requestVehicules['identifier'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        $vehicules = $this->internalGetVehicules($identifier);

        return $response->withJson($vehicules, 200);
    }

    /**
     * Update vehicules.
     *
     * Json body request like:
     *
     * [
     *  {
     *    "identifier":"47gV61SUd3AraOCkNIvPwKDoQZynzJjFt9qXW0i8mLfMHxe5buhYEsR2GBcTpl"
     *  },
     *  {
     *    "vehicules":[
     *      {
     *          "vehiculeId": "1",
     *          "name": "Rémy"
     *      },
     *      {
     *          "vehiculeId": "2",
     *          "name": "Emilie"
     *      },
     *      {
     *          "name":"Nouveau passager"
     *      }
     *    ]
     *  }
     * ]
     *
     * @return \Slim\Http\Response
     */
    public function updateVehicules($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestVehicules = $request->getParsedBody();

        // device should already be registered to create or update vehicules
        $identifier = $requestVehicules[0]['identifier'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        $vehiculesResponse = array();

        foreach ($requestVehicules[1]['vehicules'] as $vehicule) {
            if (isset($vehicule['name'])) {
                if (isset($vehicule['vehiculeId'])) { // Try to update. The updateVehicule contains the security. It will not update if the deviceId is not the good one
                    $this->info($this->updateVehicule($deviceId, $vehicule['vehiculeId'], $vehicule['name'], $vehicule['trademark'], $vehicule['color'], $vehicule['seats']));
                } else {
                    $this->info($this->createVehicule($deviceId, $vehicule['name'], $vehicule['trademark'], $vehicule['color'], $vehicule['seats']));
                }
            }
        }

        $vehicules = $this->internalGetVehicules($identifier);

        return $response->withJson($vehicules, 200);
    }

    private function internalGetVehicules($identifier)
    {
        $getQuery = $this->db->prepare('
            SELECT VehiculeId as vehiculeId, VehiculeName as name, Trademark as trademark, Color as color, Seats as seats
            FROM Vehicule Pa
            INNER JOIN Device De ON Pa.DeviceId = De.DeviceId
            WHERE De.Identifier = :identifier
            ');
        $getQuery->bindParam(':identifier', $identifier);
        $getQuery->execute();

        return $getQuery->fetchAll(\PDO::FETCH_CLASS, 'App\\Vehicule');
    }

    /**
     * Update Vehicule.
     * Security: The deviceId must be correct to be able to modify a vehicule.
     */
    private function updateVehicule($deviceId, $vehiculeId, $name, $trademark, $color, $seats)
    {
        try {
            $query = $this->db->prepare('UPDATE Vehicule SET
                                    VehiculeName=:name, Trademark=:trademark, Color=:color, Seats=:seats
                                    WHERE VehiculeId = :vehiculeId
                                    AND DeviceId = :deviceId');
            $query->bindParam(':deviceId', $deviceId);
            $query->bindParam(':name', $name);
            $query->bindParam(':trademark', $trademark);
            $query->bindParam(':color', $color);
            $query->bindParam(':seats', $seats);
            $query->bindParam(':vehiculeId', $vehiculeId);
            $query->execute();

            return "Vehicule updated: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function createVehicule($deviceId, $name, $trademark, $color, $seats)
    {
        try {
            $query = $this->db->prepare('INSERT INTO Vehicule
                                     (VehiculeName, Trademark, Color, Seats, DeviceId)
                                     VALUES
                                     (:name, :trademark, :color, :seats, :deviceId)                                        
                                    ');
            $query->bindParam(':name', $name);
            $query->bindParam(':trademark', $trademark);
            $query->bindParam(':color', $color);
            $query->bindParam(':seats', $seats);
            $query->bindParam(':deviceId', $deviceId);
            $query->execute();

            $isSuccess = $query->rowCount() == 1;

            return "Vehicule created: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get whether vehicule exists for the given device identifier.
     *
     * @param device identifier
     * @param vehicule name
     *
     * @return whether vehicule exists
     */
    private function isVehiculeExist($identifier, $vehiculeId)
    {
        $query = $this->db->prepare('SELECT VehiculeId as vehiculeId 
                                        FROM Vehicule Pa
                                        INNER JOIN Device De ON Pa.DeviceId = De.DeviceId
                                        WHERE De.Identifier = :identifier
                                        AND Pa.VehiculeId = :vehiculeId');
        $query->bindParam(':identifier', $identifier);
        $query->bindParam(':vehiculeId', $vehiculeId);
        $query->execute();

        return $query->fetch() != null;
    }
}
