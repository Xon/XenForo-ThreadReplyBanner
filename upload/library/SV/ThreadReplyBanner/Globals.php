<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_ThreadReplyBanner_Globals
{
    /** @var SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread */
    public static $controller = null;

    private function __construct() { }
}
