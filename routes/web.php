<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

require app_path('Dashboard/modules/Sites/IO/Routes/web.php');

require __DIR__.'/settings.php';
