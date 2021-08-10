<?php
/*
 * Copyright 2020-2021 Gabor Szita
 *
 * This file is part of Discord Genius Bot.
 *
 * Discord Genius Bot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Discord Genius Bot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Discord Genius Bot.  If not, see <https://www.gnu.org/licenses/>.
 */
?>

<?php
ini_set('max_execution_time', 300); //300 seconds = 5 minutes. In case if your CURL is slow and is loading too much (Can be IPv6 problem)

error_reporting(E_ALL);

define('OAUTH2_CLIENT_ID', '738515487167873186');
define('OAUTH2_CLIENT_SECRET', 'k5xJhseBPfD0jmhOxcI_PKnqeF8Xfomd');
define('BASE_URL', 'https://discord.com/api/');
define('AUTHORIZE_URL', BASE_URL . 'oauth2/authorize');
define('TOKEN_URL', BASE_URL . 'oauth2/token');
define('TOKEN_REVOKE_URL', BASE_URL . 'oauth2/token/revoke');
define('API_URL_ME', BASE_URL . 'users/@me');
define('API_URL_GUILDS', BASE_URL . 'users/@me/guilds');

session_start();

// Start the login process by sending the user to Discord's authorization page
if(array_key_exists('action', $_GET) && $_GET['action'] == 'login') 
{

  $params = array(
    'client_id' => OAUTH2_CLIENT_ID,
    'redirect_uri' => 'http://gaborszita.net/',
    'response_type' => 'code',
    'scope' => 'identify guilds'
  );

  // Redirect the user to Discord's authorization page
  header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params));
  die();
}

function login()
{
   $params = array(
      'client_id' => OAUTH2_CLIENT_ID,
      'redirect_uri' => 'http://gaborszita.net/',
      'response_type' => 'code',
      'scope' => 'identify'
   );

   // Redirect the user to Discord's authorization page
   header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params));
   die();
}

// When Discord redirects the user back here, there will be a "code" and "state" parameter in the query string
if(array_key_exists('code', $_GET))
{

  // Exchange the auth code for a token
  $token = apiRequest(TOKEN_URL, array(
    "grant_type" => "authorization_code",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => 'http://gaborszita.net/',
    'code' => $_GET['code']
  ));
  $_SESSION['access_token'] = $token->access_token;


  header('Location: ' . $_SERVER['PHP_SELF']);
}

if(session('access_token')) 
{
  $user = apiRequest(API_URL_ME);

  echo '<h3>Logged In</h3>';
  echo '<h4>Welcome, ' . $user->username . '</h4>';
  echo '<p><a href="?action=logout">Log Out</a></p>';
  echo '<pre>';
    print_r($user);
  echo '</pre>';

} else {
  echo '<h3>Not logged in</h3>';
  echo '<p><a href="?action=login">Log In</a></p>';
}


if(array_key_exists('action', $_GET) && $_GET['action'] == 'logout') 
{
  $params = array(                                                 
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'token' => $_SESSION['access_token']
  );

  apiRequest(TOKEN_REVOKE_URL, $params);
  session_destroy();
  die();
}

function apiRequest($url, $post=FALSE, $headers=array()) 
{
  $ch = curl_init($url);
  //curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);


  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers[] = 'Accept: application/json';

  if(session('access_token'))
    $headers[] = 'Authorization: Bearer ' . session('access_token');
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response);
}

function session($key, $default=NULL) 
{
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}

?>
