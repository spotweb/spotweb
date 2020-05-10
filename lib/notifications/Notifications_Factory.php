<?php

class Notifications_Factory
{
    public static function build($appName, $provider, array $dataArray)
    {
        // Nieuwe handlers voegen we expliciet toe omdat we anders
        // niet weten wat we includen in combinate met __autoload()
        switch ($provider) {
            case 'boxcar': $handler = new Notifications_Boxcar($appName, $dataArray); break;
            case 'email': $handler = new Notifications_Email($appName, $dataArray); break;
            case 'growl': $handler = new Notifications_Growl($appName, $dataArray); break;
            case 'nma': $handler = new Notifications_NMA($appName, $dataArray); break;
            case 'prowl': $handler = new Notifications_Prowl($appName, $dataArray); break;
            case 'twitter': $handler = new Notifications_Twitter($appName, $dataArray); break;
            default: $handler = false; break;
        } // switch

        return $handler;
    }

    // build()

    public static function getActiveServices()
    {
        return ['boxcar',
            'email',
            'growl',
            'nma',
            'prowl',
            'twitter',
        ];
    }
} // class Notifications_Factory
