<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('previews first 20 rows with validation highlights')->todo();

it('upserts batches in replace mode and records adjustments')->todo();

it('upserts batches in delta mode and records receive movements')->todo();

it('skips existing batches and receives new ones')->todo();

it('rolls back the entire file in strict mode using a single transaction when any row fails')->todo();

it('commits valid rows and exports errors in lenient mode')->todo();

it('ignores any price columns in the import file')->todo();
