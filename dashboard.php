<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Think Room | Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      background: #0d0d0d url('images/jessica-christian-wuqYCTAYPH4-unsplash.jpg') no-repeat center center/cover;
      background-size: cover;
      font-family: 'Merriweather', serif;
      color: #f8f1e7;
      height: 100vh;
      overflow: hidden;
      animation: zoomBackground 30s ease-in-out infinite alternate;
    }
    @keyframes zoomBackground {
      from { background-size: 100%; }
      to { background-size: 110%; }
    }
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
z-index: -1;
    }
    .dashboard {
      display: flex;
      height: 100vh;
      position: relative;
      z-index: 1;
    }
    .sidebar {
      width: 250px;
      background: rgba(0, 0, 0, 0.9);
      padding: 20px 10px;
      display: flex;
      flex-direction: column;
      align-items: start;
      border-right: 1px solid rgba(255,255,255,0.1);
    }
    .sidebar a {
      color: #f8f1e7;
      text-decoration: none;
      font-size: 1.1rem;
      margin: 15px 0;
      width: 100%;
      display: flex;
      align-items: center;
    }
    .sidebar a:hover {
      color: #ccc;
    }
    .sidebar i {
      margin-right: 10px;
      font-size: 1.2rem;
    }
    .main-content {
      flex-grow: 1;
      padding: 40px;
      overflow-y: auto;
    }
    textarea, input {
      background: rgba(255,255,255,0.05);
      border: none;
      color: #f8f1e7;
      width: 100%;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 15px;
    }
    footer {
      text-align: center;
      padding: 10px;
      font-size: 0.8rem;
      color: #aaa;
      background: rgba(0, 0, 0, 0.8);
    }
    .hidden {
      display: none;
    }
    @media (max-width: 768px) {
      .dashboard {
        flex-direction: column;
      }
      .sidebar {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        padding: 10px;
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.1);
      }
      .sidebar a {
        margin: 10px;
        font-size: 1rem;
        justify-content: center;
      }
      .main-content {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

<div class="overlay"></div>
<!-- Mobile Navbar -->
<nav class="navbar navbar-dark bg-dark d-md-none px-3">
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
    <span class="navbar-toggler-icon"></span>
  </button>
  <span class="navbar-brand ms-2">Think Room</span>
</nav>

<!-- Mobile Collapsible Sidebar -->
<div class="collapse d-md-none bg-dark" id="mobileSidebar">
  <div class="d-flex flex-column text-center py-2">
    <a href="#home" class="nav-link text-light py-2" onclick="event.preventDefault(); showSection('home');" data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
      <i class="bi bi-house-door"></i> Home
    </a>
    <a href="#compose" class="nav-link text-light py-2" onclick="event.preventDefault(); showSection('compose');" data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
      <i class="bi bi-pencil-square"></i> Compose
    </a>
    <a href="#archive" class="nav-link text-light py-2" onclick="event.preventDefault(); showSection('archive');" data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
      <i class="bi bi-collection"></i> Archive
    </a>
    <a href="#stats" class="nav-link text-light py-2" onclick="event.preventDefault(); showSection('stats');" data-bs-toggle="collapse" data-bs-target="#mobileSidebar">
      <i class="bi bi-bar-chart"></i> Stats
    </a>
    <a href="logout.php" class="nav-link text-light py-2">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</div>



<div class="dashboard">
 <div class="sidebar d-none d-md-flex">
    <a href="#" onclick="showSection('home')"><i class="bi bi-house-door"></i> Home</a>
    <a href="#" onclick="showSection('compose')"><i class="bi bi-pencil-square"></i> Compose</a>
    <a href="#" onclick="showSection('archive')"><i class="bi bi-collection"></i> Archive</a>
    <a href="#" onclick="showSection('stats')"><i class="bi bi-bar-chart"></i> Stats</a>
    <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>

  <div class="main-content container">
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
 <div class="alert alert-success fade show" role="alert">
      Work saved successfully!
    </div>
    <?php endif; ?>

<div id="home" class="text-light">
  <h1 class="fw-bold">Welcome, Freddy Carias</h1>
  <p class="lead">God is good...</p>

  <!-- Stats Cards -->
  <div class="row mt-5">
    <div class="col-md-6 col-lg-4 mb-3">
      <div class="card bg-dark border-secondary shadow-sm">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Poems</h6>
<h4 id="poemCount" class="card-title fw-bold text-light">...</h4>

        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
      <div class="card bg-dark border-secondary shadow-sm">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Top Category</h6>
          <h4 id="topCategory" class="card-title fw-bold text-light">...</h4>

        </div>
      </div>
    </div>
  </div>

  <!-- Recent Poems List -->
  <div class="mt-5">
    <h5 class="fw-semibold mb-3">Recent Poems</h5>
    <ul id="recentPoems" class="list-group list-group-flush bg-transparent">
      <!-- JS fills this -->
    </ul>
  </div>
</div>





    <div id="compose" class="hidden">
      <h1>Compose a New Work</h1>
<form id="composeForm">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="category" placeholder="Category (e.g., Love, Reflection)" required>
        <textarea name="body" rows="10" placeholder="Write your poem here..." required></textarea>
        <button type="submit" class="btn btn-outline-light">Save Work</button>
      </form>
    </div>

    <div id="archive" class="hidden">
      <h1>Your Archives</h1>
      <div id="poemsList" class="row row-cols-1 row-cols-md-3 g-4 mt-4"></div>
    </div>

    <div id="stats" class="hidden container-fluid px-0">
      <div class="w-100">
<iframe 
  src="https://lookerstudio.google.com/embed/reporting/175f58db-eded-456f-9cf4-eb768ab086a7/page/nZkIF"
  class="w-100 border-0"
  style="height: 85vh; min-height: 60vh;"
  allowfullscreen
  sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox">
</iframe>

      </div>
    </div>
  </div>
</div>

<!-- Poem Modal -->
<div class="modal fade" id="poemModal" tabindex="-1" aria-labelledby="poemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header">
        <h5 class="modal-title" id="poemModalLabel">Poem Title</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="poemModalBody"></div>
      <div class="modal-footer">
        <a href="#" id="editLink" class="btn btn-outline-warning btn-sm">Edit</a>
        <button type="button" id="deleteButton" class="btn btn-outline-danger btn-sm">Delete</button>
      </div>
    </div>
  </div>
</div>

<footer>
  &copy; 2025 Freddy Carias. All Rights Reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

// ---------------------------------------------
// SECTION NAVIGATION & STATE MEMORY
// ---------------------------------------------
// Handles visibility of dashboard sections (home, compose, archive, stats)
// and remembers the last active section using localStorage.
// This ensures the user returns to the same section after a page reload.
// ---------------------------------------------
function showSection(sectionId) {
  localStorage.setItem('activeSection', sectionId); // store it
  const sections = ['home', 'compose', 'archive', 'stats'];
  sections.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  });

  const target = document.getElementById(sectionId);
  if (target) target.classList.remove('hidden');

  if (sectionId === 'archive') {
    loadPoems();
  } else if (sectionId === 'home') {
    loadHomeStats();
  }
}

// ---------------------------------------------
// INITIAL SECTION LOAD ON PAGE READY
// ---------------------------------------------
// On DOM ready:
// - Detects if the app is running as a PWA
// - Clears localStorage to always start fresh
// - Loads 'home' section
// - Handles dismissing any success alert
// ---------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  const isPWA = window.matchMedia('(display-mode: standalone)').matches;
  if (isPWA) {
    localStorage.removeItem('activeSection');
  }

  const saved = localStorage.getItem('activeSection');
  if (saved && ['home', 'compose', 'archive', 'stats'].includes(saved)) {
    showSection(saved);
  } else {
    showSection('home');
  }

  const alert = document.querySelector('.alert-success');
  if (alert) {
    setTimeout(() => {
      alert.classList.remove('show');
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  }
});



function loadPoems() {
  fetch('get_poems.php')
    .then(response => response.json())
    .then(data => {
      const poemsList = document.getElementById('poemsList');
      poemsList.innerHTML = '';

      if (data.length === 0) {
        poemsList.innerHTML = '<p>No works saved yet.</p>';
        return;
      }

      data.forEach(poem => {
        const poemCard = `
          <div class="col">
            <div class="card bg-dark text-light border-secondary h-100">
              <div class="card-body p-3">
                <h6 class="card-title mb-1">${poem.title}</h6>
                <p class="text-muted small mb-2">${poem.category}</p>
                <div class="d-flex flex-column gap-1 mt-2">
                  <button class="btn btn-sm btn-outline-light text-start" onclick="openPoemModal(${poem.id})">
                    <i class="bi bi-eye me-1"></i> View Poem
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
        poemsList.innerHTML += poemCard;
      });
    })
    .catch(error => {
      console.error('Error loading poems:', error);
    });
}

function openPoemModal(poemId) {
  fetch('get_poem_by_id.php?id=' + poemId)
    .then(response => response.json())
    .then(poem => {
      document.getElementById('poemModalLabel').innerText = poem.title;
      document.getElementById('poemModalBody').innerHTML = poem.body.replace(/\n/g, '<br>');
      document.getElementById('editLink').href = `edit_poem.php?id=${poemId}`;
      document.getElementById('deleteButton').onclick = () => deletePoem(poemId);

      const modal = new bootstrap.Modal(document.getElementById('poemModal'));
      modal.show();
    })
    .catch(error => {
      console.error('Error loading poem:', error);
    });
}

function deletePoem(id) {
  if (!confirm('Are you sure you want to delete this poem?')) return;

  fetch('delete_poem.php?id=' + id, { method: 'GET' })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        loadPoems();
        const modal = bootstrap.Modal.getInstance(document.getElementById('poemModal'));
        modal.hide();
      } else {
        alert('Failed to delete poem.');
      }
    })
    .catch(error => {
      console.error('Error deleting poem:', error);
    });
}


function loadHomeStats() {
  fetch('get_poems.php')
    .then(res => res.json())
    .then(poems => {
      // Total poems
      document.getElementById('poemCount').textContent = poems.length;

      // Count categories
      const categoryCounts = {};
      poems.forEach(p => {
        const cat = p.category || 'Uncategorized';
        categoryCounts[cat] = (categoryCounts[cat] || 0) + 1;
      });

      // Find most frequent category
      let topCategory = 'N/A';
      let max = 0;
      for (const [category, count] of Object.entries(categoryCounts)) {
        if (count > max) {
          max = count;
          topCategory = category;
        }
      }
      document.getElementById('topCategory').textContent = topCategory;

      // Recent Poems
   const recentList = document.getElementById('recentPoems');
recentList.innerHTML = '';
poems.slice(0, 3).forEach(poem => {
  const li = document.createElement('li');
  li.className = 'list-group-item bg-transparent text-light border-secondary';
  li.textContent = poem.title;
  recentList.appendChild(li);
});
    })
    .catch(err => {
      console.error('Error loading home stats:', err);
      document.getElementById('poemCount').textContent = '—';
      document.getElementById('topCategory').textContent = '—';
    });
}

// Handle poem submission via AJAX:
// - Prevents page reload
// - Sends form data to save_work.php
// - On success, shows a toast, clears the form, and optionally refreshes poem list
document.getElementById('composeForm').addEventListener('submit', function (e) {
  e.preventDefault(); // prevent default form submission

  const form = this;
  const formData = new FormData(form);

  fetch('save_work.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const toast = new bootstrap.Toast(document.getElementById('saveToast'));
        toast.show();
        form.reset(); // clear the form
           loadPoems();             // Optionally still refresh archive in the background
        // showSection('archive'); // <— Comment or remove this line to stay on compose
      } else {
        alert('Failed to save poem.');
      }
    })
    .catch(err => {
      console.error('Save error:', err);
      alert('Something went wrong.');
    });
});





</script>
<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        Poem saved successfully!
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>



</body>
</html>
