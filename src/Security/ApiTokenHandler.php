<?php

namespace App\Security;

use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;

// class ApiTokenHandler implements AccessTokenHandlerInterface
// {
//     public function __construct(private ApiTokenRespository $apiTokenRepository)
//     {
        
//     }

//     public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
//     {
//         $token = $this->apiTokenRepository->findOneBy(['token' => $accessToken]);
//     }
// }