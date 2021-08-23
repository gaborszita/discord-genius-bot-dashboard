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
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
session_start();
require_once 'php-files/common-defines.php';
require_once 'php-files/discord-oauth2.php';
require_once 'php-files/HTMLResponseBuilder.php';
require_once 'php-files/discord-permissions.php';

$url_parts = parse_url($_SERVER['REQUEST_URI']);
$parsedurl = $url_parts['path'];
$found = false;

if(substr($parsedurl, -1) == '/')
    $request_uri = substr($parsedurl, 0, -1);
else
    $request_uri = $parsedurl;

$responseBuilder = new HTMLResponseBuilder;

$responseBuilder->body .= '<h2>Discord Genius Bot Dashboard</h2>';
if($request_uri=='/login')
{
   login();
   $found = true;
}
else if($request_uri=='/login/callback')
{
    if(callback())
        header('Location: /dashboard');
    else
    {
        $responseBuilder->body .= '<p>Login error.</p>';
        http_response_code(400);
    }
    $found = true;
}
else if($request_uri=='/logout')
{
    logout();
    $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . ';
        url=/" />';
    $responseBuilder->body .= '<p>You have successfully logged out.</p>
        <p>You will be redirected in a few seconds to the home page</p>';
    $found = true;
}

else if($request_uri=='/revoketoken')
{
    logout();
    if(array_key_exists('access_token', $_SESSION) && revokeToken())
    {
        $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . ';
            url=/" />';
        $responseBuilder->body .= '<p>Your token has been successfully revoked.</p>
            <p>You will be redirected in a few seconds to the home page</p>';
    }
    else
    {
        $responseBuilder->body .= '<p>Cannot revoke token.</p>';
        http_response_code(400);
    }
    $found = true;
}

else if(substr($request_uri, 0, 10)=='/dashboard') 
{
    if(array_key_exists('access_token', $_SESSION))
    {
        $responseMe = apiRequest(API_URL_ME);
        $responseGuilds = apiRequest(API_URL_GUILDS);
        if($responseMe['code']==200 && $responseGuilds['code']==200)
        {
            $serversManagePermission = array();
            for($i=0; $i<count($responseGuilds['content']); $i++)
            {
                if(($responseGuilds['content'][$i]->permissions & ADMINISTRATOR) === ADMINISTRATOR)
                {
                    $serversManagePermission[] = array(
                        'name' => $responseGuilds['content'][$i]->name,
                        'id' => $responseGuilds['content'][$i]->id);
                }
            }
            if($request_uri == '/dashboard')
            {
                $responseBuilder->body .= '<h3>Logged In</h3>';
                $responseBuilder->body .= '<h4>Welcome, ' . $responseMe['content']->username . '</h4>';
                $responseBuilder->body .= '<p>Please select a server:</p>';
                for($i=0; $i<count($serversManagePermission); $i++)
                {
                    $responseBuilder->body .= '<p><a href="/dashboard/' . $serversManagePermission[$i]['id']
                         . '">' . $serversManagePermission[$i]['name'] . '</a></p>';
                }
                $found = true;
            }
            else
            {
                $urlSeperated = explode('/', $request_uri);
                $guild = $urlSeperated[2];
                $hasServerManagePermission = false;
                for($i=0; $i<count($serversManagePermission); $i++)
                {
                    if($guild == $serversManagePermission[$i]['id'])
                    {
                        $hasServerManagePermission = true;
                        break;
                    }
                }
                $responseBuilder->body .= '<p><a href="/dashboard">Dashboard</a></p>';
                if($hasServerManagePermission)
                {
                    if(count($urlSeperated)==3)
                    {
                        $response = apiRequest(BASE_URL . "guilds/" . $guild
                                               . "/members/" . BOT_ID, false, array(), true);
                        if($response['code']==200)
                        {
                            $responseBuilder->body .= '<p>Utilities:</p>';
                            $responseBuilder->body .= '<p><a href="/dashboard/' . 
                                $guild. '/counter' . '">Counter</a></p>';
                            $responseBuilder->body .= '<p><a href="/dashboard/' . 
                                $guild. '/greeting' . '">Greeting</a></p>';
                            $responseBuilder->body .= '<p><a href="/dashboard/' . 
                                $guild. '/offensive-words-patrol' . '">Offensive Words Patrol</a></p>';
                        }
                        else
                        {
                            header('Location: ' . AUTHORIZE_URL . '?client_id=' . 
                                   OAUTH2_CLIENT_ID . '&permissions=8&scope=bot&guild_id=' . $guild);
                        }
                        $found = true;
                    }
                    else if(count($urlSeperated)>=4 && $urlSeperated[3]=='counter')
                    {
                        $conn = new mysqli(MARIADB_SERVER_NAME, MARIADB_USERNAME, MARIADB_PASSWORD, 
                                           MARIADB_DATABASE_NAME);
                        if($conn->connect_error)
                        {
                            http_response_code(500);
                            $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            $found = true;
                        }
                        else if(count($urlSeperated)==4)
                        {
                            $result = $conn->query('SELECT nextCount, counterChannelId FROM counter_guild_data'
                                                   . ' WHERE guildId="' . $guild . '"');
                            if($result)
			    {
                                $responseBuilder->body .= '<p>This feature enables you and people in your server to count in a text channel.</p>';
                                $resultArray = mysqli_fetch_array($result);
                                if($result->num_rows>0)
                                {
                                    $channel = apiRequest(BASE_URL . 'channels/' . 
                                                          $resultArray['counterChannelId'], false, array(), true);
                                    $guildChannels = apiRequest(BASE_URL . 'guilds/' . 
                                                          $guild . '/channels', false, array(), true);
                                    if($guildChannels['code']==200)
                                    {
                                        $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/counter/update" method="post">';
                                        $responseBuilder->body .= '<label for="count_channels">Count channel:</label><br>';
                                        $responseBuilder->body .= '<select name="channel" id="count_channels">';
                                        $channelIncludedInGuild = false;
                                        if($channel['code']==200)
                                        {
                                            for($i=0; $i<count($guildChannels['content']); $i++)
                                            {
                                                if($channel['content']->id == $guildChannels['content'][$i]->id && $guildChannels['content'][$i]->type==0)
                                                {
                                                    $responseBuilder->body .= '<option value="' . 
                                                        $guildChannels['content'][$i]->id . '" selected>' . 
                                                        $guildChannels['content'][$i]->name . '</option>';
                                                    $channelIncludedInGuild = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if(!$channelIncludedInGuild)
                                        {
                                            $responseBuilder->body .= '<option value="0" selected></option>';
                                        }
                                        if($channel['code']==200)
                                        {
                                            $channelSelected = $channel['content']->id;
                                        }
                                        else
                                        {
                                            $channelSelected = 0;
                                        }
                                        for($i=0; $i<count($guildChannels['content']); $i++)
                                        {
                                            if($channelSelected != $guildChannels['content'][$i]->id && $guildChannels['content'][$i]->type==0)
                                            {
                                                $responseBuilder->body .= '<option value="' . 
                                                    $guildChannels['content'][$i]->id . '">' . 
                                                    $guildChannels['content'][$i]->name . '</option>';
                                            }
                                        }
                                        $responseBuilder->body .= '</select>';
                                        $responseBuilder->body .= '<br><label for="next_count">Next count:</label>';
                                        $responseBuilder->body .= '<br><input name="nextCount" id="next_count" type="text" value="' . 
                                            $resultArray['nextCount'] . '" />';
                                        $responseBuilder->body .= '<br><br><input type="submit" value="Update settings" />';
                                        $responseBuilder->body .= '</form>';
                                        $responseBuilder->body .= '<br>';
                                        $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/counter/disable">';
                                        $responseBuilder->body .= '<input type="submit" value="Disable">';
                                        $responseBuilder->body .= '</form>';
                                    }                              
                                }
                                else
                                {
                                    $responseBuilder->body .= '<p>This feature is not enabled</p>';
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/counter/enable">';
                                    $responseBuilder->body .= '<input type="submit" value="Enable" />';
                                    $responseBuilder->body .= '</form>';
                                }
                                
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                            
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='update')
                        {
                            if(array_key_exists('channel', $_POST) && array_key_exists('nextCount', $_POST))
                            {
                                $result = $conn->query('UPDATE counter_guild_data SET counterChannelId="' . $_POST['channel'] . 
                                                       '", nextCount="' . $_POST['nextCount'] . '" WHERE guildId="' . $guild . '"');
                                if($result == true)
                                {
                                    $responseBuilder->body .= '<p>Settings have been successfully updated.</p>';
                                    $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                    $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                         '; url=/dashboard/' . $guild . '/counter" />';
                                }
                                else
                                {
                                    http_response_code(500);
                                    $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                                }
                            }
                            else
                            {
                                http_response_code(400);
                                $responseBuilder->body .= '<p>This request is malformed</p>';
                            }
                            $found = true;
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='disable')
                        {
                            $result = $conn->query('DELETE FROM counter_guild_data WHERE guildId="' . $guild . '"');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully disabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/counter" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='enable')
                        {
                            $result = $conn->query('INSERT INTO counter_guild_data(guildId, nextCount) VALUES("' . $guild . '", 1)');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully enabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/counter" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        $conn->close();
                    }
                    else if(count($urlSeperated)>=4 && $urlSeperated[3]=='greeting')
                    {
                        $conn = new mysqli(MARIADB_SERVER_NAME, MARIADB_USERNAME, MARIADB_PASSWORD, 
                                           MARIADB_DATABASE_NAME);
                        if($conn->connect_error)
                        {
                            http_response_code(500);
                            $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            $found = true;
                        }
                        else if(count($urlSeperated)==4)
                        {
                            $result = $conn->query('SELECT 1 FROM greeting_guild_data'
                                                   . ' WHERE guildId="' . $guild . '"');
                            if($result)
                            {
                                $resultArray = mysqli_fetch_array($result);
                                $responseBuilder->body .= '<p>This feature greets users when they enter messages like ' . 
                                    '"hello" and "what\'s up".</p>';
                                if($result->num_rows>0)
                                {
                                    $responseBuilder->body .= '<p>This feature is enabled. Genius Bot is greeting people!</p>';
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/greeting/disable">';
                                    $responseBuilder->body .= '<input type="submit" value="Disable">';
                                    $responseBuilder->body .= '</form>';
                                }
                                else
                                {
                                    $responseBuilder->body .= '<p>This feature is not enabled</p>';
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/greeting/enable">';
                                    $responseBuilder->body .= '<input type="submit" value="Enable" />';
                                    $responseBuilder->body .= '</form>';
                                }
                                
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                            
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='disable')
                        {
                            $result = $conn->query('DELETE FROM greeting_guild_data WHERE guildId="' . $guild . '"');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully disabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/greeting" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='enable')
                        {
                            $result = $conn->query('INSERT INTO greeting_guild_data(guildId) VALUES("' . $guild . '")');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully enabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/greeting" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        $conn->close();
                    }
                    else if(count($urlSeperated)>=4 && $urlSeperated[3]=='offensive-words-patrol')
                    {
                        $conn = new mysqli(MARIADB_SERVER_NAME, MARIADB_USERNAME, MARIADB_PASSWORD, 
                                           MARIADB_DATABASE_NAME);
                        if($conn->connect_error)
                        {
                            http_response_code(500);
                            $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            $found = true;
                        }
                        else if(count($urlSeperated)==4)
                        {
                            $result = $conn->query('SELECT watchNSFW FROM offensive_words_patrol_guild_data'
                                                   . ' WHERE guildId="' . $guild . '"');
                            if($result)
			    {
                                $responseBuilder->body .= '<p>This feature disallows users to use offensive words in your server.</p>';
                                $resultArray = mysqli_fetch_array($result);
                                if($result->num_rows>0)
                                {
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/offensive-words-patrol/update" method="post">';
                                    if ($resultArray['watchNSFW'] == true)
                                    {
                                        $responseBuilder->body .= '<input type="checkbox" id="watchNSFW" name="watchNSFW" value="true" checked>';
                                    }
                                    else
                                    {
                                        $responseBuilder->body .= '<input type="checkbox" id="watchNSFW" name="watchNSFW" value="true">';
                                    }
                                    $responseBuilder->body .= '<label for="watchNSFW">Enable offensive words patrol in NSFW channels</label>';
                                    $responseBuilder->body .= '<br><br><input type="submit" value="Update settings">';
                                    $responseBuilder->body .= '</form>';
                                    $responseBuilder->body .= '<br>';
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/offensive-words-patrol/disable">';
                                    $responseBuilder->body .= '<input type="submit" value="Disable">';
                                    $responseBuilder->body .= '</form>';
                                }
                                else
                                {
                                    $responseBuilder->body .= '<p>This feature is not enabled</p>';
                                    $responseBuilder->body .= '<form action="/dashboard/' . $guild . '/offensive-words-patrol/enable">';
                                    $responseBuilder->body .= '<input type="submit" value="Enable" />';
                                    $responseBuilder->body .= '</form>';
                                }
                                
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                            
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='update')
                        {
                            if (isset($_POST['watchNSFW']))
                            {
                                $watchNSFW = 'true';
                            }
                            else
                            {
                                $watchNSFW = 'false';
                            }
                            $result = $conn->query('UPDATE offensive_words_patrol_guild_data SET watchNSFW=' . $watchNSFW . 
                                                    ' WHERE guildId="' . $guild . '"');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>Settings have been successfully updated.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                        '; url=/dashboard/' . $guild . '/offensive-words-patrol" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='disable')
                        {
                            $result = $conn->query('DELETE FROM offensive_words_patrol_guild_data WHERE guildId="' . $guild . '"');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully disabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/offensive-words-patrol" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        else if(count($urlSeperated)==5 && $urlSeperated[4]=='enable')
                        {
                            $result = $conn->query('INSERT INTO offensive_words_patrol_guild_data(guildId) VALUES("' . $guild . '")');
                            if($result == true)
                            {
                                $responseBuilder->body .= '<p>This feature has been successfully enabled.</p>';
                                $responseBuilder->body .= '<p>You will be redirected in a few seconds</p>';
                                $responseBuilder->head .= '<meta http-equiv="refresh" content="' . REDIRECT_TIMEOUT . 
                                     '; url=/dashboard/' . $guild . '/offensive-words-patrol" />';
                            }
                            else
                            {
                                http_response_code(500);
                                $responseBuilder->body .= '<p>An unexpected error has occured</p>';
                            }
                            $found = true;
                        }
                        $conn->close();
                    }
                }
                else
                {
                    http_response_code(403);
                    $responseBuilder->body .= '<p>You do not have permission to manage this server.</p>';
                    $found = true;
                }
            }
            if($found)
                $responseBuilder->body .= '<br><p><a href="/logout">Log Out</a></p>';
            
        }
        else
        {
            logout();
            http_response_code(403);
            header('Location: /');
            $found = true;
        }
        
    }
    else
    {
        http_response_code(401);
        header('Location: /');
        $found = true;
    }
    
}

else if($request_uri == '')
{
    if(array_key_exists('access_token', $_SESSION))
        $responseBuilder->body .= '<p>You are logged in and are ready access the dashboard.</p>
            <p><a href="/dashboard">Dashboard</a><p>';
    else
        $responseBuilder->body .= '<p>You are not logged in. To access the dashboard, 
            please log in.</p><p><a href="/login">Log In</a></p>';
    $found = true;
}
if(!$found)
{
    $responseBuilder->body .= '<p>404 The page you requested was not found</p>';
    http_response_code(404);
}
echo $responseBuilder->constructHTML();
//require("php-files/login.php");
?>
