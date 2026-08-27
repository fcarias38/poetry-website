<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = "fcarias38@outlook.com"; // Replace with your email
    $subject = "New message from Think Room";
    $name = strip_tags($_POST["name"]);
    $email = strip_tags($_POST["email"]);
    $message = strip_tags($_POST["message"]);

    $body = "From: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        echo "<script>alert('Message sent!'); window.location.href='home.shtml';</script>";
    } else {
        echo "<script>alert('Message failed to send.'); window.history.back();</script>";
    }
}
?>
