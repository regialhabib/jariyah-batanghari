<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Format No. HP Indonesia: 08…, 628…, atau +628…, total 10–15 digit.
     */
    protected const POLA_NO_HP = '/^(\+62|62|0)8[0-9]{7,12}$/';

    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * NIK: 16 digit angka dan unik.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nikRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'digits:16',
            $userId === null
                ? Rule::unique(User::class, 'nik')
                : Rule::unique(User::class, 'nik')->ignore($userId),
        ];
    }

    /**
     * No. HP Indonesia.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:'.self::POLA_NO_HP,
        ];
    }

    /**
     * Pesan validasi khusus untuk field profil.
     *
     * @return array<string, string>
     */
    protected function profileMessages(): array
    {
        return [
            'phone.regex' => 'Format No. HP tidak valid. Gunakan nomor seluler Indonesia, contoh: 081234567890.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }

    /**
     * Nama field yang tampil di pesan validasi.
     *
     * @return array<string, string>
     */
    protected function profileAttributes(): array
    {
        return [
            'name' => 'Nama Lengkap',
            'nik' => 'NIK',
            'email' => 'Email',
            'phone' => 'No. HP',
            'password' => 'Password',
            'terms' => 'Syarat dan Ketentuan',
        ];
    }
}
