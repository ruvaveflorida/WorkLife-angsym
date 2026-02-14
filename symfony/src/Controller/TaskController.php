<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Service\JwtAuth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Serializer\SerializerInterface;


final class TaskController extends AbstractController
{
    #[Route('/task', name: 'app_task')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TaskController.php',
        ]);
    }

    #[Route('/task/new', name: 'app_task_new', methods: ['POST'])]
    public function newTask(
        Request                $request,
        EntityManagerInterface $em,
        JwtAuth                $jwtAuth,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        //Obtener token del header Authorization
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7); // elimina los primeros 7 caracteres
        }
        //Comprobar token
        if (!$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        } else {
            //Token válido -> obtener identidad
            $identity = $jwtAuth->checkToken($token, true);

            //Solo admin puede crear tareas
            if ($identity->role !== 'admin') {
                return $this->json([
                    'status' => 'error',
                    'code' => 403,
                    'msg' => 'Only admin can create tasks'
                ], 403);
            }

            //Obtener JSON del body
            $params = json_decode($request->getContent(), true);

            if (!$params) {
                return $this->json([
                    'status' => 'error',
                    'code' => 400,
                    'msg' => 'Task not created, params failed !!'
                ], 400);
            } else {
                //Campos
                $userId = $identity->sub ?? null;
                $title = $params['title'] ?? null;
                $description = $params['description'] ?? null;
                $status = $params['status'] ?? null;

                if (!$userId || !$title) {
                    //Validacion basica fallida
                    return $this->json([
                        'status' => 'error',
                        'code' => 400,
                        'msg' => 'Task not created, validation failed !!'
                    ], 400);
                } else {
                    //Busca el user
                    $user = $em->getRepository(User::class)->find($userId);

                    if (!$user) {
                        // Usuario no encontrado
                        return $this->json([
                            'status' => 'error',
                            'code' => 404,
                            'msg' => 'User not found'
                        ], 404);
                    } else {
                        // Busca el usuario asignado
                        $assignedUserId = $params['assigned_to'] ?? $identity->sub; // si no se manda, se asigna al admin
                        $assignedUser = $em->getRepository(User::class)->find($assignedUserId);
                        if (!$assignedUser) {
                            return $this->json([
                                'status' => 'error',
                                'code' => 404,
                                'msg' => 'Assigned user not found'
                            ], 404);
                        }

                        //Crea la task
                        $task = new Task();
                        $task->setUser($user);
                        $task->setAssignedTo($assignedUser);
                        $task->setTitle($title);
                        $task->setDescription($description);
                        $task->setStatus($status);
                        $task->setCreatedAt(new \DateTimeImmutable());
                        $task->setUpdatedAt(new \DateTimeImmutable());

                        $em->persist($task);
                        $em->flush();

                        //Devuelve datos de la tarea creada
                        return $this->json([
                            'status' => 'success',
                            'code' => 200,
                            'data' => [
                                'id' => $task->getId(),
                                'title' => $task->getTitle(),
                                'description' => $task->getDescription(),
                                'status' => $task->getStatus(),
                                'user' => [
                                    'id' => $user->getId(),
                                    'email' => $user->getEmail(),
                                ],
                                'assigned_to' => [
                                    'id' => $assignedUser->getId(),
                                    'email' => $assignedUser->getEmail(),
                                ],
                                'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                                'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s')
                            ]
                        ]);
                    }
                }
            }
        }
    }

    #[Route('/task/edit/{id}', name: 'app_task_edit', methods: ['POST'])]
    public function editTask(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        JwtAuth $jwtAuth
    ): JsonResponse
    {
        // Obtener token JWT del header Authorization
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

        // Obtener identidad
        $identity = $jwtAuth->checkToken($token, true);
        $userRole = $identity->role ?? 'user';
        $userId   = $identity->sub;

        // Decodificar JSON
        $params = json_decode($request->getContent(), true);
        if (!$params) {
            return $this->json([
                'status' => 'error',
                'code' => 400,
                'msg' => 'Invalid JSON'
            ], 400);
        }

        // Buscar la tarea
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json([
                'status' => 'error',
                'code' => 404,
                'msg' => 'Task not found'
            ], 404);
        }

        // Solo admin o usuario asignado puede editar
        if ($userRole !== 'admin' && $task->getAssignedTo()->getId() !== $userId) {
            return $this->json([
                'status' => 'error',
                'code' => 403,
                'msg' => 'You are not allowed to edit this task'
            ], 403);
        }

        // Si NO es admin, solo puede cambiar el status
        if ($userRole !== 'admin') {
            if (isset($params['title']) || isset($params['description']) || isset($params['assigned_to'])) {
                return $this->json([
                    'status' => 'error',
                    'code' => 403,
                    'msg' => 'Assigned user can only update the status'
                ], 403);
            }
        }

        // Actualizar campos
        if (isset($params['status'])) {
            $task->setStatus($params['status']);
        }

        // Solo el admin puede tocar estos campos
        if ($userRole === 'admin') {
            if (isset($params['title'])) {
                $task->setTitle($params['title']);
            }
            if (isset($params['description'])) {
                $task->setDescription($params['description']);
            }
            if (isset($params['assigned_to'])) {
                $assignedUser = $em->getRepository(User::class)->find($params['assigned_to']);
                if (!$assignedUser) {
                    return $this->json([
                        'status' => 'error',
                        'code' => 404,
                        'msg' => 'Assigned user not found'
                    ], 404);
                }
                $task->setAssignedTo($assignedUser);
            }
        }

        // Actualizar fecha
        $task->setUpdatedAt(new \DateTimeImmutable());

        // Guardar cambios
        $em->flush();

        // Respuesta
        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'user' => [
                    'id' => $task->getUser()->getId(),
                    'email' => $task->getUser()->getEmail()
                ],
                'assigned_to' => [
                    'id' => $task->getAssignedTo()->getId(),
                    'email' => $task->getAssignedTo()->getEmail()
                ],
                'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }



    #[Route('/task/list', name: 'app_task_list', methods: ['POST'])]
    public function listTask(
        Request $request,
        JwtAuth $jwtAuth,
        EntityManagerInterface $em,
        \Knp\Component\Pager\PaginatorInterface $paginator
    ): JsonResponse {

        //Lee el token
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7); // elimina los primeros 7 caracteres
        }
        if (!$token || !$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        //Identifica al usuario
        $identity = $jwtAuth->checkToken($token, true);

        // Según el rol, construimos la query
        if ($identity->role === 'admin') {
            // Admin ve todas las tareas
            $dql = 'SELECT t FROM App\Entity\Task t ORDER BY t.id DESC';
            $query = $em->createQuery($dql);
        } else {
            // Usuario normal solo ve las tareas asignadas a él
            $dql = 'SELECT t FROM App\Entity\Task t WHERE t.assignedTo = :user ORDER BY t.id DESC';
            $query = $em->createQuery($dql)
                ->setParameter('user', $identity->sub);
        }


        //Se hace la paginacion
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 10;
        $pagination = $paginator->paginate($query, $page, $itemsPerPage);

        //Convierte objetos a arrays
        $tasksArray = [];
        foreach ($pagination->getItems() as $task) {
            $tasksArray[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'created_at' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $task->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $task->getUser()->getId(),
                    'email' => $task->getUser()->getEmail(),
                ],
                'assigned_to' => [
                    'id' => $task->getAssignedTo()?->getId(),
                    'email' => $task->getAssignedTo()?->getEmail(),
                ]
            ];
        }

        //Respuesta final
        return $this->json([
            'status' => 'success',
            'code' => 200,
            'total_items_count' => $pagination->getTotalItemCount(),
            'page_actual' => $page,
            'items_per_page' => $itemsPerPage,
            'total_pages' => ceil($pagination->getTotalItemCount() / $itemsPerPage),
            'data' => $tasksArray,
        ]);
    }


    #[Route('/task/detail/{id}', name: 'app_task_detail', methods: ['POST'])]
    public function detailTask(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        JwtAuth                $jwtAuth
    ): JsonResponse
    {

        //Obtiene token
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7); // elimina los primeros 7 caracteres
        }

        //Valida token
        if (!$token || !$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        //Identidad del usuario
        $identity = $jwtAuth->checkToken($token, true);

        //Busca la task
        $task = $em->getRepository(Task::class)->find($id);

        //Comprueba existencia y permisos
        if (
            !$task ||
            (
                $identity->role !== 'admin' &&
                $task->getAssignedTo()->getId() !== $identity->sub
            )
        ) {
            return $this->json([
                'status' => 'error',
                'code' => 404,
                'msg' => 'Task not found or no permission'
            ], 404);
        }


        //Devuelve los datos de las task
        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'created_at' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $task->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $task->getUser()->getId(),
                    'email' => $task->getUser()->getEmail(),
                ],
                'assigned_to' => [
                    'id' => $task->getAssignedTo()?->getId(),
                    'email' => $task->getAssignedTo()?->getEmail(),
                    'name' => $task->getAssignedTo()->getName(),
                    'surname' => $task->getAssignedTo()->getSurname(),
                ]
            ]
        ]);
    }

    #[Route('/task/search/{search}', name: 'app_task_search', methods: ['POST'])]
    public function searchTask(
        Request                $request,
        EntityManagerInterface $em,
        JwtAuth                $jwtAuth,
        ?string                $search = null
    ): JsonResponse
    {
        //Obtener token del header
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7); // elimina los primeros 7 caracteres
        }

        if (!$token || !$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        //Identidad del usuario
        $identity = $jwtAuth->checkToken($token, true);

        // Leer JSON raw del body
        $body = json_decode($request->getContent(), true);
        //Enviar en body
        $filter = $body['filter'] ?? null;
        $assignedToId = $body['assigned_to_id'] ?? null;


        // Mapeo:
            // 1 -> new
            // 2 -> todas
            // 3 -> finished
            // 4 -> in_progress

        if ($filter === null || $filter == 2) {
            $filter = null; // todas
        } elseif ($filter == 1) {
            $filter = 'new';
        } elseif ($filter == 3) {
            $filter = 'finished';
        } elseif ($filter == 4) {
            $filter = 'in_progress';
        } else {
            $filter = null;
        }

        //Orden
        $orderValue = $body['order'] ?? 1;

        if ($orderValue == 1) {
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }

        //Construccion del DQL
        // Construcción base del DQL según el rol
        if ($identity->role === 'admin') {
            $dql = 'SELECT t FROM App\Entity\Task t WHERE 1=1';

            if ($assignedToId) {
                $dql .= ' AND t.assignedTo = :assignedTo';
            }

        } else {
            $dql = 'SELECT t FROM App\Entity\Task t WHERE t.assignedTo = :user';
        }


        if (!empty($search) && $search !== 'all') {
            $dql .= ' AND (t.title LIKE :search OR t.description LIKE :search)';
        }
        if ($filter !== null) {
            $dql .= ' AND t.status = :filter';
        }
        $dql .= " ORDER BY t.id $order";

        //Crear query
        $user = $em->getRepository(User::class)->find($identity->sub);
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'code' => 404,
                'msg' => 'User not found'
            ], 404);
        }

        $query = $em->createQuery($dql);

        if ($identity->role !== 'admin') {
            $user = $em->getRepository(User::class)->find($identity->sub);
            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'code' => 404,
                    'msg' => 'User not found'
                ], 404);
            }

            $query->setParameter('user', $user);
        }
        if ($identity->role === 'admin' && $assignedToId) {
            $assignedUser = $em->getRepository(User::class)->find($assignedToId);
            if ($assignedUser) {
                $query->setParameter('assignedTo', $assignedUser);
            }
        }
        if (!empty($search) && $search !== 'all') {
            $query->setParameter('search', '%' . $search . '%');
        }
        if ($filter !== null) {
            $query->setParameter('filter', $filter);
        }

        //Ejecutar
        $tasks = $query->getResult();

        //Formatear datos
        $data = [];
        foreach ($tasks as $task) {
            $data[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'created_at' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $task->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail()
                ],
                'assigned_to' => [
                    'id' => $task->getAssignedTo()?->getId(),
                    'email' => $task->getAssignedTo()?->getEmail(),
                    'name' => $task->getAssignedTo()->getName(),
                    'surname' => $task->getAssignedTo()->getSurname(),
                ]
            ];
        }

        //Respuesta
        return $this->json([
            'status' => 'success',
            'code' => 200,
            'data' => $data
        ]);
    }

    #[Route('/task/remove/{id}', name: 'app_task_remove', methods: ['POST'])]
    public function removeTask(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        JwtAuth                $jwtAuth
    ): JsonResponse
    {
        // Obtener token
        $token = $request->headers->get('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // Validar token
        if (!$token || !$jwtAuth->checkToken($token)) {
            return $this->json([
                'status' => 'error',
                'code' => 401,
                'msg' => 'Authorization not valid'
            ], 401);
        }

        // Identidad del usuario
        $identity = $jwtAuth->checkToken($token, true);

        // Comprobar que es admin
        $userRole = $identity->role ?? 'user';
        if ($userRole !== 'admin') {
            return $this->json([
                'status' => 'error',
                'code' => 403,
                'msg' => 'No tienes permisos para eliminar tareas'
            ], 403);
        }

        // Buscar la tarea (sin comprobar propietario)
        $task = $em->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'code' => 404,
                'msg' => 'Task not found'
            ], 404);
        }

        // Borrar la tarea
        $em->remove($task);
        $em->flush();

        // Respuesta
        return $this->json([
            'status' => 'success',
            'code' => 200,
            'msg' => 'Task deleted successfully',
            'data' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
            ]
        ]);
    }

}
