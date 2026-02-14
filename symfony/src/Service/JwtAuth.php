<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth
{
    private UserRepository $userRepo;
    private string $key;

    public function __construct(UserRepository $userRepo, string $jwtSecret)
    {
        $this->userRepo = $userRepo;
        $this->key = $jwtSecret;
    }

    public function signup(User $user, bool $getHash = false)
    {


        $token = [
            "sub" => $user->getId(),
            "email" => $user->getEmail(),
            "name" => $user->getName(),
            "surname" => $user->getSurname(),
            'role' => $user->getRole(),
            "iat" => time(),
            "exp" => time() + (7 * 24 * 60 * 60)
        ];
        $jwt = JWT::encode($token, $this->key, 'HS256');
        $decoded = JWT::decode($jwt, new Key($this->key, 'HS256'));

        return $getHash ? $jwt : $decoded;
    }

    public function checkToken(string $jwt, bool $getIdentity = false): bool|object{
        try {
            $decoded = JWT::decode($jwt, new Key($this->key, 'HS256'));
        }catch(\Throwable $e){
            return false;
        }

        if(isset($decoded->sub)){
            return $getIdentity ? $decoded : true;
        }

        return false;
    }
}

