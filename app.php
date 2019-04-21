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
    $scopes = str_replace(' ', '%20', $config['sso']['scopes']);
    $app->render('index.twig', array('esiURL' => 'https://login.eveonline.com/v2/oauth/authorize?response_type=code&scope=' . $scopes . '&redirect_uri=' . $config['sso']['callbackURL'] . '&client_id=' . $config['sso']['clientID'] . '&state=ireallyshouldimplementthis'));
});

$app->get('/token/', function () use ($app, $config) {
    $code = $_GET['code'];

    $tokenURL = 'https://login.eveonline.com/v2/oauth/token';
    $base64 = base64_encode($config['sso']['clientID'] . ':' . $config['sso']['secretKey']);

    $data = json_decode(sendData($tokenURL, array(
        'grant_type' => 'authorization_code',
        'code' => $code
    ), array("Authorization: Basic {$base64}")));

    $refreshToken = $data->refresh_token;
    $accessToken = $data->access_token;
    $expires = time() + $data->expires_in;

    $app->render('token.twig', array('token' => $refreshToken, 'atoken' => $accessToken, 'expiration' => date("M, d, Y h:i A", $expires)));
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
