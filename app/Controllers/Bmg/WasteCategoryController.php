<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\WasteCategoryModel;

/**
 * WasteCategoryController — manages waste type taxonomy.
 *
 * Used by the BMG module to tag batches by waste type, enabling
 * comparative analysis of decomposition duration and yield across
 * different waste compositions.
 */
class WasteCategoryController extends BaseController
{
    protected WasteCategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new WasteCategoryModel();
        helper(['form']);
    }

    /**
     * List all waste categories.
     */
    public function index()
    {
        return view('bmg/categories/index', [
            'title'      => 'Waste Categories — SYNAPSE',
            'heading'    => 'Waste Categories',
            'categories' => $this->categoryModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return view('bmg/categories/create', [
            'title'   => 'New Category — SYNAPSE',
            'heading' => 'Add Waste Category',
        ]);
    }

    /**
     * Store new category.
     */
    public function store()
    {
        $rules = [
            'code'  => 'required|max_length[50]|is_unique[waste_categories.code]',
            'name'  => 'required|max_length[100]',
            'expected_yield_pct' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Skip the model's auto-validation — controller already validated above.
        $this->categoryModel->skipValidation(true);
        $id = $this->categoryModel->insert([
            'code'                    => strtolower(trim($this->request->getPost('code'))),
            'name'                    => $this->request->getPost('name'),
            'description'             => $this->request->getPost('description'),
            'expected_yield_pct'      => $this->request->getPost('expected_yield_pct') ?: null,
            'reference_duration_days' => (int) ($this->request->getPost('reference_duration_days') ?: 45),
            'is_active'               => 1,
        ], true);

        if (! $id) {
            $errors = $this->categoryModel->errors() ?: ['Unknown database error.'];
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        return redirect()->to('/bmg/categories/' . $id)->with('success', 'Category added.');
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to('/bmg/categories')->with('error', 'Category not found.');
        }

        return view('bmg/categories/edit', [
            'title'    => 'Edit Category — SYNAPSE',
            'category' => $category,
        ]);
    }

    /**
     * Update category.
     */
    public function update($id)
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to('/bmg/categories')->with('error', 'Category not found.');
        }

        $this->categoryModel->update($id, [
            'name'                    => $this->request->getPost('name'),
            'description'             => $this->request->getPost('description'),
            'expected_yield_pct'      => $this->request->getPost('expected_yield_pct') ?: null,
            'reference_duration_days' => (int) ($this->request->getPost('reference_duration_days') ?: 45),
            'is_active'               => $this->request->getPost('is_active') ?: 0,
        ]);

        return redirect()->to('/bmg/categories')->with('success', 'Category updated.');
    }

    /**
     * Delete category (only if not used by any batch).
     */
    public function delete($id)
    {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return redirect()->to('/bmg/categories')->with('error', 'Category not found.');
        }

        // Check if any batch uses this category
        $batchModel = new \App\Models\BmgBatchModel();
        $usage = $batchModel->where('waste_category_id', $id)->countAllResults();
        if ($usage > 0) {
            return redirect()->to('/bmg/categories')
                ->with('error', "Cannot delete: category is used by {$usage} batch(es). Mark it inactive instead.");
        }

        $this->categoryModel->delete($id);
        return redirect()->to('/bmg/categories')->with('success', 'Category deleted.');
    }
}