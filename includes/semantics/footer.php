<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ChronoNav Footer</title>

  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">

  <!-- Font Awesome (for social icons) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-5a6b4ht5bJz8b6EzR/AvHmsZmF6y/5JSRt3TDhLroYVvL1oKkCGM58HjYpRBnmhxR1ZT9vC8rZ5YFC9lXlcbXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    /* ===== CHRONONAV FOOTER STYLES ===== */
    .chrononav-footer {
      font-family: Inter, "Noto Sans", sans-serif;
      background: linear-gradient(135deg, #2E78C6 0%, rgb(37, 191, 252) 100%);
      color: #fff;
      text-align: center;
      padding: 20px 40px;
      border-top: 1px solid rgba(255, 255, 255, 0.25);
    }

    .chrononav-footer .footer-top {
      margin-bottom: 10px;
    }

    .chrononav-footer .footer-logo {
      height: 28px;
      object-fit: contain;
    }

    .chrononav-footer .footer-links {
      font-size: 14px;
      margin-bottom: 8px;
    }

    .chrononav-footer .footer-links span {
      margin-right: 12px;
    }

    .chrononav-footer .footer-links a {
      margin: 0 8px;
      font-weight: 500;
      color: #fff;
      text-decoration: none;
      transition: opacity 0.2s ease, text-decoration 0.2s ease;
    }

    .chrononav-footer .footer-links a:hover {
      opacity: 0.85;
      text-decoration: underline;
    }

    .chrononav-footer .footer-bottom {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.9);
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .chrononav-footer .footer-bottom a {
      color: rgba(255, 255, 255, 0.95);
      text-decoration: none;
    }

    .chrononav-footer .footer-bottom a:hover {
      opacity: 0.85;
      text-decoration: underline;
    }

    /* ===== Social Links ===== */
    .chrononav-footer .footer-socials {
      margin-top: 10px;
      display: flex;
      justify-content: center;
      gap: 16px;
    }

    .chrononav-footer .footer-socials a {
      font-size: 20px;
      color: #fff;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .chrononav-footer .footer-socials a:hover {
      opacity: 0.85;
      transform: translateY(-2px);
    }

    /* ===== Responsive ===== */
    @media (max-width: 600px) {
      .chrononav-footer {
        padding: 20px;
      }
      .chrononav-footer .footer-links {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }
      .chrononav-footer .footer-bottom {
        flex-direction: column;
        gap: 4px;
      }
      .chrononav-footer .footer-socials {
        margin-top: 14px;
      }
    }
  </style>
</head>
<body>

  <!-- ===== CHRONONAV FOOTER ===== -->
  <footer class="chrononav-footer">
    <div class="footer-top">
      <img 
        src="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png" 
        alt="ChronoNav Logo" 
        class="footer-logo"
      />
    </div>

    <div class="footer-links">
      <span>CHRONONAV © 2025</span>
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>

    <div class="footer-bottom">
      <span class="contact">
        Contact: <a href="mailto:chrononav.support@domain.com">chrononav.support@domain.com</a>
      </span>
      <span class="version">Version 1.0.0</span>
    </div>

    <div class="footer-socials">
      <a href="https://facebook.com" target="_blank" aria-label="Facebook">
        <i class="fab fa-facebook-f"></i>
      </a>
      <a href="https://x.com" target="_blank" aria-label="Twitter / X">
        <i class="fa-brands fa-twitter"></i> <!-- fallback bird -->
      </a>
      <a href="https://linkedin.com" target="_blank" aria-label="LinkedIn">
        <i class="fab fa-linkedin-in"></i>
      </a>
      <a href="https://github.com" target="_blank" aria-label="GitHub">
        <i class="fab fa-github"></i>
      </a>
    </div>
  </footer>

</body>
</html>
