<?php

namespace Tests\Functional;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use PDO;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 */
class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Use middleware when running application?
     *
     * @var bool
     */
    protected $withMiddleware = true;

    protected $db;

    // constructor receives container instance
    public function __construct()
    {
        // Use the application settings
        $s = require __DIR__.'/../../src/settings.php';
        $settings = $s['settings']['db'];
        $db = new PDO('mysql:host='.$settings['host'].';dbname='.$settings['dbname'], $settings['user'], $settings['pass']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->db = $db;
    }

    /**
     * Process the application given a request method and URI.
     *
     * @param string            $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string            $requestUri    the request URI
     * @param array|object|null $requestData   the request data
     *
     * @return \Slim\Http\Response
     */
    public function runApp($requestMethod, $requestUri, $requestData = null)
    {
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => $requestMethod,
                'REQUEST_URI' => $requestUri,
                'CONTENT_TYPE' => 'application/json;charset=utf8',
            ]
        );

        // Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);

        // Add request data, if it exists
        if (isset($requestData)) {
            $request = $request->withParsedBody($requestData);
        }

        // Set up a response object
        $response = new Response();

        // Use the application settings
        $settings = require __DIR__.'/../../src/settings.php';

        // Instantiate the application
        $app = new App($settings);

        // Set up dependencies
        require __DIR__.'/../../src/dependencies.php';

        // Register middleware
        if ($this->withMiddleware) {
            require __DIR__.'/../../src/middleware.php';
        }

        // Register routes
        require __DIR__.'/../../src/routes.php';

        // Process the application
        $response = $app->process($request, $response);

        // Return the response
        return $response;
    }

    public static function randomString($length = 10)
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    public static function randomNumber($length = 10)
    {
        return intval(substr(str_shuffle('0123456789'), 0, $length));
    }

    public static function randomOs()
    {
        $availableOs = array('Android', 'iOS', 'Windows');
        shuffle($availableOs);

        return $availableOs[0];
    }

    public static function show($obj)
    {
        fwrite(STDERR, print_r($obj, true));
    }

    /**
     * Register a new device.
     *
     * @return New device unique identifier
     */
    public static function registerNewDevice()
    {
        // Register device
        $device = [
            'os' => 'Android',
            'version' => self::randomNumber(1),
            'identifier' => self::randomString(64),
        ];
        $deviceTest = new DeviceTest();
        $deviceTest->registerDevice($device['os'], $device['version'], $device['identifier']);

        return $device['identifier'];
    }
}
