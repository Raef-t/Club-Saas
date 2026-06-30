<?php

namespace Modules\FormulaEngine\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FormulaEngine\Services\FormulaEngineService;

class UpdateFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formulaId = $this->route('formula');

        return [
            'name'        => 'sometimes|string|max:100',
            'key'         => ['sometimes', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('formulas', 'key')->ignore($formulaId)],
            'expression'  => 'sometimes|string',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->any()) {
                return;
            }

            $expression = $this->input('expression');
            if (!$expression) {
                return;
            }

            $variableNames = collect($this->input('variables', []))->pluck('variable_name')->toArray();

            try {
                app(FormulaEngineService::class)->validateExpression($expression, $variableNames);
            } catch (\Throwable $e) {
                $v->errors()->add('expression', $e->getMessage());
            }
        });
    }
}
