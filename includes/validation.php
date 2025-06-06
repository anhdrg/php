<?php
// /includes/validation.php

/**
 * Kiểm tra xem một giá trị có rỗng không.
 * @param mixed $value
 * @return bool
 */
function isEmpty($value) {
    return !isset($value) || trim($value) === '';
}

/**
 * Kiểm tra độ dài của một chuỗi.
 * @param string $value
 * @param int $min
 * @param int $max
 * @return bool
 */
function validateLength($value, $min, $max) {
    $length = mb_strlen(trim($value), 'UTF-8');
    return $length >= $min && $length <= $max;
}

/**
 * Kiểm tra định dạng email.
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Kiểm tra xem mật khẩu có khớp với mật khẩu xác nhận không.
 * @param string $password
 * @param string $confirmPassword
 * @return bool
 */
function confirmPassword($password, $confirmPassword) {
    return trim($password) === trim($confirmPassword);
}

/**
 * Kiểm tra độ phức tạp của mật khẩu (ví dụ: ít nhất 1 chữ hoa, 1 chữ thường, 1 số, 1 ký tự đặc biệt).
 * Bạn có thể tùy chỉnh regex này.
 * @param string $password
 * @return bool
 */
function validatePasswordStrength($password) {
    // Ít nhất 8 ký tự, 1 chữ hoa, 1 chữ thường, 1 số
    $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\S]{8,}$/'; // \S cho phép ký tự đặc biệt
    return preg_match($regex, $password);
}


/**
 * Validate dữ liệu đăng ký người dùng.
 * @param array $data Dữ liệu từ form (ví dụ: $_POST).
 * @return array Mảng chứa các lỗi. Rỗng nếu không có lỗi.
 */
function validateRegistrationData($data, $userModel) {
    $errors = [];

    // Username
    if (isEmpty($data['username'])) {
        $errors['username'] = "Tên đăng nhập không được để trống.";
    } elseif (!validateLength($data['username'], 3, 50)) {
        $errors['username'] = "Tên đăng nhập phải từ 3 đến 50 ký tự.";
    } elseif ($userModel->findUserByUsername($data['username'])) {
        $errors['username'] = "Tên đăng nhập này đã tồn tại.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
        $errors['username'] = "Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới (_).";
    }


    // Email
    if (isEmpty($data['email'])) {
        $errors['email'] = "Email không được để trống.";
    } elseif (!validateEmail($data['email'])) {
        $errors['email'] = "Địa chỉ email không hợp lệ.";
    } elseif ($userModel->findUserByEmail($data['email'])) {
        $errors['email'] = "Địa chỉ email này đã được sử dụng.";
    }

    // Password
    if (isEmpty($data['password'])) {
        $errors['password'] = "Mật khẩu không được để trống.";
    } elseif (!validatePasswordStrength($data['password'])) {
        // Cung cấp thông báo rõ ràng hơn về yêu cầu mật khẩu
        $errors['password'] = "Mật khẩu phải dài ít nhất 8 ký tự, bao gồm ít nhất một chữ hoa, một chữ thường và một chữ số.";
    }

    // Confirm Password
    if (isEmpty($data['confirm_password'])) {
        $errors['confirm_password'] = "Xác nhận mật khẩu không được để trống.";
    } elseif (!confirmPassword($data['password'], $data['confirm_password'])) {
        $errors['confirm_password'] = "Mật khẩu xác nhận không khớp.";
    }

    // Full Name (tùy chọn)
    if (!isEmpty($data['full_name']) && !validateLength($data['full_name'], 2, 100)) {
        $errors['full_name'] = "Họ và tên phải từ 2 đến 100 ký tự.";
    }

    return $errors;
}

/**
 * Validate dữ liệu đăng nhập.
 * @param array $data
 * @return array
 */
function validateLoginData($data) {
    $errors = [];
    if (isEmpty($data['username_or_email'])) {
        $errors['username_or_email'] = "Tên đăng nhập hoặc email không được để trống.";
    }
    if (isEmpty($data['password'])) {
        $errors['password'] = "Mật khẩu không được để trống.";
    }
    return $errors;
}


// Thêm các hàm validation khác nếu cần (ví dụ: cho sản phẩm, thanh toán,...)

?>
