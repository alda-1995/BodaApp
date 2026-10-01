<?php

namespace App\Http\Controllers;

use App\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected TemplateService $templateService
    ) {}

    public function index(Request $request): View
    {
        $templates = $this->templateService->getAllActive();
        return view("home", compact("templates"));
    }
}