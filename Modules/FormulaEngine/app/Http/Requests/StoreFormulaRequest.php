<?php

namespace Modules\FormulaEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\FormulaEngine\Services\FormulaEngineService;

class StoreFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled at route level (middleware)
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:100',
            'key'         => 'required|string|max:50|unique:formulas,key|regex:/^[a-z][a-z0-9_]*$/',
            'expression'  => 'required|string',
            'description' => 'nullable|string',
            'category'    => 'nullable|in:measurement,general',
            'return_type' => 'nullable|in:float,int,bool,string',
            'unit'        => 'nullable|string|max:20',
            'is_active'   => 'nullable|boolean',

            'variables'                         => 'nullable|array',
            'variables.*.variable_name'         => 'required|string|regex:/^[a-z][a-z0-9_]*$/',
            'variables.*.source_type'           => 'required|in:input,measurement,computed',
            'variables.*.db_column'             => 'nullable|string',
            'variables.*.computed_formula_key'  => 'nullable|string|exists:formulas,key',
            'variables.*.is_required'           => 'nullable|boolean',
            'variables.*.default_value'         => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'key.unique'   => 'مفتاح المعادلة مستخدم مسبقاً.',
            'key.regex'    => 'مفتاح المعادلة يجب أن يكون حروف صغيرة وأرقام وشرطة سفلية فقط.',
        ];
    }

    /**
     * Validate the expression syntax after basic validation passes.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->any()) {
                return;
            }

            $expression    = $this->input('expression');
            $variableNames = collect($this->input('variables', []))->pluck('variable_name')->toArray();

            try {
                app(FormulaEngineService::class)->validateExpression($expression, $variableNames);
            } catch (\Throwable $e) {
                $v->errors()->add('expression', $e->getMessage());
            }
        });
    }
}
