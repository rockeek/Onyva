<?php

namespace App;

class ClubController extends Controller
{
    /**
     * To register clubs. Create new ones or update existing ones.
     * A password is mandatory for each club.
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param object              $args
     *
     * The JSON request should look like:
     * {
     *   "identifier":"47gV61SUd3AraOCkNIvPwKDoQZynzJjFt9qXW0i8mLfMHxe5buhYEsR2GBcTpl",
     *   "clubs":[
     *     {
     *        "clubId":100013,
     *        "password":"Reunion"
     *     },
     *     {
     *        "clubId":100015,
     *        "password":"popo"
     *     }
     *    ]
     * }
     *
     * @return \Slim\Http\Response
     */
    public function club($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);
        $this->db = $this->container->get('db');
        $requestClubs = $request->getParsedBody();

        // device should already be registered to create or join clubs
        $deviceId = $this->getDeviceIdFromIdentifier($requestClubs['identifier']);
        if ($deviceId == null) {
            return $response->withStatus(406);
        }

        // password is mandatory
        foreach ($requestClubs['clubs'] as $club) {
            if (!isset($club['password'])) {
                return $response->withJson($data, 406);
            }
        }

        $clubsResponse = array();
        foreach ($requestClubs['clubs'] as $club) {
            if (isset($club['name']) && !isset($club['clubId'])) {
                // CREATE a new club
                // retrieve clubId, name, number of members
                $newClubId = $this->createNewClub($club['name'], $club['password']);
                $clubsResponse[] = $this->getClubById($newClubId);
            } elseif (isset($club['clubId'])) {
                // if password is correct, JOIN the club: get name and number of members from database
                // send clubId, name, number of members
                $clubsResponse[] = $this->getClub($club['clubId'], $club['password']);
            }
        }

        return $response->withJson($clubsResponse, 200);
    }

    private function getClub($clubId, $password)
    {
        $getQuery = $this->db->prepare('SELECT ClubId as clubId, ClubName as name, ClubPassword as password FROM Club 
            WHERE ClubId = :clubId AND ClubPassword = :password');
        $getQuery->bindParam(':clubId', $clubId);
        $getQuery->bindParam(':password', $password);
        $getQuery->execute();

        // $result = $getQuery->fetchObject();
        $result = $getQuery->fetchAll(\PDO::FETCH_CLASS, 'App\\Club');
        if (sizeof($result) == 0) {
            // The club has not been found. Send it back with flag isInvalid
            $club = new Club();
            $club->clubId = intval($clubId);
            $club->password = $password;
            $club->isInvalid = true;
        } else {
            $club = $result[0];
        }

        return $club;
    }

    private function getClubById($clubId)
    {
        $getQuery = $this->db->prepare('SELECT ClubId as clubId, ClubName as name, ClubPassword as password FROM Club WHERE ClubId = :clubId');
        $getQuery->bindParam(':clubId', $clubId);
        $getQuery->execute();

        $club = $getQuery->fetchObject('App\\Club');

        return $club;
    }

    // Return the new ClubId
    private function createNewClub($name, $password)
    {
        $createQuery = $this->db->prepare('INSERT INTO Club (ClubName, ClubPassword) VALUES (:name, :password)');
        $createQuery->bindParam(':name', $name);
        $createQuery->bindParam(':password', $password);
        $createQuery->execute();

        return $this->db->lastInsertId();
    }
}
