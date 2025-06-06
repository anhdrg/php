<?php
// /models/UserModel.php

class UserModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Tạo người dùng mới.
     * @param array $data Dữ liệu người dùng (username, email, password, full_name, role (optional)).
     * @return string|false Trả về ID người dùng mới nếu thành công, false nếu thất bại.
     * @throws Exception Nếu username hoặc email đã tồn tại.
     */
    public function createUser($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $this->db->query('INSERT INTO users (username, email, password, full_name, role) VALUES (:username, :email, :password, :full_name, :role)');
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':full_name', $data['full_name'] ?? null);
        $this->db->bind(':role', $data['role'] ?? ROLE_USER);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                if (strpos($e->getMessage(), "'users.username'") !== false || strpos($e->getMessage(), "for key 'username'") !== false) {
                    throw new Exception("Tên đăng nhập đã tồn tại.");
                } elseif (strpos($e->getMessage(), "'users.email'") !== false || strpos($e->getMessage(), "for key 'email'") !== false) {
                    throw new Exception("Địa chỉ email đã tồn tại.");
                }
            }
            write_log("Lỗi tạo người dùng: " . $e->getMessage() . " | Data: " . json_encode($data), 'ERROR');
            throw $e; 
        }
    }

    public function findUserByUsername($username) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();
        return $row ? $row : false;
    }

    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        $row = $this->db->single();
        return $row ? $row : false;
    }

    public function findUserById($id) {
        $this->db->query('SELECT id, username, email, full_name, balance, role, created_at, updated_at FROM users WHERE id = :id');
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $row = $this->db->single();
        return $row ? $row : false;
    }

    /**
     * Kiểm tra thông tin đăng nhập.
     * @param string $usernameOrEmail Username hoặc Email.
     * @param string $password Mật khẩu chưa hash.
     * @return mixed Trả về mảng thông tin người dùng nếu đăng nhập thành công, false nếu không.
     */
    public function login($usernameOrEmail, $password) {
        // Sửa lỗi: Sử dụng hai tên placeholder khác nhau
        $this->db->query('SELECT * FROM users WHERE username = :uoe_username OR email = :uoe_email');
        $this->db->bind(':uoe_username', $usernameOrEmail); // Bind cho placeholder username
        $this->db->bind(':uoe_email', $usernameOrEmail);   // Bind cho placeholder email (cùng giá trị)
        
        $user = $this->db->single();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
        }
        return false;
    }

    public function updateUserProfile($userId, $data) {
        $setClauses = [];
        $params = [':id' => (int)$userId];
        if (array_key_exists('full_name', $data)) {
            $setClauses[] = 'full_name = :full_name';
            $params[':full_name'] = $data['full_name'];
        }
        if (isset($data['email']) && !empty($data['email'])) {
            $setClauses[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (empty($setClauses)) { return true; }
        $sql = 'UPDATE users SET ' . implode(', ', $setClauses) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && (strpos($e->getMessage(), "'users.email'") !== false || strpos($e->getMessage(), "for key 'email'") !== false)) {
                throw new Exception("Địa chỉ email này đã được sử dụng bởi tài khoản khác.");
            }
            write_log("Lỗi cập nhật profile người dùng: " . $e->getMessage() . " | UserID: " . $userId, 'ERROR');
            return false;
        }
    }

    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query('UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':id', (int)$userId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi cập nhật mật khẩu: " . $e->getMessage() . " | UserID: " . $userId, 'ERROR');
            return false;
        }
    }

    public function verifyCurrentPassword($userId, $currentPassword) {
        $this->db->query('SELECT password FROM users WHERE id = :id');
        $this->db->bind(':id', (int)$userId, PDO::PARAM_INT);
        $user = $this->db->single();
        return ($user && password_verify($currentPassword, $user['password']));
    }

    public function updateUserBalance($userId, $amount, $operation = 'add') {
        $absAmount = abs(floatval($amount));
        if ($absAmount < 0.01 && $amount != 0) {
             write_log("Số tiền cập nhật số dư không hợp lệ: " . $amount . " | UserID: " . $userId, 'WARNING');
            return false;
        }
        if ($absAmount == 0 && $amount == 0) return true;
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT balance FROM users WHERE id = :id FOR UPDATE');
            $this->db->bind(':id', (int)$userId, PDO::PARAM_INT);
            $user = $this->db->single();
            if (!$user) { $this->db->rollBack(); write_log("Không tìm thấy UserID: " . $userId . " để cập nhật số dư", 'ERROR'); return false; }
            $currentBalance = floatval($user['balance']); $newBalance = $currentBalance;
            if ($operation === 'add') { $newBalance = $currentBalance + $absAmount;
            } elseif ($operation === 'subtract') {
                if ($currentBalance < $absAmount) { $this->db->rollBack(); write_log("Không đủ số dư UserID: {$userId}", "WARNING"); return false; }
                $newBalance = $currentBalance - $absAmount;
            } else { $this->db->rollBack(); write_log("Thao tác cập nhật số dư không hợp lệ: " . $operation, 'ERROR'); return false; }
            $this->db->query('UPDATE users SET balance = :balance, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $this->db->bind(':balance', $newBalance);
            $this->db->bind(':id', (int)$userId, PDO::PARAM_INT);
            if ($this->db->execute()) { $this->db->commit(); return true;
            } else { $this->db->rollBack(); write_log("Lỗi thực thi cập nhật số dư UserID: " . $userId, 'ERROR'); return false; }
        } catch (PDOException $e) {
            $this->db->rollBack();
            write_log("Lỗi PDO khi cập nhật số dư: " . $e->getMessage() . " | UserID: " . $userId, 'ERROR');
            return false;
        }
    }

    public function getAllUsers($limit = ADMIN_DEFAULT_ITEMS_PER_PAGE, $offset = 0) {
        $this->db->query('SELECT id, username, email, full_name, balance, role, created_at, updated_at FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getTotalUsersCount() {
        $this->db->query('SELECT COUNT(id) as total FROM users');
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    public function adminUpdateUser($userId, $data) {
        $setClauses = []; $params = [':id' => (int)$userId];
        if (isset($data['username'])) { $setClauses[] = 'username = :username'; $params[':username'] = $data['username']; }
        if (isset($data['email'])) { $setClauses[] = 'email = :email'; $params[':email'] = $data['email']; }
        if (array_key_exists('full_name', $data)) { $setClauses[] = 'full_name = :full_name'; $params[':full_name'] = $data['full_name']; }
        if (isset($data['balance']) && (is_numeric($data['balance']) || $data['balance'] === null) ) {
            $setClauses[] = 'balance = :balance'; $params[':balance'] = ($data['balance'] !== null) ? (float)$data['balance'] : null;
        }
        if (isset($data['role']) && in_array($data['role'], [ROLE_USER, ROLE_ADMIN])) { $setClauses[] = 'role = :role'; $params[':role'] = $data['role']; }
        if (isset($data['password']) && !empty($data['password'])) {
            $setClauses[] = 'password = :password'; $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (empty($setClauses)) { return true; }
        $sql = 'UPDATE users SET ' . implode(', ', $setClauses) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            if ($key === ':balance' && $value === null) { $this->db->bind($key, $value, PDO::PARAM_NULL); } 
            else { $this->db->bind($key, $value); }
        }
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                if (strpos($e->getMessage(), "'users.username'") !== false || strpos($e->getMessage(), "for key 'username'") !== false) { throw new Exception("Tên đăng nhập đã tồn tại."); }
                if (strpos($e->getMessage(), "'users.email'") !== false || strpos($e->getMessage(), "for key 'email'") !== false) { throw new Exception("Địa chỉ email đã tồn tại."); }
            }
            write_log("Lỗi admin cập nhật người dùng: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public function deleteUser($userId) {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', (int)$userId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi admin xóa người dùng: " . $e->getMessage() . " | UserID: " . $userId, 'ERROR');
            return false;
        }
    }
    
    public function getCountByRole($role) {
        $this->db->query("SELECT COUNT(id) as total FROM users WHERE role = :role");
        $this->db->bind(':role', $role);
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }
}
?>