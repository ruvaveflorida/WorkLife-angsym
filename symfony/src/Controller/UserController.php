<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\JwtAuth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }

    #[Route('/user/new', name: 'app_user_new', methods: ['POST'])]
    public function newUser(
        Request                $request,
        EntityManagerInterface $em,
        ValidatorInterface     $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        // Obtiene el cuerpo de la peticion en formato JSON
        $json = $request->getContent();
        $params = json_decode($json);

        // Respuesta por defecto en caso de error
        $data = [
            'status' => 'error',
            'code' => 400,
            'msg' => 'User not created !!'
        ];

        // Comprueba que el JSON se haya decodificado correctamente
        if ($params !== null) {
            // Fecha de creacion del usuario
            $createdAt = new \DateTimeImmutable();
            // Rol por defecto del usuario
            $role = $params->role ?? 'user';
            // Obtiene los campos del JSON o null si no existen
            $email = $params->email ?? null;
            $name = $params->name ?? null;
            $surname = $params->surname ?? null;
            $password = $params->password ?? null;

            // Validacion de email
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "This email is not valid !!";
            $validateEmail = $validator->validate($email, $emailConstraint);

            // Comprueba que todos los campos obligatorios sean válidos
            if ($email && count($validateEmail) === 0 && $password && $name && $surname) {
                // Verificar si ya existe el usuario
                $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                //Si no existe lo crea
                if (!$existingUser) {
                    $user = new User();
                    $user->setCreatedAt($createdAt);
                    $user->setRole($role);
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setPassword($password);

                    //Cifrar la password
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);


                    // Prepara el usuario para guardarlo
                    $em->persist($user);
                    // Guarda el usuario en la base de datos
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'msg' => 'New user created !!',
                        'user' => [
                            'id' => $user->getId(),
                            'email' => $user->getEmail(),
                            'name' => $user->getName(),
                            'surname' => $user->getSurname(),
                            'role' => $user->getRole(),
                            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                        ]
                    ];
                } else {
                    $data['msg'] = 'User already exists !!';
                }
            } else {
                $data['msg'] = 'Invalid or missing fields !!';
            }
        }

        return $this->json($data);
    }

    #[Route('/user/edit', name: 'app_user_edit', methods: ['POST'])]
    public function editUser(
        Request                $request,
        EntityManagerInterface $em,
        ValidatorInterface     $validator,
        JwtAuth                $jwtAuth,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        // Obtiene el token JWT del header Authorization
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7); // Quita "Bearer "
        }

        if (!$token || !$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        // Obtiene los datos del usuario a partir del token
        $identity = $jwtAuth->checkToken($token, true);

        // Busca el usuario en la base de datos por su id
        $user = $em->getRepository(User::class)->find($identity->sub);

        // Si el usuario no existe, devuelve error
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'code' => 404,
                'msg' => 'User not found'
            ], 404);
        }

        // Decodifica el JSON enviado en el body de la peticion
        $params = json_decode($request->getContent(), true);

        // Si el JSON es invalido, devuelve error
        if (!$params) {
            return $this->json([
                'status' => 'error',
                'code' => 400,
                'msg' => 'Invalid JSON'
            ], 400);
        }

        // Si viene el email en el JSON
        if (isset($params['email'])) {
            // Define la validación de formato email
            $emailConstraint = new Assert\Email();
            // Valida el email recibido
            $errors = $validator->validate($params['email'], $emailConstraint);

            // Si el email no es valido, devuelve error
            if (count($errors) > 0) {
                return $this->json([
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Invalid email'
                ], 400);
            }
            // Busca si ya existe otro usuario con ese email
            $existingUser = $em->getRepository(User::class)
                ->findOneBy(['email' => $params['email']]);
            // Comprueba que el email no pertenezca a otro usuario distinto
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json([
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Email already in use'
                ], 400);
            }
            // Actualiza el email del usuario
            $user->setEmail($params['email']);
        }


        // Actualiza si viene el nombre, surname,password
        if (isset($params['name'])) {
            $user->setName($params['name']);
        }

        if (isset($params['surname'])) {
            $user->setSurname($params['surname']);
        }

        if (
            isset($params['password']) &&
            !empty($params['password']) &&
            strlen($params['password']) >= 3
        ) {
            $hashedPassword = $passwordHasher->hashPassword($user, $params['password']);
            $user->setPassword($hashedPassword);
        }

        $em->flush();

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'msg' => 'User updated',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
            ]
        ]);
    }

    #[Route('/user/all', name: 'app_user_all', methods: ['GET'])]
    public function getAllUsers(Request $request, EntityManagerInterface $em, JwtAuth $jwtAuth): JsonResponse
    {
        // Obtener token
        $token = $request->headers->get('Authorization');
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // Validar token
        if (!$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        // Identidad del usuario
        $identity = $jwtAuth->checkToken($token, true);

        // Comprobar si es admin
        if (!isset($identity->role) || $identity->role !== 'admin') {
            return $this->json([
                'status' => 'error',
                'code' => 403,
                'msg' => 'Access denied'
            ], 403);
        }

        // Traer todos los usuarios
        $users = $em->getRepository(User::class)->findAll();
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()
            ];
        }

        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
    }

}
