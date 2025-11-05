<?php

class AuthController {
    private $userModel;
    private $jwtSecret;

    public function __construct() {
        $this->userModel = new User();
        $config = require __DIR__ . '/../../config/app.php';
        $this->jwtSecret = $config['jwt_secret'];
    }

    public function register($request) {
        // Validate input
        if (empty($request['email']) || empty($request['password'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
        }

        if (!filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
        }

        // Check if user exists
        if ($this->userModel->findByEmail($request['email'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'User already exists'], 400);
        }

        // Hash password
        $hashedPassword = password_hash($request['password'], PASSWORD_BCRYPT);

        // Create user
        $userId = $this->userModel->create([
            'email' => $request['email'],
            'password' => $hashedPassword,
            'firstName' => $request['firstName'] ?? null,
            'lastName' => $request['lastName'] ?? null,
            'phone' => $request['phone'] ?? null,
        ]);

        $user = $this->userModel->findById($userId);

        // Generate tokens
        $accessToken = $this->generateToken($user, 3600); // 1 hour
        $refreshToken = $this->generateToken($user, 604800); // 7 days

        // Save refresh token
        $this->userModel->updateRefreshToken($userId, $refreshToken);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $this->sanitizeUser($user),
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
            ]
        ], 201);
    }

    public function login($request) {
        if (empty($request['email']) || empty($request['password'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
        }

        $user = $this->userModel->findByEmail($request['email']);

        if (!$user || !password_verify($request['password'], $user['password'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Generate tokens
        $accessToken = $this->generateToken($user, 3600);
        $refreshToken = $this->generateToken($user, 604800);

        $this->userModel->updateRefreshToken($user['id'], $refreshToken);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $this->sanitizeUser($user),
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
            ]
        ]);
    }

    public function getProfile($userId) {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->sanitizeUser($user)
        ]);
    }

    public function updateProfile($userId, $request) {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $this->userModel->updateProfile($userId, $request);
        $updatedUser = $this->userModel->findById($userId);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $this->sanitizeUser($updatedUser)
        ]);
    }

    public function refreshToken($request) {
        if (empty($request['refreshToken'])) {
            return $this->jsonResponse(['success' => false, 'message' => 'Refresh token required'], 400);
        }

        try {
            $decoded = $this->verifyToken($request['refreshToken']);
            $user = $this->userModel->findById($decoded->userId);

            if (!$user || $user['refreshToken'] !== $request['refreshToken']) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid refresh token'], 401);
            }

            $accessToken = $this->generateToken($user, 3600);

            return $this->jsonResponse([
                'success' => true,
                'data' => ['accessToken' => $accessToken]
            ]);
        } catch (Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid token'], 401);
        }
    }

    public function logout($userId) {
        $this->userModel->updateRefreshToken($userId, null);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function completeOnboarding($userId, $request) {
        $user = $this->userModel->findById($userId);

        if (!$user) {
            return $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        // Update user profile with onboarding data
        $updateData = [
            'onboardingCompleted' => 1
        ];

        if (isset($request['firstName'])) {
            $updateData['firstName'] = $request['firstName'];
        }

        if (isset($request['selectedCauses'])) {
            $updateData['selectedCauses'] = json_encode($request['selectedCauses']);
        }

        if (isset($request['followedOrganizations'])) {
            $updateData['followedOrganizations'] = json_encode($request['followedOrganizations']);
        }

        if (isset($request['notificationPreferences'])) {
            $prefs = $request['notificationPreferences'];
            if (isset($prefs['pushNotifications'])) {
                $updateData['pushNotifications'] = $prefs['pushNotifications'] ? 1 : 0;
            }
            if (isset($prefs['emailNotifications'])) {
                $updateData['emailNotifications'] = $prefs['emailNotifications'] ? 1 : 0;
            }
            if (isset($prefs['organizationUpdates'])) {
                $updateData['organizationUpdates'] = $prefs['organizationUpdates'] ? 1 : 0;
            }
            if (isset($prefs['causeAlerts'])) {
                $updateData['causeAlerts'] = $prefs['causeAlerts'] ? 1 : 0;
            }
        }

        $this->userModel->updateProfile($userId, $updateData);
        $updatedUser = $this->userModel->findById($userId);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Onboarding completed successfully',
            'data' => $this->sanitizeUser($updatedUser)
        ]);
    }

    private function generateToken($user, $expiresIn) {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'userId' => $user['id'],
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + $expiresIn
        ]));

        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true));

        return "$header.$payload.$signature";
    }

    public function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;
        $validSignature = base64_encode(hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true));

        if ($signature !== $validSignature) {
            throw new Exception('Invalid signature');
        }

        $decoded = json_decode(base64_decode($payload));

        if ($decoded->exp < time()) {
            throw new Exception('Token expired');
        }

        return $decoded;
    }

    private function sanitizeUser($user) {
        unset($user['password'], $user['refreshToken'], $user['passwordResetToken'], $user['passwordResetExpires']);
        
        // Parse JSON fields
        if (isset($user['selectedCauses'])) {
            $user['selectedCauses'] = json_decode($user['selectedCauses']);
        }
        if (isset($user['followedOrganizations'])) {
            $user['followedOrganizations'] = json_decode($user['followedOrganizations']);
        }

        return $user;
    }

    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
