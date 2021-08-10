<?php
require_once 'common-defines.php';

function logout()
{
    session_destroy();
    return true;
}
?>
