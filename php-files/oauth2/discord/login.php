<?php
require_once 'common-defines.php';

function login()
{
    $params = array(
        'client_id' => OAUTH2_CLIENT_ID,
        'redirect_uri' => REDIRECT_URI,
        'response_type' => CALLBACK_CODE_PARAMETER,
        'scope' => 'identify guilds'
    );

    // Redirect the user to Discord's authorization page
    header('Location: https://discordapp.com/api/oauth2/authorize' . '?' . http_build_query($params));
    return true;
}
?>
