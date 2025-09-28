<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

tests()->beforeEach(function () {
    $this->withoutVite();
});
