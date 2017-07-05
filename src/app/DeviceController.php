<?php

namespace App;

class DeviceController extends Controller
{
    /**
     * To register a device. Create a new one or update existing.
     *
     * Request body is like:
     *
     * {
     *  "os":"Android",
     *  "version":"1",
     *  "identifier":"abc1234"
     * }
     *
     * @return When device created or updated: status 200. 406 otherwise.
     */
    public function device($request, $response, $args)
    {
        $this->logMe(get_class(), __FUNCTION__, $args);

        $body = $request->getParsedBody();

        $identifier = $body['identifier'];
        $version = $body['version'];
        $os = $body['os'];
        $ip = $request->getAttribute('ip_address');

        // Required identifier, os and version
        if (!isset($identifier) || !isset($version) || !isset($os) || !isset($ip)) {
            return $response->withStatus(406);
        }

        $db = $this->container->get('db');

        $query = $db->prepare('SELECT * FROM Device  WHERE identifier = :identifier');
        $query->bindParam(':identifier', $identifier);
        $query->execute();

        // Device does not exist. Create it.
        if ($query->fetch() == null) {
            $this->container->logger->info('Device with identifier='.$identifier.' is new.');

            $query = $db->prepare('INSERT INTO Device (Identifier, Version, Os, Ip) VALUES (:identifier, :version, :os, :ip)');
            $query->bindParam(':identifier', $identifier);
            $query->bindParam(':version', $version);
            $query->bindParam(':os', $os);
            $query->bindParam(':ip', $ip);

            $query->execute();
            $this->container->logger->info('Device with identifier='.$identifier.', os='.$os.', version='.$version.', ip='.$ip.' has been created.');
        }
        // If it exists, update it with new values.
        else {
            $query = $db->prepare('UPDATE Device SET Version=:version, Os=:os, Ip=:ip WHERE Identifier=:identifier;');
            $query->bindParam(':identifier', $identifier);
            $query->bindParam(':version', $version);
            $query->bindParam(':os', $os);
            $query->bindParam(':ip', $ip);
            $query->execute();

            $this->container->logger->info('Device with identifier='.$identifier.', os='.$os.', version='.$version.', ip='.$ip.' has been updated.');
        }

        return $response->withStatus(200); //->write(print_r($body, true));
    }
}
