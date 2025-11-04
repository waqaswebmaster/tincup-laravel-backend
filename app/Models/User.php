<?php

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $sql = "INSERT INTO users (
            email, password, firstName, lastName, phone, 
            emailVerified, phoneVerified, profilePicture, bio, 
            location, city, state, zipCode, selectedCauses, 
            followedOrganizations, pushNotifications, emailNotifications,
            organizationUpdates, causeAlerts, onboardingCompleted, 
            createdAt, updatedAt
        ) VALUES (
            :email, :password, :firstName, :lastName, :phone,
            :emailVerified, :phoneVerified, :profilePicture, :bio,
            :location, :city, :state, :zipCode, :selectedCauses,
            :followedOrganizations, :pushNotifications, :emailNotifications,
            :organizationUpdates, :causeAlerts, :onboardingCompleted,
            NOW(), NOW()
        )";

        $this->db->execute($sql, [
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':firstName' => $data['firstName'] ?? null,
            ':lastName' => $data['lastName'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':emailVerified' => $data['emailVerified'] ?? false,
            ':phoneVerified' => $data['phoneVerified'] ?? false,
            ':profilePicture' => $data['profilePicture'] ?? null,
            ':bio' => $data['bio'] ?? null,
            ':location' => $data['location'] ?? null,
            ':city' => $data['city'] ?? null,
            ':state' => $data['state'] ?? null,
            ':zipCode' => $data['zipCode'] ?? null,
            ':selectedCauses' => isset($data['selectedCauses']) ? json_encode($data['selectedCauses']) : null,
            ':followedOrganizations' => isset($data['followedOrganizations']) ? json_encode($data['followedOrganizations']) : null,
            ':pushNotifications' => $data['pushNotifications'] ?? true,
            ':emailNotifications' => $data['emailNotifications'] ?? true,
            ':organizationUpdates' => $data['organizationUpdates'] ?? true,
            ':causeAlerts' => $data['causeAlerts'] ?? true,
            ':onboardingCompleted' => $data['onboardingCompleted'] ?? false,
        ]);

        return $this->db->lastInsertId();
    }

    public function findByEmail($email) {
        return $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function findById($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function updateRefreshToken($userId, $token) {
        return $this->db->execute(
            "UPDATE users SET refreshToken = ?, updatedAt = NOW() WHERE id = ?",
            [$token, $userId]
        );
    }

    public function updateProfile($userId, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'email' && $key !== 'password') {
                $fields[] = "$key = ?";
                $params[] = is_array($value) ? json_encode($value) : $value;
            }
        }
        
        $fields[] = "updatedAt = NOW()";
        $params[] = $userId;
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $params);
    }

    public function updateLastLogin($userId) {
        return $this->db->execute(
            "UPDATE users SET lastLoginAt = NOW(), updatedAt = NOW() WHERE id = ?",
            [$userId]
        );
    }
}
