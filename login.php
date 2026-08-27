<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Think Room | Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  
    <!-- Manifest for PWA Install Support -->
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0d0d0d">
  <!-- Bootstrap CSS -->


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: #0d0d0d url('images/jessica-christian-wuqYCTAYPH4-unsplash.jpg') no-repeat center center/cover;
      font-family: 'Merriweather', serif;
      color: #f8f1e7;
      margin: 0;
      position: relative;
      overflow: hidden;
      animation: zoomBackground 30s ease-in-out infinite alternate;
    }
    .overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: 0;
    }
    .content {
      max-width: 400px;
      padding: 40px;
      background: rgba(0, 0, 0, 0.75);
      border-radius: 10px;
      position: relative;
      z-index: 1;
      animation: fadeInContent 2s ease-in;
    }
    .alert {
      display: none;
      margin-bottom: 20px;
    }
    .form-control {
      background-color: rgba(255, 255, 255, 0.1);
      border: none;
      color: #f8f1e7;
    }
    .form-control::placeholder {
      color: #ccc;
    }
    .btn-outline-light {
      font-size: 1rem;
      padding: 0.6rem 2.5rem;
      border-radius: 30px;
      margin-top: 1rem;
    }
    footer {
      position: absolute;
      bottom: 10px;
      width: 100%;
      text-align: center;
      font-size: 0.8rem;
      color: #aaa;
    }
    @keyframes fadeInContent { from { opacity: 0; } to { opacity: 1; } }
    @keyframes zoomBackground { from { background-size: 100%; } to { background-size: 110%; } }
  </style>
</head>
<body>

<div class="overlay"></div>

<div class="content">
  <div id="errorMessage" class="alert alert-danger" role="alert">
    Wrong username or password.
  </div>

  <h2>Login</h2>

  <form id="loginForm">
    <div class="mb-3">
      <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
    </div>
    <div class="mb-3">
      <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
    </div>
    <button type="submit" class="btn btn-outline-light w-100">Login</button>
  </form>
</div>

<footer>
  &copy; 2025 Freddy Carias. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Handle login AJAX
document.getElementById('loginForm').addEventListener('submit', function(e) {
  e.preventDefault(); // Stop default form submit

  const formData = new FormData(this);

  fetch('admin_login.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.text())
  .then(data => {
    if (data.trim() === 'success') {
      window.location.href = 'dashboard.php'; // ✅ correct destination!
    } else {
      document.getElementById('errorMessage').style.display = 'block'; // Show error
    }
  })
  .catch(error => console.error('Error:', error));
});
</script>

</body>
</html>
