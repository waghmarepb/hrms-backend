<?php

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user and receive access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@hrms.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="admin@hrms.com"),
     *                 @OA\Property(property="is_admin", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials or inactive account",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */
    public function login()
    {
        $data = Request::all();
        
        // Validation
        Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required'
        ])->validate();
        
        $email = $data['email'];
        $password = $data['password'];
        
        // Find user
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            Response::error('The provided credentials are incorrect.', 401);
        }
        
        // Check if account is active
        if ($user['status'] != 1) {
            Response::error('Account is inactive.', 401);
        }
        
        // Verify password
        if (!$this->userModel->checkPassword($user, $password)) {
            Response::error('The provided credentials are incorrect.', 401);
        }
        
        // Update login info
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->userModel->updateLoginInfo($user['id'], $ipAddress);
        
        // Create token
        $token = Auth::createToken($user['id']);
        
        Response::json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $this->userModel->getFullName($user),
                'email' => $user['email'],
                'is_admin' => $user['is_admin'],
                'image' => $user['image'] ?? null,
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="User logout",
     *     description="Logout user and revoke access token",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        $user = Auth::user();
        
        if ($user) {
            // Update logout info
            $this->userModel->updateLogoutInfo($user['id']);
            
            // Revoke token
            $token = Request::bearerToken();
            Auth::revokeToken($token);
        }
        
        Response::json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     summary="Get current user",
     *     description="Get authenticated user information",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="fullname", type="string", example="John Doe"),
     *                 @OA\Property(property="firstname", type="string", example="John"),
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="admin@hrms.com"),
     *                 @OA\Property(property="is_admin", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", nullable=true),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="last_login", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function me()
    {
        $user = Auth::user();
        
        if (!$user) {
            Response::unauthorized();
        }
        
        Response::json([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'fullname' => $this->userModel->getFullName($user),
                'firstname' => $user['firstname'] ?? null,
                'lastname' => $user['lastname'] ?? null,
                'email' => $user['email'],
                'is_admin' => $user['is_admin'],
                'image' => $user['image'] ?? null,
                'status' => $user['status'],
                'last_login' => $user['last_login'] ?? null,
            ]
        ], 200);
    }
}

