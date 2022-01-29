<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;


class IndexController extends BaseController
{
    public function index()
    {
        // Return OK:
        return (new Status\Ok(['message' => 'Hello world!']))->send();
    }
}