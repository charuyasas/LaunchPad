<?php

namespace App\Console\Commands;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * LaunchPad has no public sign-up, so accounts are created from the server.
 */
class CreateUserCommand extends Command
{
    use ProfileValidationRules;

    protected $signature = 'launchpad:user
        {--name= : Display name}
        {--email= : Login email}
        {--password= : Password (prompted when omitted)}';

    protected $description = 'Create a user who can sign in to LaunchPad';

    public function handle(): int
    {
        $input = [
            'name' => $this->option('name') ?: text('Name', required: true),
            'email' => Str::lower((string) ($this->option('email') ?: text('Email', required: true))),
            'password' => $this->option('password') ?: password('Password', required: true),
        ];

        $validator = Validator::make($input, [
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($input);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Created {$user->email}. They can now sign in.");

        return self::SUCCESS;
    }
}
