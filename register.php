
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>register page</title>
    <!-- custom css file link -->
    <link rel="stylesheet" href="style.css">
    <!-- cdn js file link -->
     <link rel="stylesheet" href="">
</head>
<body>
    <section class="form-container">
        <form action="" method="post" enctype="multipart/form-data">
            <h3>create an account</h3>
            <input type="text" placeholder="enter your name" class="box" required name="name">
            <input type="email" placeholder="enter your email" class="box" required name="email">
            <input type="password" placeholder="enter your password" class="box" required name="password">
            <input type="password" placeholder="confirm your password" class="box" required name="cpassword">
            <input type="image" placeholder="image/jpeg,image/png,image/jpg" class="box" required name="image">
            <input type="submit" name="submit" class="btn" value="register now">
            <p>already have an account?<a href="login.php">login here</a>
            </p>
        </form>
    </section>
</body>
</html>
