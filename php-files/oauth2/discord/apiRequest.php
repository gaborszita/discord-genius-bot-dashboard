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
function apiRequest($url, $post=FALSE, $headers=array(), $bot=false) 
{
    $ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
 
    $response = curl_exec($ch);


    if($post)
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $headers[] = 'Accept: application/json';

    if(array_key_exists('access_token', $_SESSION) && !$bot)
    {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
    }
    else if($bot)
    {
        $headers[] = 'Authorization: Bot ' . BOT_TOKEN;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    while(1)
    {
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $return = array(
            'content' => json_decode($response),
            'code' => $code
            );
        if($return['code']==429)
            sleep(1);
        else
            break;
    }

    return $return;
}
?>
