# Discord Genius Bot - Dashboard code

Main repository of this project: [https://github.com/gaborszita/discord-genius-bot](https://github.com/gaborszita/discord-genius-bot)

Dashboard setup:

1. Web server configuration setup:

   Copy the .htaccess.example file and name it .htaccess. Also, make sure 
   that you're web server has mod_rewrite enabled.
   
   Alternatively, you may choose to put the config in the htaccess file in 
   the web server config.
   
   Configure your web server to serve the root directory of this repo.
   
2. Secrets and API keys:

   1. Copy the common-defines.example.php located in the php-files directory 
   and name it common-defines.php. Replace every ```ENTER_SOMETHING_HERE``` 
   with the correct value.
   
   2. Copy the common-defines.example.php locatedin the 
   php-files/oauth2/discord directory and name it common-defines.php. Replace 
   every ```ENTER_SOMETHING_HERE``` with the correct value.
   
