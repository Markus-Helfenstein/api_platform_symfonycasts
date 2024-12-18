<?php

namespace App\Tests\Functional;

use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserResourceTest extends BaseApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testRegisterAndLogin(): void
    {
        $email = 'draggin-in-the-morning@coffee.com';
        $password = 'Pa$$w0rd';

        $this->browser()
            ->post('/api/users', [
                'json' => [
                    'email' => $email,
                    'username' => 'anything',
                    'password' => $password,
                ],
            ])
            ->assertStatus(201)
            ->post('/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ])
            ->dump()
            ->assertSuccessful()
        ;            
    }
}