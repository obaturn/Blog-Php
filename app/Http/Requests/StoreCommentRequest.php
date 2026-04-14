<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreCommentRequest - Validates comment creation requests
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'post_id' => 'required|integer|exists:posts,id',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string|min:1|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'post_id.required' => 'A post ID is required.',
            'post_id.exists' => 'The specified post does not exist.',
            'content.required' => 'Comment content is required.',
            'content.min' => 'Comment must be at least 1 character.',
            'content.max' => 'Comment cannot exceed 2000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Extract post_id from URL if not in request body
        if (!$this->has('post_id') && $this->route('post')) {
            $this->merge(['post_id' => $this->route('post')]);
        }
    }
}