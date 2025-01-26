<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require '../db.php';

    // Data sanitization and validation
    $name = dataFilter($_POST['name']);
    $mobile = dataFilter($_POST['mobile']);
    $user = dataFilter($_POST['uname']);
    $email = dataFilter($_POST['email']);
    $pass = dataFilter(password_hash($_POST['pass'], PASSWORD_BCRYPT));
    $hash = dataFilter(md5(rand(0, 1000)));
    $category = dataFilter($_POST['category']);
    $addr = dataFilter($_POST['addr']);

    // Session setup
    $_SESSION['Email'] = $email;
    $_SESSION['Name'] = $name;
    $_SESSION['Password'] = $pass;
    $_SESSION['Username'] = $user;
    $_SESSION['Mobile'] = $mobile;
    $_SESSION['Category'] = $category;
    $_SESSION['Hash'] = $hash;
    $_SESSION['Addr'] = $addr;
    $_SESSION['Rating'] = 0;

    // Validate mobile number length
    if (strlen($mobile) != 10) {
        $_SESSION['message'] = "Invalid Mobile Number!";
        header("location: error.php");
        exit();
    }

    // Table selection based on category
    $table = $category == 1 ? "farmer" : "buyer";
    $prefix = $category == 1 ? "f" : "b";

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM $table WHERE {$prefix}email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "User with this email already exists!";
        header("location: error.php");
        exit();
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO $table ({$prefix}name, {$prefix}username, {$prefix}password, {$prefix}hash, {$prefix}mobile, {$prefix}email, {$prefix}address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $user, $pass, $hash, $mobile, $email, $addr);

    if ($stmt->execute()) {
        $_SESSION['Active'] = 0;
        $_SESSION['logged_in'] = true;
        $_SESSION['picStatus'] = 0;
        $_SESSION['picExt'] = "png";

        $stmt = $conn->prepare("SELECT * FROM $table WHERE {$prefix}username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $User = $result->fetch_assoc();

        $_SESSION['id'] = $User["{$prefix}id"];

        $_SESSION['picId'] = $_SESSION['picStatus'] == 0 ? 0 : $_SESSION['id'];
        $_SESSION['picName'] = $_SESSION['picStatus'] == 0 ? "profile0.png" : "profile{$_SESSION['picId']}.{$_SESSION['picExt']}";

        $_SESSION['message'] = "Confirmation link has been sent to $email. Please verify your account by clicking on the link in the email!";

        $to = $email;
        $subject = "Account Verification (AgroCulture)";
        $message_body = "
            Hello $user,

            Thank you for signing up!

            Please click this link to activate your account:
            http://localhost/AgroCulture/Login/verify.php?email=$email&hash=$hash
        ";

        // Uncomment to send the email
        // mail($to, $subject, $message_body);

        header("location: profile.php");
    } else {
        $_SESSION['message'] = "Registration failed!";
        header("location: error.php");
    }
}

// Data filter function
function dataFilter($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
