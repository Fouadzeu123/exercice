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

        $referralCode = null;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, 35)];
            }
            $referralCode = $code;
        } while (User::where('referral_code', $referralCode)->exists());

        return User::create([
            'phone' => $input['phone'],
            'password' => $input['password'],
            'referral_code' => $referralCode,
            'referrer_id' => $referrer_id,
            'draw_spins' => 0,
        ]);
    }
}
