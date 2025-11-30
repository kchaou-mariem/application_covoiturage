<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>
<header class="site-header">
  <div class="navbar">
    <div class="brand">
      <i class="fas fa-car-side"></i>
      <span>VroomVroom</span>
    </div>

    <nav class="nav-links" aria-label="Main navigation">
      <a href="trajets.php">Journeys</a>
      <a href="searchJourney.php" class="nav-search"><i class="fas fa-search me-1"></i>Search</a>
      <?php if (!empty($_SESSION['user_cin'])): ?>
        <a href="createJourney.php">Create</a>
        <a href="my_trajets.php">My Journeys</a>
        <a href="list_booking.php">My Bookings</a>
        <form method="post" action="logout.php" style="display:inline;margin:0">
          <button type="submit" class="btn" style="border:none;background:transparent;padding:0 8px;color:#0b5ed7;cursor:pointer">Logout</button>
        </form>
        <span class="user-badge">Hello <?= htmlspecialchars($_SESSION['user_firstName'] ?? 'User') ?></span>
      <?php else: ?>
        <a href="authentification.html">Login</a>
        <a href="inscription.html" class="btn">Sign Up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
