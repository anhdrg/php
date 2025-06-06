<?php
// /models/Database.php

// Nạp file config nếu chưa có (để lấy DB credentials)
if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $conn;
    private $stmt;

    public function __construct() {
        $this->conn = null;
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Bật exception cho lỗi PDO
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Đặt kiểu fetch mặc định là mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES   => false,                // Tắt emulate prepares để sử dụng native prepared statements
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Ghi log lỗi chi tiết
            write_log("Lỗi kết nối CSDL: " . $e->getMessage(), 'ERROR');
            // Hiển thị thông báo lỗi thân thiện hơn cho người dùng (hoặc không hiển thị gì cả tùy theo DEBUG_MODE)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Lỗi kết nối CSDL: " . $e->getMessage());
            } else {
                die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
            }
        }
    }

    /**
     * Lấy đối tượng PDO connection.
     * @return PDO|null
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Chuẩn bị một câu lệnh SQL.
     * @param string $sql Câu lệnh SQL.
     */
    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
    }

    /**
     * Gán giá trị cho tham số trong câu lệnh đã chuẩn bị.
     * @param mixed $param Tham số (ví dụ: ':username').
     * @param mixed $value Giá trị của tham số.
     * @param int|null $type Kiểu dữ liệu của tham số (PDO::PARAM_*).
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Thực thi câu lệnh đã chuẩn bị.
     * @return bool True nếu thành công, False nếu thất bại.
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            write_log("Lỗi thực thi SQL: " . $e->getMessage() . " | SQL: " . $this->stmt->queryString, 'ERROR');
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                // Có thể throw lại exception để controller xử lý, hoặc hiển thị lỗi
                throw $e; // Hoặc return false; tùy theo cách bạn muốn xử lý lỗi ở tầng Model/Controller
            }
            return false;
        }
    }

    /**
     * Lấy tất cả các dòng kết quả dưới dạng mảng các đối tượng (hoặc mảng kết hợp tùy fetch mode).
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Lấy một dòng kết quả duy nhất.
     * @return mixed
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Lấy số lượng dòng bị ảnh hưởng bởi câu lệnh DELETE, INSERT, hoặc UPDATE cuối cùng.
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Lấy ID của dòng cuối cùng được chèn vào CSDL.
     * @return string|false ID của dòng cuối cùng hoặc false nếu thất bại.
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Bắt đầu một transaction.
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit một transaction.
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback một transaction.
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
}
?>
