<?php

class Tarea1
{

    public function execute()
    {

        //$fecha = date_parse(getdate());
        exec("php -v > resultado.txt &");
        
        //header("Location: TareaTerminada.php?tarea=1");
    }
}
