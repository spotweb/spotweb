<?php

abstract class Notifications_abs
{
    public function __construct()
    {
    }

    /* registreert een service bij een host
     * Gezocht: betere omschrijving :) */
    abstract public function register();

    /* verstuurt het bericht */
    abstract public function sendMessage($type, $title, $body, $sourceUrl, $smtp);
} // SpotNotifyService_abs
