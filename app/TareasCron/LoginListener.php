<?php

//namespace App\TareasCron;

class LoginListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function execute()
    {
        error_log('Paso por aqui--->'.time());

    }
}
