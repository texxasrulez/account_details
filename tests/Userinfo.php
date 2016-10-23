<?php

class Userinfo_Plugin extends PHPUnit_Framework_TestCase
{

    function setUp()
    {
        include_once __DIR__ . '/../moreuserinfo.php';
    }

    /**
     * Plugin object construction test
     */
    function test_constructor()
    {
        $rcube  = rcube::get_instance();
        $plugin = new moreuserinfo($rcube->api);

        $this->assertInstanceOf('moreuserinfo', $plugin);
        $this->assertInstanceOf('rcube_plugin', $plugin);
    }
}

