<?php
class HTMLResponseBuilder
{
    public $head = '';
    public $body = '';

    function constructHTML()
    {
        return '<!DOCTYPE html><html><head>' . $this->head . '</head><body>' . $this->body . '</body></html>';
    }
}
?>
