<?php

class SV_ThreadReplyBanner_Listener
{
    const AddonNameSpace = 'SV_ThreadReplyBanner_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class;
    }
}