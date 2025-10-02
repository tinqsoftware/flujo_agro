<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataSourceApiController;
use App\Http\Controllers\Api\FormulasApiController;

Route::post('/datasource/record-meta', [DataSourceApiController::class, 'recordMeta']);
Route::post('/formulas/eval',         [FormulasApiController::class, 'evaluate']);
Route::get('/api/datasource/table-options', [DataSourceApiController::class, 'tableOptions']);

