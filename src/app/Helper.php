<?php

namespace App;

class Helper
{
    public static function flushClub($db, $code, $password)
    {
        if(!isset($code) || !isset($password))
        {
            return;
        }
        
        //delete from database all the clubs and their related data
        $query = $db->prepare('SELECT ClubId FROM Club');
        $query->bindParam(':code', $club['code']);
        $query->bindParam(':password', $club['password']);
        $query->execute();
        $clubs = $query->fetchAll();

        $clubIds = array();
        foreach ($clubs as $club) {
            $clubIds[] = $club['ClubId'];
        }
        
        $query = $db->prepare('DELETE FROM Club WHERE ClubId IN (:clubIds)');
        $query->bindParam(':clubIds', implode(',', $clubIds));
        $query->execute();
    }

    public static function flushTravel($db, $travelId)
    {
    }
}
