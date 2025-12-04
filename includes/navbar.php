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
        <a href="cart.php" class="cart-link">
          <i class="fas fa-shopping-cart me-2"></i>Cart
          <?php 
          $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
          if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown">
          <button class="user-dropdown-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle"></i> Hello <?= htmlspecialchars($_SESSION['user_firstName'] ?? 'User') ?>
            <i class="fas fa-chevron-down ms-1"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li>
              <a class="dropdown-item" href="profile.php">
                <i class="fas fa-user-cog"></i> Personal Information
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="logout.php" style="margin:0">
                <button type="submit" class="dropdown-item text-danger">
                  <i class="fas fa-sign-out-alt"></i> Logout
                </button>
              </form>
            </li>
          </ul>
        </div>
      <?php else: ?>
        <a href="authentification.html">Login</a>
        <a href="inscription.html" class="btn">Sign Up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
