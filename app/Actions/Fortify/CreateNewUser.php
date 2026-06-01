<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'phone' => ['required', 'string', 'max:255', 'unique:users'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
            'password' => $this->passwordRules(),
        ])->validate();

        $referrer_id = null;
        if (!empty($input['referral_code'])) {
            $referrer = User::where('referral_code', $input['referral_code'])->first();
            if ($referrer) {
                $referrer_id = $referrer->id;
            }
        }

        return User::create([
            'phone' => $input['phone'],
            'password' => $input['password'],
            'referral_code' => \Illuminate\Support\Str::random(10),
            'referrer_id' => $referrer_id,
        ]);
    }
}
