<?php
define('OAUTH2_CLIENT_ID', 'ENTER_YOUR_OAUTH2_CLIENT_ID_HERE');
define('OAUTH2_CLIENT_SECRET', 'ENTER_YOUR_OAUTH2_CLIENT_SECRET_HERE');
define('BOT_TOKEN', 'ENTER_YOUR_BOT_TOKEN_HERE');
define('BOT_ID', 'ENTER_YOUR_BOT_ID_HERE');
define('BASE_URL', 'https://discord.com/api/');
define('AUTHORIZE_URL', BASE_URL . 'oauth2/authorize');
define('TOKEN_URL', BASE_URL . 'oauth2/token');
define('TOKEN_REVOKE_URL', BASE_URL . 'oauth2/token/revoke');
define('API_URL_ME', BASE_URL . 'users/@me');
define('API_URL_GUILDS', BASE_URL . 'users/@me/guilds');
define('CALLBACK_CODE_PARAMETER', 'code');
define('REDIRECT_URI', 'https://geniusbot.gaborszita.net/login/callback');
?>
