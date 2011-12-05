<?php

require_once __DIR__ . '/ODTestSuite.php';

class AllTests extends PHPUnit_Framework_TestSuite
{
    public function __construct()
    {
        $this->addTest(new ODTestSuite());
    }

    public static function suite()
    {
        return new AllTests();
    }
}
