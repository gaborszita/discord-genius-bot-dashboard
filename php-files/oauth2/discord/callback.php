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
