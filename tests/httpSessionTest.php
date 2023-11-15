<?php

use Cocoon\Http\Facades\Session;
use PHPUnit\Framework\TestCase;
Session::start();
class httpSessionTest extends TestCase
{
	public function testIdSessionTrue()
    {
        $this->assertTrue(Session::isSession());
    }
}