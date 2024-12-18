<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\Json;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends BaseApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testGetCollectionOfTreasures(): void
    {
        DragonTreasureFactory::createMany(5);

        $this->browser()
            ->get('/api/treasures')
            ->assertJson()
            ->assertJsonMatches('"hydra:totalItems"', 5)
            // works like dependency injection: function argument type hint is identified, then appropriate object is passed on
            ->use(function(Json $json) {
                $json->assertMatches('keys("hydra:member"[0])', [
                    '@id',
                    '@type',
                    'name',
                    'description',
                    'value',
                    'coolFactor',
                    'owner',
                    'shortDescription',
                    'plunderedAtAgo',
                ]);
            })
        ;
    }

    public function testPostToCreateTreasure(): void
    {
        // $password = 'pass';
        // $user = UserFactory::createOne(['password' => $password]);
        $user = UserFactory::createOne();

        $this->browser()
            // ->post('/login', [
            //     'json' => [
            //         'email' => $user->getEmail(),
            //         'password' => $password,
            //     ]
            // ])
            // ->assertStatus(204)
            ->actingAs($user)
            ->post('/api/treasures', [
                'json' => [],
            ])
            ->assertStatus(422)
            ->post('/api/treasures', [
                'json' => [
                    'name' => 'dsfhj',
                    'description' => 'shdjksghdf\nsdfhjskfdghdfjk',
                    'value' => 1000,
                    'coolFactor' => 0,
                    'owner' => '/api/users/' . $user->getId()
                ],
            ])
            ->assertStatus(201)
            ->assertJsonMatches('name', 'dsfhj')
            ->assertHeaderContains('content-type', 'application/ld+json; charset=utf-8')
        ;
    }

    public function testPostToCreateTreasureWithApiKey(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_CREATE],
        ]);

        $this->browser()
            // has to be configured properly before it works:
            // ->authWithToken([ApiToken::SCOPE_TREASURE_CREATE])
            // Validation error
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token->getToken()
                ]
            ])
            ->assertStatus(422)
            // ->post('/api/treasures', [
            //     'json' => [],
            //     'headers' => [
            //         'Authorization' => 'Bearer ' . $token->getToken()
            //     ]
            // ])
            // ->assertStatus(201)
        ;
    }

    public function testPostToCreateTreasureDeniedWithInvalidToken(): void
    {
        $tokenWithMissingScope = ApiTokenFactory::createOne([
            'scopes' => [],
        ]);

        $expiredToken = ApiTokenFactory::createOne([
            'expiresAt' => \DateTimeImmutable::createFromMutable(date_add(new \DateTime(), \DateInterval::createFromDateString('-10 seconds')))
        ]);

        $this->browser()
            // Invalid credentials
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer Foo'
                ]
            ])
            ->assertStatus(401)
            ->assertHeaderContains('www-authenticate', 'Bearer error="invalid_token",error_description="Invalid credentials."')
            // Token expired
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer ' . $expiredToken->getToken()
                ]
            ])
            ->assertStatus(401)
            ->assertHeaderContains('www-authenticate', 'Bearer error="invalid_token",error_description="Token expired"')
            // Missing scope
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenWithMissingScope->getToken()
                ]
            ])
            ->assertStatus(403)
        ;
    }

    public function testPatchToUpdateTreasureAsOwner(): void
    {
        $owner = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $owner
        ]);

        $this->browser()
            ->actingAs($owner)
            ->patch('/api/treasures/' . $treasure->getId(), [
                'json' => [
                    'value' => 12345
                ],
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
        ;
    }

    public function testPatchToUpdateTreasureForbiddenForOthers(): void
    {
        $otherUser = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne();

        $this->browser()
            ->actingAs($otherUser)
            ->patch('/api/treasures/' . $treasure->getId(), [
                'json' => [
                    'value' => 12345
                ],
            ])
            ->assertStatus(403)
        ;
    }

    public function testPatchToUpdateTreasureOwnershipImmutable(): void
    {
        $otherUser = UserFactory::createOne();
        $owner = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $owner
        ]);

        $this->browser()
            ->actingAs($owner)
            ->patch('/api/treasures/' . $treasure->getId(), [
                'json' => [
                    'owner' => '/api/users/' . $otherUser->getId()
                ],
            ])
            ->assertStatus(403)
        ;
    }

    public function testAdminCanPatchToEditTreasure(): void
    {
        $admin = UserFactory::new()->withAdminRole()->create();
        $someonesTreasure = DragonTreasureFactory::createOne([
            'isPublished' => false,
        ]);

        $this->browser()
            ->actingAs($admin)
            ->patch('/api/treasures/' . $someonesTreasure->getId(), [
                'json' => [
                    'value' => 12345
                ],
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ->assertJsonMatches('isPublished', false)
        ;
    }

    public function testOwnerCanSeeIsPublishedField(): void
    {
        $owner = UserFactory::createOne();
        $someonesTreasure = DragonTreasureFactory::createOne([
            'isPublished' => false,
            'owner' => $owner
        ]);

        $this->browser()
            ->actingAs($owner)
            ->patch('/api/treasures/' . $someonesTreasure->getId(), [
                'json' => [
                    'value' => 12345
                ],
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ->assertJsonMatches('isPublished', false)
        ;
    }
}