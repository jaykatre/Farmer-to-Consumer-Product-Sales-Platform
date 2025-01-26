<?php
session_start();
require '../db.php';

// Data sanitization function
function dataFilter($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submission for upload or remove profile picture
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Profile picture upload
    if (isset($_POST['upload'])) {
        $pic = $_FILES['profilePic'];
        $picName = $pic['name'];
        $picTmpName = $pic['tmp_name'];
        $picSize = $pic['size'];
        $picError = $pic['error'];
        $picType = $pic['type'];

        // Extract the file extension and validate it
        $picExt = explode('.', $picName);
        $picActualExt = strtolower(end($picExt));
        $allowed = array('jpg', 'jpeg', 'png');

        if (in_array($picActualExt, $allowed)) {
            if ($picError === 0) {
                // Generate a unique name for the profile picture
                $picNameNew = "profile" . $_SESSION['id'] . "." . $picActualExt;
                $_SESSION['picName'] = $picNameNew;
                $_SESSION['picExt'] = $picActualExt;

                // Specify the upload destination
                $picDestination = "../images/profileImages/" . $picNameNew;

                // Move the uploaded file to the destination
                move_uploaded_file($picTmpName, $picDestination);
                $id = $_SESSION['id'];

                // Prepare SQL query to update the database
                $sql = "UPDATE members SET picStatus = 1, picExt = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'si', $picActualExt, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['message'] = "Profile picture updated successfully!";
                        header("Location: ../profileView.php");
                        exit();
                    } else {
                        $_SESSION['message'] = "There was an error in updating your profile picture! Please try again!";
                        header("Location: ../Login/error.php");
                        exit();
                    }
                } else {
                    $_SESSION['message'] = "Database query error!";
                    header("Location: ../Login/error.php");
                    exit();
                }
            } else {
                $_SESSION['message'] = "There was an error in uploading your image! Please try again!";
                header("Location: ../Login/error.php");
                exit();
            }
        } else {
            $_SESSION['message'] = "You cannot upload files with this extension!";
            header("Location: ../Login/error.php");
            exit();
        }
    }
    // Profile picture removal
    else if (isset($_POST['remove']) && $_SESSION['picId'] != 0) {
        $picToRemove = "../images/profileImages/" . $_SESSION['picName'];
        
        // Attempt to remove the profile picture from the server
        if (!unlink($picToRemove)) {
            $_SESSION['message'] = "There was an error in deleting the profile picture!";
            header("Location: ../Login/error.php");
            exit();
        } else {
            $_SESSION['message'] = "The profile picture was successfully deleted!";
            $id = $_SESSION['id'];

            // Update the database to remove the profile picture
            $sql = "UPDATE members SET picStatus = 0, picExt = 'png' WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['picId'] = 0;
                    $_SESSION['picExt'] = "png";
                    $_SESSION['picName'] = "profile0.png";
                    header("Location: ../profileView.php");
                    exit();
                } else {
                    $_SESSION['message'] = "Error updating database after deletion!";
                    header("Location: ../Login/error.php");
                    exit();
                }
            }
        }
    } else {
        // If no valid action was taken, redirect to the profile page
        header("Location: ../profileView.php");
        exit();
    }
}
?>
