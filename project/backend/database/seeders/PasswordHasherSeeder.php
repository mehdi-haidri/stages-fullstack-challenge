<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PasswordHasherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        /**
         * Run the database seeds.
         *
         * This ensures all existing users with plain text passwords are updated
         * with hashed passwords, preventing a major security vulnerability.
         */
        foreach ($users as $user) {
            // Check if the password is NOT already hashed.
            // A simple check is usually sufficient in a migration context.
            // Laravel's Hash::needsRehash() is an alternative, but checking length/format often works too.

            if (Hash::needsRehash($user->password)) {
                $user->password = Hash::make($user->password);
                $user->save();
            }
        }
    }
}
