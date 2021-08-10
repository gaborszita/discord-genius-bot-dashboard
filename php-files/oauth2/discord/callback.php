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
require_once 'common-defines.php';
require_once 'apiRequest.php';

function callback()
{
    if(array_key_exists(CALLBACK_CODE_PARAMETER, $_GET))
    {
        // Exchange the auth code for a token
        $params = array(
            "grant_type" => "authorization_code",
            'client_id' => OAUTH2_CLIENT_ID,
            'client_secret' => OAUTH2_CLIENT_SECRET,
            'redirect_uri' => REDIRECT_URI,
            'code' => $_GET[CALLBACK_CODE_PARAMETER]
            );

        $response = apiRequest(TOKEN_URL, $params);
        if($response['code']==200 && isset($response['content']->access_token))
        {
            $_SESSION['access_token'] = $response['content']->access_token;
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}
?>
