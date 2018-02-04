<?php

define('BASEDIR', __DIR__);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once BASEDIR . '/config/config.php';
require_once BASEDIR . '/vendor/autoload.php';
use RestCord\DiscordClient;

$app = new \Slim\Slim($config['slim']);
$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware());
$app->view(new \Slim\Views\Twig());

// Load libraries
foreach (glob(BASEDIR . '/libraries/*.php') as $lib)
    require_once($lib);


// Routes
$app->get('/', function () use ($app, $config) {
    $url = urlencode('https://login.eveonline.com/oauth/authorize?response_type=code&scope=publicData esi-location.read_location.v1 esi-location.read_ship_type.v1 esi-mail.read_mail.v1 esi-mail.send_mail.v1 esi-skills.read_skills.v1 esi-skills.read_skillqueue.v1 esi-assets.read_assets.v1 esi-fleets.read_fleet.v1 esi-fleets.write_fleet.v1 esi-ui.write_waypoint.v1 esi-corporations.read_structures.v1 esi-industry.read_character_jobs.v1 esi-location.read_online.v1 esi-characters.read_fatigue.v1 esi-characters.read_notifications.v1 esi-industry.read_character_mining.v1 esi-characterstats.read.v1&redirect_uri=' . $config['sso']['callbackURL'] . '&client_id=' . $config['sso']['clientID']);
    $app->render('index.twig', array('esiURL' => $url));
});

$app->get('/token/', function () use ($app, $config) {
    $code = $_GET['code'];

    $tokenURL = 'https://login.eveonline.com/oauth/token';
    $base64 = base64_encode($config['sso']['clientID'] . ':' . $config['sso']['secretKey']);

    $data = json_decode(sendData($tokenURL, array(
        'grant_type' => 'authorization_code',
        'code' => $code
    ), array("Authorization: Basic {$base64}")));

    $refreshToken = $data->refresh_token;
    $app->render('token.twig', array('token' => $refreshToken));
});

$app->run();

/**
 * Var_dumps and dies, quicker than var_dump($input); die();
 *
 * @param $input
 */
function dd($input)
{
    var_dump($input);
    die();
}
