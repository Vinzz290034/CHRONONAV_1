
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ChronoNav</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto', sans-serif; }
    body { margin: 0; line-height: 1.6; background: #ffffffff; color: #333; scroll-behavior: smooth; }
    header { background-color: #4b4d4eff; position: fixed; top: 0; width: 100%;  padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; z-index: 999; }
    .logo { font-size: 1.5rem; color: #fff; font-weight: bold; display: flex; align-items: center; }
    .logo i { font-size: 1.5rem; margin-right: 10px; }
    .logo img {vertical-align: middle; border-radius: 36px;}
    nav a { margin: 0 1rem; color: #fff; text-decoration: none; transition: color 0.3s; }
    nav a:hover { color: #ffcc00; }
    .hero { background: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644') no-repeat center/cover; height: 100vh; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; color: white; padding-top: 4rem; }
    .hero h1 { font-size: 3rem; margin-bottom: 1rem; text-shadow: 1px 1px 5px #000; }
    .hero p { font-size: 1.2rem; }
    .btns { margin-top: 2rem; }
    .btns a { background: #fff; color: #004080; padding: 0.75rem 1.5rem; border-radius: 25px; margin: 0 0.5rem; text-decoration: none; font-weight: bold; transition: background 0.3s; }
    .btns a:hover { background: #ffcc00; color: #000; }

    section { padding: 80px 20px; text-align: center; }
    section h2 { font-size: 2.5rem; margin-bottom: 2rem; color: #004080; }

    /* Services */
    .services { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; }
    .service-card { background: white; padding: 2rem; border-radius: 10px; width: 280px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
    .service-card:hover { transform: translateY(-10px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .service-card i { font-size: 2rem; color: #004080; margin-bottom: 1rem; }
    .service-card h3 { margin-bottom: 1rem; font-size: 1.2rem; }

    /* FAQs */
    .faq-item { max-width: 700px; margin: 1rem auto; text-align: left; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
    .faq-question { background: #004080; color: #fff; padding: 1rem; cursor: pointer; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.5s ease, padding 0.3s ease; padding: 0 1rem; background: #f9f9f9; }
    .faq-answer.open { padding: 1rem; max-height: 200px; }

    /* Contact */
    .contact { max-width: 600px; margin: 0 auto; text-align: left; background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    .contact input, .contact textarea { width: 100%; margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; }
    .contact button { padding: 0.75rem 2rem; background: #004080; color: white; border: none; border-radius: 5px; cursor: pointer; }

    footer { background: #003366; color: #fff; padding: 2rem; text-align: center; margin-top: 2rem; }
    .scroll-top { position: fixed; right: 20px; bottom: 20px; background: #004080; color: white; border: none; padding: 0.5rem 1rem; border-radius: 30px; cursor: pointer; display: none; }
    .scroll-top.show { display: block; }
  </style>
</head>
<body>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <header>
  <div class="logo">
    <img src="assets/img/chrononav_logo.jpg" alt="ChronoNav Logo" style="height: 40px; margin-right: 10px;">
    <span>ChronoNav</span>
  </div>
  <nav>
    <a href="#about">About</a>
    <a href="#services">Services</a>
    <a href="#faqs">FAQs</a>
    <a href="#contact">Contact</a>
  </nav>
</header>


  <section class="hero">
    <h1>Welcome to ChronoNav</h1>
    <p>Optimizing Campus Navigation Through Time-Integrated Mobile Scheduling</p>
    <div class="btns">
      <a href="auth/login.php">Login</a>
      <a href="auth/register.php">Register</a>
    </div>
  </section>

  <section id="about">
    <h2>About ChronoNav</h2>
    <p>ChronoNav is a campus navigation and scheduling system built for students. Find classes, manage your schedule, and get reminders with ease.</p>
  </section>

  <section id="services">
    <h2>Our Features</h2>
    <div class="services">
      <div class="service-card">
        <i class="fas fa-calendar-check"></i>
        <h3>Class Schedule Management</h3>
        <p>Upload and view your personalized class schedules from your dashboard.</p>
      </div>
      <div class="service-card">
        <i class="fas fa-map"></i>
        <h3>Indoor Navigation</h3>
        <p>Navigate through campus buildings with integrated indoor maps and room directions.</p>
      </div>
      <div class="service-card">
        <i class="fas fa-bell"></i>
        <h3>Class Reminders</h3>
        <p>Get notified of upcoming classes and schedule changes in real-time.</p>
      </div>
    </div>
  </section>

  <section id="faqs">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-item">
      <div class="faq-question">How do I reset my password? <span>+</span></div>
      <div class="faq-answer">Click on "Forgot Password" on the login page and follow the instructions.</div>
    </div>
    <div class="faq-item">
      <div class="faq-question">Where can I find my class schedule? <span>+</span></div>
      <div class="faq-answer">Your class schedule is located on your dashboard under the "Upcoming Classes" section.</div>
    </div>
    <div class="faq-item">
      <div class="faq-question">Is ChronoNav available on mobile? <span>+</span></div>
      <div class="faq-answer">Yes, ChronoNav is mobile-friendly and works on any device with a browser.</div>
    </div>
  </section>

  <section id="contact">
    <h2>Contact Us</h2>
    <div class="contact">
      <form>
        <input type="text" placeholder="Your Name" required>
        <input type="email" placeholder="Your Email" required>
        <textarea rows="4" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 ChronoNav. All rights reserved.</p>
  </footer>

  <button class="scroll-top" id="scrollTop">Top</button>

  <script>
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
      const question = item.querySelector('.faq-question');
      const answer = item.querySelector('.faq-answer');
      question.addEventListener('click', () => {
        answer.classList.toggle('open');
        const symbol = question.querySelector('span');
        symbol.textContent = answer.classList.contains('open') ? '-' : '+';
      });
    });

    const scrollBtn = document.getElementById('scrollTop');
    window.addEventListener('scroll', () => {
      scrollBtn.classList.toggle('show', window.scrollY > 300);
    });
    scrollBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  </script>
</body>
</html>
