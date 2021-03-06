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
