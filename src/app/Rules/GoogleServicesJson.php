<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GoogleServicesJson implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure it's a valid uploaded file
        if (! $value->isValid()) {
            $fail('The :attribute file upload failed.');
            return;
        }

        // Must be .json file
        if ($value->getClientOriginalExtension() !== 'json') {
            $fail('The :attribute must have a .json extension.');
            return;
        }

        // Read contents
        $content = @file_get_contents($value->getRealPath());
        if ($content === false) {
            $fail('The :attribute could not be read.');
            return;
        }

        // Decode JSON
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('The :attribute must contain valid JSON.');
            return;
        }

        // Check required structure
        $requiredKeys = ['project_info', 'client'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $json)) {
                $fail("The :attribute is missing the required key: {$key}.");
                return;
            }
        }

        // Validate Firebase-specific structure
        $clientValid = false;
        foreach ($json['client'] as $client) {
            if (
                isset($client['client_info']['mobilesdk_app_id']) &&
                isset($client['api_key'][0]['current_key'])
            ) {
                $clientValid = true;
                break;
            }
        }

        if (! $clientValid) {
            $fail('The :attribute does not appear to be a valid google-services.json file.');
        }
    }
}
