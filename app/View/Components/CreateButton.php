<?php

namespace App\View\Components;


use Illuminate\View\Component;

class CreateButton extends Component
{
    public string $label;
    public string $route;
    public string $csrf;

    public function __construct(string $label = 'Cadastrar nova função')
    {
        $this->label = $label;
        $this->route = route('operacional.funcoes.store-ajax');
        $this->csrf  = csrf_token();
    }

    public function render()
    {
        return view('components.funcoes.create-button');
    }
}
