<?php

define('BASEDIR', __DIR__);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once BASEDIR . '/config/config.php';
require_once BASEDIR . '/vendor/autoload.php';
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
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

    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $accessToken);

    $jwks = getJWKS();

    $rsa = new RSA();
    $rsa->loadKey([
      'e' => new BigInteger(base64_decode($jwks->e), 256),
      'n' => new BigInteger(base64_decode($jwks->n), 256)
    ]);
    $rsa->setHash('sha256');
    $rsa->setSignatureMode('CRYPT_RSA_SIGNATURE_PKCS1');

    //echo $rsa;

    $dataEncoded = "$headerEncoded.$payloadEncoded";
    $signature = base64UrlDecode($signatureEncoded);

    //$result = $rsa->verfiy($dataEncoded, $signature);
    $result = openssl_verify($dataEncoded, $signature, $rsa, "RSA-SHA256") ? 'verified' : 'unverified';

    //echo $result;

    $expires = time() + $data->expires_in;

    $app->render('token.twig', array('token' => $refreshToken, 'atoken' => $accessToken, 'expiration' => date("M, d, Y h:i A", $expires), 'valid' => $result));
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


function base64UrlDecode(string $data): string
{
    $urlUnsafeData = strtr($data, '-_', '+/'); 
    $paddedData = str_pad($urlUnsafeData, strlen($data) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($paddedData);
}

function getJWKS() {
    $curl = curl_init();

    if (!$curl) {
         die("Couldnt init a cURL handle");
    }

    curl_setopt($curl, CURLOPT_URL, "https://login.eveonline.com/oauth/jwks");

    // Follow redirects, if any
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    // Fail the cURL request if response code = 400 (like 404 errors)
    curl_setopt($curl, CURLOPT_FAILONERROR, true);

    // Return the actual result of the curl result instead of success code
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Wait for 10 seconds to connect, set 0 to wait indefinitely
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

    // Execute the cURL request for a maximum of 50 seconds
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);

    $html = json_decode(curl_exec($curl));

    curl_close($curl);

    $keys = $html->keys;

    $key = "";

//    echo json_encode($keys);

    if ($keys[0]->alg == "RS256") {
        $key = $keys[0];
    } else {
        $key = $keys[1];
    }

    return $key;

}
