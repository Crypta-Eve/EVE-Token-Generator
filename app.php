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
    $app->render('index.twig', array('esiURL' => 'https://login.eveonline.com/oauth/authorize?response_type=code&scope=' . $scopes . '&redirect_uri=' . $config['sso']['callbackURL'] . '&client_id=' . $config['sso']['clientID']));
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
