<?php

namespace App;

class TravelController extends Controller
{
    /**
     * Get travels via POST. URL can have parameters:
     * /gettravel/friday
     * /gettravel/friday/18:15.
     *
     * POST contains info to check security:
     *
     * {
     *   "identifier":"aaabbb",
     *   "clubId":"1",
     *   "clubPassword":""
     * }
     */
    public function getTravels($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestTravels = $request->getParsedBody();

        // device should already be registered to create or update vehicules
        $identifier = $requestTravels['identifier'];
        $clubId = $requestTravels['clubId'];
        $clubPassword = $requestTravels['clubPassword'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null || !isset($clubId) || !isset($clubPassword)) {
            return $response->withStatus(406);
        }

        $travels = $this->internalGetTravels($clubId, $clubPassword, $args['day'], $args['time']);

        return $response->withJson($travels, 200);
    }

    /*
     * Update all travels via POST at once.
     * /settravel.
     *
     * POST contains info to check security.
     * Security: discard request if identifier, clubId and clubPassword are wrong.
     * Each travel will be checked individually for rights to modify them
     *
     *[
     *   {
     *      "identifier":"aaabbb",
     *      "clubId":"10001",
     *      "clubPassword":"boing"
     *   },
     *   {
     *      "travels":[
     *         {
     *            "travelId":"1",
     *            "day":"MONDAY",
     *            "time":"08:00"
     *         },
     *         {
     *            "travelId":"2",
     *            "date":"2017-12-17",
     *            "time":"17:30"
     *         },
     *         {
     *            "day":"TUESDAY",
     *            "time":"17:30"
     *         }
     *      ]
     *   }
     *
     * @return \Slim\Http\Response
     */
    public function updateTravels($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $requestTravels = $request->getParsedBody();

        // device should already be registered to create or update vehicules
        $identifier = $requestTravels[0]['identifier'];
        $clubId = $requestTravels[0]['clubId'];
        $clubPassword = $requestTravels[0]['clubPassword'];

        $deviceId = $this->getDeviceIdFromIdentifier($identifier);
        if ($deviceId == null || !isset($clubId) || !isset($clubPassword)) {
            return $response->withStatus(406);
        }

        // Check the user belongs to the club
        $q = 'SELECT Cl.ClubId as clubId
                     FROM Club Cl
                     WHERE Cl.ClubId = :clubId
                     AND Cl.ClubPassword = :clubPassword';

        $query = $this->db->prepare($q);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();
        if ($query->fetch() == null) {
            // The user does not belong to the club
            return $response->withStatus(406);
        }

        $travelsResponse = array();

        foreach ($requestTravels[1]['travels'] as $travel) {
            if (isset($travel['time'])) {
                if (isset($travel['travelId'])) {
                    $this->info($this->updateTravel($travel['travelId'], $clubId, $clubPassword, $travel['date'], $travel['day'], $travel['time']));
                } else {
                    // Before creating a new one, check that the date/time or day/time does not exist yet
                    $this->info($this->createTravel($clubId, $travel['date'], $travel['day'], $travel['time']));
                }
            }
        }

        $travels = $this->internalGetAllTravels($clubId, $clubPassword);

        return $response->withJson($travels, 200);
    }

    private function createTravel($clubId, $date, $day, $time)
    {
        // Search for existing combination. If found, do not create
        if ($this->isTravelExist($clubId, $date, $day, $time)) {
            return;
        }

        try {
            $query = $this->db->prepare('INSERT INTO Travel
                                        (ClubId, Date, Day, DepartureTime)
                                        VALUES
                                        (:clubId, :date, :day, :time)');
            $query->bindParam(':clubId', $clubId);
            $query->bindParam(':date', $date);
            $query->bindParam(':day', $day);
            $query->bindParam(':time', $time);
            $query->execute();

            return "Travel created: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function updateTravel($travelId, $clubId, $clubPassword, $date, $day, $time)
    {
        try {
            $query = $this->db->prepare('UPDATE Travel Tr SET
                                    Date=:date, DepartureTime=:time, Day=:day
                                    WHERE TravelId = :travelId
                                    AND ClubId = (SELECT Cl.ClubId FROM Club Cl WHERE Cl.ClubId = :clubId AND Cl.ClubPassword = :clubPassword)');
            $query->bindParam(':travelId', $travelId);
            $query->bindParam(':date', $date);
            $query->bindParam(':time', $time);
            $query->bindParam(':day', $day);
            $query->bindParam(':clubId', $clubId);
            $query->bindParam(':clubPassword', $clubPassword);
            $query->execute();

            return "Travel updated: '$name'".$query->rowCount() == 1 ? 'succeeded.' : 'failed.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get all travels. Security: club Id and Password.
     *
     * @param clubId
     * @param clubPassword
     *
     * @return array of travels
     */
    private function internalGetAllTravels($clubId, $clubPassword)
    {
        return $this->internalGetTravels($clubId, $clubPassword, null, null);
    }

    /**
     * Get travels. You can filter by day and time.
     * Usually, day and time filters come from URL parameters.
     *
     * @param clubId
     * @param clubPassword
     * @param day
     * @param time
     *
     * @return array of travels
     */
    private function internalGetTravels($clubId, $clubPassword, $day, $time)
    {
        $q = 'SELECT Tr.TravelId as travelId,
                     Tr.Date as date,
                     Tr.DepartureTime as time,
	                 Tr.Day as day
	                    FROM Travel Tr
                        INNER JOIN Club Cl ON Tr.ClubId = Cl.ClubId
                        WHERE Cl.ClubId = :clubId
                        AND Cl.ClubPassword = :clubPassword';

        if (isset($time)) {
            $q .= ' AND DepartureTime = :time';
        }
        if (isset($day)) {
            $q .= ' AND Day = :day';
        }

        $query = $this->db->prepare($q);

        if (isset($time)) {
            $query->bindParam(':time', $time);
        }
        if (isset($day)) {
            $query->bindParam(':day', $day);
        }
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':clubPassword', $clubPassword);

        $query->execute();

        return $query->fetchAll();
    }

    /**
     * Get whether travel exists for the given travelId.
     *
     * @param clubId
     * @param date
     * @param day
     * @param time
     *
     * @return whether travel exists
     */
    private function isTravelExist($clubId, $date, $day, $time)
    {
        $q = 'SELECT TravelId as travelId
                        FROM Travel
                        WHERE ClubId = :clubId
                        AND DepartureTime = :time';
        $q .= $date == null ? ' AND Date is null' : ' AND Date = :date';
        $q .= $day == null ? ' AND Day is null' : ' AND Day = :day';

        $query = $this->db->prepare($q);
        $query->bindParam(':clubId', $clubId);
        $query->bindParam(':time', $time);

        if ($date != null) {
            $query->bindParam(':date', $date);
        }
        if ($day != null) {
            $query->bindParam(':day', $day);
        }
        $query->execute();

        return $query->fetch() != null;
    }
}
