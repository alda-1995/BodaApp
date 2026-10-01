<?php

namespace App\View\Components\Tables;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Pagination\LengthAwarePaginator;

class TableWithPagination extends Component
{
     /**
     * @param array $columns Estructura: [['label' => 'Nombre', 'width' => '200px', 'render' => fn($item) => e($item->name)]]
     * @param LengthAwarePaginator $items
     */
    public function __construct(public array $columns,
        public LengthAwarePaginator $items)
    {

    }

    public function render(): View|Closure|string
    {
        return view('components.tables.table-with-pagination');
    }
}
