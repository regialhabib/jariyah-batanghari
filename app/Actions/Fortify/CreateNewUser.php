<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Registrasi publik khusus Nazir. Role TIDAK PERNAH diambil dari input (docs/02, docs/06).
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $data = Validator::make($input, [
            ...$this->profileRules(),
            'nik' => $this->nikRules(),
            'phone' => $this->phoneRules(),
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ], [
            ...$this->profileMessages(),
            'terms.accepted' => 'Anda harus menyetujui Syarat dan Ketentuan yang berlaku.',
        ], $this->profileAttributes())->validate();

        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'nik' => $data['nik'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->assignRole(Role::Nazir->value);

            return $user;
        });
    }
}
