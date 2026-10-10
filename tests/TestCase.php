<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Enumerated columns are native Postgres enum types, so a wiped test
     * database must drop its types too, or the next migrate:fresh fails on
     * CREATE TYPE.
     *
     * @var bool
     */
    protected $dropTypes = true;
}
