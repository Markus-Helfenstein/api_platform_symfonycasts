<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
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
            ->assertSuccessful()
        ;
    }

    public function testPatchToUpdateUser(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->patch('/api/users/' . $user->getId(), [
                'json' => [
                    'username' => 'changed',
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ]
            ])
            ->assertStatus(200)
        ;
    }
}