<?php
#******************************************************************#
#                      GA-Spider Application                       #
#******************************************************************#
# Google Analytics — Beacons for tracking users                    #
# Application for tracking users through pictures and proxy links  #
# Copyright 2016 Vasilyuk Vasiliy <vasilyukvasiliy@gmail.com>     #
#******************************************************************#
#                   GitHub->https://git.io/vrsil                   #
#******************************************************************#

# Defining constants
define('GA_SSL_URL', 'https://ssl.google-analytics.com/collect');
define('SES_COOKIE', '__SID');
define('SES_LIFETIME', 2 * 365 * 24 * 60 * 60); // 2 Year
define('RED_URL', 'https://git.io/vrsil'); // Redirect url from bad request
define('DEFAULT_PRINT_IMG_TYPE', 'png'); // or gif, or jpg
define('IMG_CONF_JSON', 'img/config.json');

# Configuration for environment
ini_set('session.name', SES_COOKIE);
ini_set('session.gc_maxlifetime', SES_LIFETIME);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1000);
ini_set('session.use_strict_mode', true);
ini_set('session.use_cookies', true);
ini_set('session.use_only_cookies', true);
ini_set('session.cookie_lifetime', SES_LIFETIME);
ini_set('session.cookie_secure', false);
ini_set('session.cookie_httponly', true);
ini_set('session.cache_limiter', 'nocache');
ini_set('session.hash_function', 'whirlpool');
ini_set('session.hash_bits_per_character', 6);

# Configuration headers
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: Wed, 01 Sep 1900 00:00:00');

# Main block
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method Not Allowed');
}

$r = false;
$_GET = array_change_key_case($_GET, CASE_LOWER); // All key 
session_start(); // Session start

// favicon.ico
if ($_SERVER['REQUEST_URI'] == '/favicon.ico') {
    if (file_exists('img/favicon.ico')) {
        header('Content-type: image/x-icon');
        readfile('img/favicon.ico');
    } else {
        printDefaultImg();
    }
    exit();
}

// Tracking
$isCorrect = stripos($_SERVER['REQUEST_URI'], '/t');
if ($isCorrect !== false && $isCorrect == 0) {
    if (googleTrackIdValidate($_GET['gtid'])) sendData2GoogleAnalytics(); // If is set Google Tracking ID
    if (!empty($_GET['go'])) {
        $url = urlValidator($_GET['go']);
        if ($url) {
            header('Location: ' . $url);
            exit();
        } else {
            header('Location: ' . RED_URL);
        }
    }
    checkAndPrintImgList();
    exit();
}

// If an invalid request redirects users
if (!$r) {
    header('HTTP/1.1 301 Moved Permanently'); // Header redirect
    header('Location: ' . RED_URL); // Redirect to git repo
    exit();
}

// Validate and return image
function checkAndPrintImgList()
{
    // check image print type
    $typeList = array('png', 'jpg', 'gif', 'svg'); // List image types
    foreach ($typeList as $v) {
        if (array_key_exists($v, $_GET)) {
            $type = $v;
            break;
        } else {
            $type = DEFAULT_PRINT_IMG_TYPE; // Default type;
        }
    }

    if (file_exists(IMG_CONF_JSON)) {
        $imgList = array();
        $imgList = json_decode(file_get_contents(IMG_CONF_JSON), true);
        $imgList = array_change_key_case($imgList, CASE_LOWER);
        // check image name
        foreach ($imgList as $k => $v) {
            if (array_key_exists($k, $_GET)) {
                $img = $v;
                break;
            }
        }
        if (isset($img)) {
            return checkAndPrintImg($img . $type);
        }
    }

    return printDefaultImg($type);
}

// Cheack and return images
function checkAndPrintImg($path)
{
    if (!file_exists($path)) printDefaultImg();
    $extension = end(explode('.', $path));
    if ($extension == 'jpg' || $extension == 'jpeg') header('Content-type: image/jpeg');
    elseif ($extension == 'png') header('Content-type: image/png');
    elseif ($extension == 'svg') header('Content-type: image/svg+xml');
    elseif ($extension == 'gif') header('Content-type: image/gif');
    else printDefaultImg();
    return readfile($path);
}

// Gives the user the default image, if other image not found
function printDefaultImg($extension = DEFAULT_PRINT_IMG_TYPE)
{
    if ($extension == 'jpg') {
        header('Content-type: image/jpeg');
        // ATTENTION! DO NOT CHANGE!
        echo base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDAREAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACv/EABgQAQEBAQEAAAAAAAAAAAAAAAYFBAMC/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AAPrFip6qSunSVA0bNEFlYxYrLNFGqWKkdHTYQpUqGxp2V7qC7X2a6lmzU16qNSjq07t2nvp79evoP/Z=');
    } elseif ($extension == 'gif') {
        header('Content-type: image/gif');
        // ATTENTION! DO NOT CHANGE!
        echo base64_decode('R0lGODlhAQABAIAAAP// /wAAACwAAAAAAQABAAACAkQBADs=');
    } else {
        header('Content-type: image/png');
        // ATTENTION! DO NOT CHANGE!
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNgAAIAAAUAAen63NgAAAAASUVORK5CYII=');
    }
    return true;
}

// User Language
function httpUserLang()
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        preg_match_all('/([a-zA-Z]{2}-[a-zA-Z]{2})|([a-z]{2})/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang);
        if (isset($lang[0][0])) return $lang[0][0];
    }
    return false;
}

// Random integer value
function randInt()
{
    $bytes = openssl_random_pseudo_bytes(100);
    $hex = bin2hex($bytes);
    return base_convert(hash_hmac('md5',
        json_encode($_SERVER) . rand(-PHP_INT_MAX, PHP_INT_MAX) . $hex,
        hash('gost', rand(-PHP_INT_MAX, PHP_INT_MAX) . $hex)), 16, 10);
}

// Client IP
function httpUserIp()
{
    if (getenv('HTTP_CLIENT_IP'))
        $ip = getenv('HTTP_CLIENT_IP');
    elseif (getenv('HTTP_X_FORWARDED_FOR'))
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    elseif (getenv('HTTP_X_FORWARDED'))
        $ip = getenv('HTTP_X_FORWARDED');
    elseif (getenv('HTTP_FORWARDED_FOR'))
        $ip = getenv('HTTP_FORWARDED_FOR');
    elseif (getenv('HTTP_FORWARDED'))
        $ip = getenv('HTTP_FORWARDED');
    elseif (getenv('REMOTE_ADDR'))
        $ip = getenv('REMOTE_ADDR');
    else $ip = false;
    return $ip;
}

function urlValidator($url)
{
    $url = preg_grep("/((https?|ftp):\/\/(\S*?\.\S*?))([\s)\[\]{},;\"\':<]|\.\s|$)/i", explode("\n", $url));
    if ($url) return $url[0]; else return false;
}

function googleTrackIdValidate($id)
{
    $id = preg_grep("/^UA-[0-9]{1,}-[0-9]{1,}$/i", explode("\n", $id));
    if ($id) return $id[0]; else return false;
}

function sendData2GoogleAnalytics()
{
    // gtid — Google Tracking ID
    if (!googleTrackIdValidate($_GET['gtid'])) return false;
    if (isset($_SERVER['HTTP_USER_AGENT'])) $requestArguments['ua'] = $_SERVER['HTTP_USER_AGENT']; // User agent
    if (httpUserLang()) $requestArguments['ul'] = httpUserLang(); // User lang
    if (isset($_SERVER['HTTP_REFERER'])) $requestArguments['dr'] = $_SERVER['HTTP_REFERER']; // Document Referrer
    if (httpUserIp()) $requestArguments['uip'] = httpUserIp(); // User IP

    // Request uri
    preg_match("/^([^?]*)?.*$/", $_SERVER['REQUEST_URI'], $uri);
    if ($uri[1] == '') $uri[1] = '/';
    $requestArguments['dp'] = trim($uri[1]); // Request uri
    if (substr($requestArguments['dp'], -1) != '/') $requestArguments['dp'] .= '/';

    // Use referrer
    if (isset($_SERVER['HTTP_REFERER'], $_GET['mr'])) {
        if (substr($requestArguments['dp'], -1) != '/') $requestArguments['dp'] .= '/';
        $uri = $_SERVER['HTTP_REFERER'];
        $uri = str_replace(array('http://', 'https://'), '', $uri);
        $requestArguments['dp'] .= 'REFERRER/' . $uri;
    }

    // Use mark
    if (isset($_GET['mgo']) && urlValidator($_GET['go'])) {
        $uri = urlValidator($_GET['go']);
        if (substr($requestArguments['dp'], -1) != '/') $requestArguments['dp'] .= '/';
        $requestArguments['dp'] .= 'GOADDRESS/' . $uri;
    }

    // Set static GA data
    $requestArguments['v'] = 1; // The Protocol version. The current value is '1'.
    $requestArguments['t'] = 'pageview'; // Hit type
    $requestArguments['ds'] = 'web'; // Indicates the data source of the hit.
    $requestArguments['qt'] = 700; // Transmission data delay ms
    $requestArguments['cid'] = session_id(); // Client ID
    // $requestArguments['sc'] = 'start'; // Used to control the session duration.
    $requestArguments['tid'] = $_GET['gtid']; // Google Tracking ID
    $requestArguments['z'] = randInt(); // Random integer / clear cache

    // Generate query string
    $requestUrl = GA_SSL_URL . '?' . http_build_query($requestArguments);

    // Send data to GA
    file_get_contents($requestUrl);

    return true;
}