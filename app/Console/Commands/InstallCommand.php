<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'laradooit:install
                            {--name= : The display name of the account}
                            {--email= : The email address used to log in}
                            {--password= : The login password, at least 8 characters}';

    /**
     * @var string
     */
    protected $description = 'Create the single user account for this instance';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->components->error(
                'This instance already has a user. laradooit is single-user, so install refuses to create a second one.'
            );

            return self::FAILURE;
        }

        $name = $this->stringOption('name') ?? text(
            label: 'What name should the account use?',
            required: true,
        );

        $email = $this->stringOption('email') ?? text(
            label: 'What email address will you log in with?',
            required: true,
        );

        $secret = $this->stringOption('password') ?? password(
            label: 'Choose a password, at least 8 characters',
            required: true,
        );

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $secret],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $secret,
        ]);

        $this->components->info("Created the account for {$email}. Log in at ".config('app.url').'/login');

        return self::SUCCESS;
    }

    /**
     * Read an option as a string, treating a missing or empty value as absent.
     */
    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
