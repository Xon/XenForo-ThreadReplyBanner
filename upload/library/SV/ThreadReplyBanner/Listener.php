<?php

class SV_ThreadReplyBanner_Listener
{
    public static function load_class($class, array &$extend)
    {
        $extend[] = 'SV_ThreadReplyBanner_' . $class;
    }
}
