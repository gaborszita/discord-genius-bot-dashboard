<?php
require_once 'common-defines.php';

function revokeToken()
{
    $params = array(                                                 
        'client_id' => OAUTH2_CLIENT_ID,
        'client_secret' => OAUTH2_CLIENT_SECRET,
        'token' => $_SESSION['access_token']
    );

    $response = apiRequest(TOKEN_REVOKE_URL, $params);
    if($response['code']==200)
    {
        return true;
    }
    else
    {
        return false;
    }
}
?>
