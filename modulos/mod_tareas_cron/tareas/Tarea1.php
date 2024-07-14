<?php

class Tarea1
{
    public function execute()
    {
        error_log('llego');
        exec("php -v > resultado.txt &");

        //evento_termine(idtarea);
    }
}
