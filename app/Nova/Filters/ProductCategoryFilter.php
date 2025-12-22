<?php

namespace App\Nova\Filters;

use App\Models\ProductCategory;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class ProductCategoryFilter extends Filter
{
    public $component = 'select-filter';
    public $name = 'Category';

    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('product_category_id', $value);
    }

    public function options(NovaRequest $request)
    {
        return ProductCategory::pluck('id', 'name')->toArray();
    }
}
