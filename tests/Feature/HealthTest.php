<?php

use Illuminate\Support\Facades\DB;

it('reports healthy without authentication', function (): void {
    $this->get('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'database' => 'ok']);
});

it('checks the database connection', function (): void {
    DB::shouldReceive('connection->select')
        ->once()
        ->with('select 1')
        ->andThrow(new RuntimeException('connection refused'));

    $this->get('/up')
        ->assertServiceUnavailable()
        ->assertExactJson(['status' => 'error', 'database' => 'unreachable']);
});
