<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JwtAuth;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MainController.php',
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'] )]
    public function login(
        Request $request,
        ValidatorInterface $validator,
        JwtAuth $jwtAuth,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse{

        $content = $request->getContent();
        $json = json_decode($content, true);

        if ($json === null) {
            return $this->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $email = $json['email'] ?? null;
        $password = $json['password'] ?? null;
        $getHash = $json['getHash'] ?? false;

        $emailConstraint = new Assert\Email();
        $errors = $validator->validate($email, $emailConstraint);


        if (count($errors) > 0 || !$password) {
            return $this->json(['status' => 'error', 'message' => 'Invalid credentials']);
        }

        // Busca el usuario en la base de datos
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'debug' => 'USER NOT FOUND',
                'email' => $email
            ], 401);
        }


        // Verifica la contraseña usando el hasher
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        return $this->json(
            $jwtAuth->signup($user, $getHash)
        );

    }

    #[Route('/pruebas', name: 'app_pruebas', methods: ['POST'] )]
    public function pruebas(UserRepository $userRepo, Request $request, JwtAuth $jwtAuth): JsonResponse
    {
        $token = $request->headers->get('Authorization');



        if ($token !== null && $jwtAuth->checkToken($token)) {


            $users = $userRepo->findAll();

            $data = [];
            foreach ($users as $user) {
                $data[] = [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'surname' => $user->getSurname(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRole(),
                    'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }

            return $this->json([
                'status' => 'success',
                'data' => $data
            ]);

        } else {

            return $this->json([
                'status' => 'error',
                'code' => 401,
                'data' => 'Authorization not valid'
            ], 401);

        }


    }
}
