<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Email</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size:16px">

    <p>Hello, {{ $formData['user']->name }}</p>

    <h1>You have received a change password</h1>

    <p>Click here to reset password</p>

    <a href="{{ route('front.resetPassword', $formData['token']) }}"> Cick here</a>

    <p>Thanks</p>

</body>
</html>
