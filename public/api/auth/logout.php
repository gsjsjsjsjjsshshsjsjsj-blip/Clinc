<?php
require __DIR__ . '/../_bootstrap.php';

use App\Support\Auth;
use App\Support\Response;

Auth::logout();
Response::json(['message' => 'Logged out']);
