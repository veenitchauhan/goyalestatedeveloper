<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--local-bootstrap : Generate local-only login details in private storage}';

    protected $description = 'Interactively create the first Super Admin without storing a password in shell history';

    public function handle(AuditRecorder $audit): int
    {
        if (User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->exists()) {
            $this->error('A Super Admin already exists. Use authorized user management.');

            return self::FAILURE;
        }
        if ($this->option('local-bootstrap') && ! app()->environment('local')) {
            $this->error('Generated bootstrap accounts are only permitted in the local environment.');

            return self::FAILURE;
        }
        $data = $this->option('local-bootstrap')
            ? ['name' => 'Local Administrator', 'email' => 'admin@goyalestatedeveloper.test', 'password' => bin2hex(random_bytes(24)).'Aa1']
            : ['name' => $this->ask('Full name'), 'email' => strtolower((string) $this->ask('Email')), 'password' => $this->secret('Password (8+ characters with letters and numbers)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:150', 'email' => 'required|email|unique:users,email', 'password' => ['required', Password::min(8)->letters()->numbers()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data, $audit) {
            $role = Role::where('name', 'super-admin')->firstOrFail();
            $user = User::create($data);
            $user->roles()->attach($role);
            $audit->record('user.bootstrap_created', $user, actorId: $user->id);
        });
        if ($this->option('local-bootstrap')) {
            $path = storage_path('app/private/local-admin.json');
            $oldMask = umask(0077);
            try {
                file_put_contents($path, json_encode(['url' => route('login'), 'email' => $data['email'], 'password' => $data['password']], JSON_PRETTY_PRINT), LOCK_EX);
            } finally {
                umask($oldMask);
            }
            $this->info('Local login details saved to storage/app/private/local-admin.json.');
        }
        $this->info('Super Admin created. Sign in and enroll an authenticator app.');

        return self::SUCCESS;
    }
}
