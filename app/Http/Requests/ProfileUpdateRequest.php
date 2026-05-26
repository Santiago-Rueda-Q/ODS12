<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /** @var list<string> */
    private const COUNTRIES = [
        'Colombia',
        'México',
        'Argentina',
        'Chile',
        'Perú',
        'Ecuador',
        'Venezuela',
        'Bolivia',
        'Paraguay',
        'Uruguay',
    ];

    protected function prepareForValidation(): void
    {
        $data = [];
        $input = $this->all();



        if (array_key_exists('linkedin', $input)) {
            $linkedin = $this->input('linkedin');
            if (is_string($linkedin)) {
                $linkedin = trim($linkedin);
                if ($linkedin !== '' && Str::contains($linkedin, 'linkedin.com', true)) {
                    $linkedin = (string) preg_replace('#^https?://([^/]+\.)?linkedin\.com/in/#i', '', $linkedin);
                    $linkedin = rtrim($linkedin, '/');
                }
                $linkedin = $linkedin !== '' ? $linkedin : null;
            } else {
                $linkedin = null;
            }
            $data['linkedin'] = $linkedin;
        }

        if (array_key_exists('username', $input)) {
            $un = $this->input('username');
            $data['username'] = is_string($un) ? strtolower(trim($un)) : $un;
        }

        if (array_key_exists('profile_edit_form', $input)) {
            $bd = $this->input('birthdate');
            $data['birthdate'] = ($bd === null || $bd === '') ? null : $bd;

            $prefs = $this->input('preferences');
            $data['preferences'] = is_array($prefs) ? array_values(array_unique($prefs)) : [];
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'lowercase',
                'alpha_dash',
                'min:3',
                'max:30',
                Rule::unique(User::class, 'username')->ignore($this->user()->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'country' => ['required', 'string', Rule::in(self::COUNTRIES)],
            'description' => ['nullable', 'string', 'max:500'],
            'linkedin' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\-]{3,100}$/'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['string', Rule::in(User::PREFERENCE_OPTIONS)],
            'profile_photo_base64' => ['nullable', 'string', 'max:3500000', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                if (! is_string($value)) {
                    $fail('La imagen del avatar no es válida.');

                    return;
                }
                if (! preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $value)) {
                    $fail('La imagen del avatar debe ser PNG, JPEG o WebP.');

                    return;
                }
                $stripped = (string) preg_replace('/^data:image\/\w+;base64,/', '', $value);
                $stripped = str_replace(' ', '+', $stripped);
                $binary = base64_decode($stripped, true);
                if ($binary === false) {
                    $fail('La imagen del avatar no es válida.');

                    return;
                }
                if (strlen($binary) > 2048 * 1024) {
                    $fail('La foto de perfil no puede superar los 2 MB.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.lowercase' => 'El nombre de usuario debe estar en minúsculas.',
            'username.alpha_dash' => 'Solo letras, números, guiones y guiones bajos.',
            'username.min' => 'El nombre de usuario debe tener al menos :min caracteres.',
            'username.max' => 'El nombre de usuario no puede superar :max caracteres.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'first_name.required' => 'Escribe tu nombre.',
            'last_name.required' => 'Escribe tu apellido.',
            'email.required' => 'Necesitamos tu correo electrónico.',
            'email.email' => 'Ese correo no es válido.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
            'country.required' => 'Debes seleccionar un país.',
            'country.in' => 'Selecciona un país válido de la lista.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'linkedin.regex' => 'El perfil de LinkedIn debe ser un slug válido (letras, números y guiones).',
            'birthdate.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'preferences.*.in' => 'Hay una preferencia no válida.',
        ];
    }
}
