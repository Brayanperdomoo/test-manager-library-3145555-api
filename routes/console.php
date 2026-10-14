<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:library', function () { $this->info('Manager Library API is ready.'); });
