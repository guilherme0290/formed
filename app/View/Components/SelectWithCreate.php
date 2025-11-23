<?php

namespace App\View\Components\Funcoes;

use Illuminate\View\Component;
use Illuminate\Support\Str;

class SelectWithCreate extends Component
{
    public string $name;
    public string $label;
    public $funcoes;
    public $selected;
    public string $fieldId;
    public string $modalId;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $name,
        string $label = 'Função',
               $funcoes = [],
               $selected = null,
        ?string $fieldId = null
    ) {
        $this->name     = $name;
        $this->label    = $label;
        $this->funcoes  = $funcoes;
        $this->selected = $selected;

        // IDs únicos para não conflitar em páginas com vários componentes
        $uid = Str::uuid()->toString();

        $this->fieldId = $fieldId ?: 'funcao-select-' . $uid;
        $this->modalId = 'modal-nova-funcao-' . $uid;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.funcoes.select-with-create');
    }
}
