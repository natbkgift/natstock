<?php

namespace Illuminate\Database;

abstract class Seeder
{
    abstract public function run(): void;
}
