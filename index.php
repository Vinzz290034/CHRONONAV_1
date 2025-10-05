<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChronoNav</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Space+Grotesk:wght@400;500;700">

    <link rel="stylesheet" href="assets/styles/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png">
</head>

<body>
    <div class="layout-container">
        <section class="Header sticky-top bg-white p-0">
            <!-- Header -->
            <header class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 2.5rem; height: 2.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%">
                            <image
                                href="https://res.cloudinary.com/deua2yipj/image/upload/v1758917007/ChronoNav_logo_muon27.png"
                                x="0" y="0" width="100" height="100" />
                        </svg>
                    </div>
                    <h2 class="mb-0 text-black-50 fw-bold fs-5">ChronoNav</h2>
                </div>
                <div class="d-flex justify-content-end">
                    <div class="d-flex align-items-center gap-4">
                        <a class="text-dark text-decoration-none fw-medium nav-link" href="#about_section">About</a>
                        <a class="text-dark text-decoration-none fw-medium nav-link" href="#services">Services</a>
                        <a class="text-dark text-decoration-none fw-medium nav-link" href="#faqs">FAQs</a>
                        <a class="text-dark text-decoration-none fw-medium nav-link" href="#contact">Contact</a>
                    </div>
                </div>
            </header>
        </section>

        <!-- Main Content -->
        <main class="px-md-40 d-flex justify-content-center flex-grow-1">
            <div class="layout-content-container d-flex flex-column">
                <!-- Hero Section -->
                <section class="hero px-3 my-5 fade-in-up">
                    <div class="hero-section rounded-3 mb-3"
                        style='background-image: linear-gradient(rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.4) 100%), url("https://lh3.googleusercontent.com/aida-public/AB6AXuCyP22Lu22QqLujv1oYat76bZwyXSnLOUcQQ204fHPj0FHThRdHwybNmHXJFyBPjpz9qL2s0UOs37tMqLEllhTCXfVUpeaNCyhWczpwvnMHdGmDlVhZziwoFROBf5N3o6m5o_ig3DzVhNJVLB7dtktkFFyR2hrlFWITf9gKl4SN_vl9FxNq9b7MB5-oGsfrG2KykArMX5l_0aVt9LaEjU5949ciddty3rSSegk0VKc19f7hPPEex-jKIO4MFLKSksdsfBzvSwd-p0I");'>
                        <div class="hero-content text-center text-white px-4">
                            <h1 class="display-4 fw-bold mb-3">Navigate Your Campus with Ease</h1>
                            <p class="lead mb-5 fs-5">
                                ChronoNav is a mobile campus navigation and scheduling app that helps students
                                automatically import their class schedules, view them in a smart calendar, and receive
                                real-time turn-by-turn directions to classrooms and facilities, even offline.
                            </p>
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                <button onclick="window.location.href='auth/login.php'"
                                    class="btn btn-custom-primary px-4 py-2 rounded">Get Started</button>
                                <button class="btn btn-custom-secondary px-4 py-2 rounded">Watch Demo</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Key Features Section -->
                <section class="mb-5 px-3 py-5" id="features">
                    <div class="mb-5 text-center">
                        <h1 class="text-dark fw-bold display-5 mb-3 section-title">Key Features</h1>
                        <p class="text-dark fs-5 mb-0 mx-auto" style="max-width: 700px;">
                            Manage your academic journey with our intuitive app, designed to streamline your schedule,
                            help you navigate campus, and connect with your peers.
                        </p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-3 fade-in-up delay-1">
                            <div class="feature-card p-3 h-100 d-flex flex-column">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-96-88v64a8,8,0,0,1-16,0V132.94l-4.42,2.22a8,8,0,0,1-7.16-14.32l16-8A8,8,0,0,1,112,120Zm59.16,30.45L152,176h16a8,8,0,0,1,0,16H136a8,8,0,0,1-6.4-12.8l28.78-38.37A8,8,0,1,0,145.07,132a8,8,0,1,1-13.85-8A24,24,0,0,1,176,136,23.76,23.76,0,0,1,171.16,150.45Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Smart Schedule Import</h2>
                                    <p class="text-muted">Automatically import your class schedules from your university
                                        portal with our OCR technology.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up delay-2">
                            <div class="feature-card p-3 h-100 d-flex flex-column">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M128,64a40,40,0,1,0,40,40A40,40,0,0,0,128,64Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,128Zm0-112a88.1,88.1,0,0,0-88,88c0,31.4,14.51,64.68,42,96.25a254.19,254.19,0,0,0,41.45,38.3,8,8,0,0,0,9.18,0A254.19,254.19,0,0,0,174,200.25c27.45-31.57,42-64.85,42-96.25A88.1,88.1,0,0,0,128,16Zm0,206c-16.53-13-72-60.75-72-118a72,72,0,0,1,144,0C200,161.23,144.53,209,128,222Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Turn-by-Turn Navigation</h2>
                                    <p class="text-muted">Get real-time, turn-by-turn directions to classrooms and
                                        facilities across campus.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up delay-3">
                            <div class="feature-card p-3 h-100 d-flex flex-column">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M221.8,175.94C216.25,166.38,208,139.33,208,104a80,80,0,1,0-160,0c0,35.34-8.26,62.38-13.81,71.94A16,16,0,0,0,48,200H88.81a40,40,0,0,0,78.38,0H208a16,16,0,0,0,13.8-24.06ZM128,216a24,24,0,0,1-22.62-16h45.24A24,24,0,0,1,128,216ZM48,184c7.7-13.24,16-43.92,16-80a64,64,0,1,1,128,0c0,36.05,8.28,66.73,16,80Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Reminders & Alerts</h2>
                                    <p class="text-muted">Receive timely reminders and alerts for classes, exams, and
                                        campus events.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up">
                            <div class="feature-card p-3 h-100 d-flex flex-column">
                                <div class="feature-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M213.92,210.62a8,8,0,1,1-11.84,10.76l-52-57.15a60,60,0,0,0-57.41,7.24,8,8,0,1,1-9.42-12.93A75.43,75.43,0,0,1,128,144c1.28,0,2.55,0,3.82.1L104.9,114.49A108,108,0,0,0,61,135.31,8,8,0,0,1,49.73,134,8,8,0,0,1,51,122.77a124.27,124.27,0,0,1,41.71-21.66L69.37,75.4a155.43,155.43,0,0,0-40.29,24A8,8,0,0,1,18.92,87,171.87,171.87,0,0,1,58,62.86L42.08,45.38A8,8,0,1,1,53.92,34.62ZM128,192a12,12,0,1,0,12,12A12,12,0,0,0,128,192ZM237.08,87A172.3,172.3,0,0,0,106,49.4a8,8,0,1,0,2,15.87A158.33,158.33,0,0,1,128,64a156.25,156.25,0,0,1,98.92,35.37A8,8,0,0,0,237.08,87ZM195,135.31a8,8,0,0,0,11.24-1.3,8,8,0,0,0-1.3-11.24,124.25,124.25,0,0,0-51.73-24.2A8,8,0,1,0,150,114.24,108.12,108.12,0,0,1,195,135.31Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Offline Access</h2>
                                    <p class="text-muted">Access your schedule and campus maps even without an internet
                                        connection.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- How It Works Section -->
                <section class="mb-5 px-3 py-5 bg-light rounded-3">
                    <div class="mb-5 text-center">
                        <h1 class="text-dark fw-bold display-5 mb-3 section-title">How It Works</h1>
                        <p class="text-dark fs-5 mb-0">Get started in three simple steps to enhance your campus
                            experience.</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4 how-it-works-step">
                            <div class="d-flex flex-column h-100 text-center">
                                <div class="how-it-works-image ratio ratio-16x9 mb-4 rounded-3"
                                    style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAVeXZEhzOYOYaSOqYDD-tJIfOPzfyZsYvERnrN1lzCBv3pJXpPGDcHduLnoRSCdYj-dO9g9k3qIHWsHXxF19i9XdKRzOGJ7vsFzQjYc_q48b4XM8E4pnVtgM9Z3S-rU2wxhzRt8TsB3LW1MaILA9Y1NKSufJit9IbjWKwv8Wij_K44h7ySHAQLQb_Kp4IZxiwsy-K-KYcB6EBjakHQwOaYxtPpMjpGl9N6AAcTjTDU-gD-ULgUSKZqFYRrEYhI92xb2slLaUeCAmg");'>
                                </div>
                                <div>
                                    <h3 class="text-dark fw-bold mb-2">Upload</h3>
                                    <p class="text-muted">Upload your class schedule from your university portal or take
                                        a screenshot.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 how-it-works-step">
                            <div class="d-flex flex-column h-100 text-center">
                                <div class="how-it-works-image ratio ratio-16x9 mb-4 rounded-3"
                                    style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBuz2x1Dp-ItPfZONvwycCKGu-0UmG4AiPiuSR_1gqShC2YPjgfoGavVF0QAKWoDXPEM8K0qs31EaTno483xWVxaeWiovosD8M_Vt-CLezpCznqgUdJcE6GCSe1Mbo45CL7Xi-jyRHdwwuog5AX4Tnw1R5D06paSkEQ34frVoXb4quAr6Ed1zkMX8mDb05TCVDcpjQSBT5EM8CZVJVHXu-XHjZP3IYItZ4CWJVKNom0AXiXsxLl7LowN7Fxd5XRgMUUlIELpkhZBWs");'>
                                </div>
                                <div>
                                    <h3 class="text-dark fw-bold mb-2">Organize</h3>
                                    <p class="text-muted">Organize your schedule with our smart calendar and set
                                        preferences.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 how-it-works-step">
                            <div class="d-flex flex-column h-100 text-center">
                                <div class="how-it-works-image ratio ratio-16x9 mb-4 rounded-3"
                                    style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDHZZRhppt1VPaIMRDYuMAPE4Exa3Z1hApycEiasuS_5l-T3zX1fLseUkF61RLp9AO9b4UXY8N-2-IqIzZDU2yQRzvHYOELnthRUts9mhhiT7wlsgKbEuY7YMDO0f56FVevZpP9RhC3-vwe6Ah0Dtsa7nls6LVnU0DoGFhWPEGGbkhAfoLeZpyNsgtZlkn-SVFzAnPVr4UzPuz0sesDb8FFH4WtSUu1JNFYJ1OgsWoY239sukJFADy15RSduYvZWrXk83QLORKfJMI");'>
                                </div>
                                <div>
                                    <h3 class="text-dark fw-bold mb-2">Navigate</h3>
                                    <p class="text-muted">Navigate your daily routine with ease using turn-by-turn
                                        directions.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Team Section -->
                <section class="mb-5" id="about">
                    <div class="mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">Our Team</h2>
                        <p class="text-dark fs-5 mb-0 mx-auto" style="max-width: 700px;">
                            Meet the dedicated team behind ChronoNav, working to make campus navigation seamless for
                            students everywhere.
                        </p>
                    </div>
                    <div class="row g-4 px-3">
                        <div class="col-md-6 col-lg-3 fade-in-up">
                            <div class="team-card text-center p-4 h-100 d-flex flex-column">
                                <div class="px-4 mb-3">
                                    <div class="team-image ratio ratio-1x1 mx-auto"
                                        style='background-image: url("https://res.cloudinary.com/deua2yipj/image/upload/ar_1:1,c_auto,g_auto/2c17c1b2-9043-4c49-95b1-40489152c427.jpg");'>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <h3 class="text-dark fw-bold mb-1">Vince Andrew Santoya</h3>
                                    <p class="text-muted">Project Manager</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up delay-1">
                            <div class="team-card text-center p-4 h-100 d-flex flex-column">
                                <div class="px-4 mb-3">
                                    <div class="team-image ratio ratio-1x1 mx-auto"
                                        style='background-image: url("https://res.cloudinary.com/deua2yipj/image/upload/v1759059007/35c1f762-0adc-446b-b017-c4b5856087e6.png");'>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <h3 class="text-dark fw-bold mb-1">Eric Dominic Momo</h3>
                                    <p class="text-muted">Head of Development</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up delay-2">
                            <div class="team-card text-center p-4 h-100 d-flex flex-column">
                                <div class="px-4 mb-3">
                                    <div class="team-image ratio ratio-1x1 mx-auto"
                                        style='background-image: url("https://res.cloudinary.com/deua2yipj/image/upload/ar_1:1,c_auto,g_auto/b510212b-1092-456d-8d31-739c4df72de9.jpg");'>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <h3 class="text-dark fw-bold mb-1">Karl Kent Amarila</h3>
                                    <p class="text-muted">Database Administrator</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 fade-in-up delay-3">
                            <div class="team-card text-center p-4 h-100 d-flex flex-column">
                                <div class="px-4 mb-3">
                                    <div class="team-image ratio ratio-1x1 mx-auto"
                                        style='background-image: url("https://res.cloudinary.com/deua2yipj/image/upload/ar_1:1,c_auto,g_auto/170d4e15-7367-49a2-83ff-3d91b9c31ed7.jpg");'>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <h3 class="text-dark fw-bold mb-1">Tristan Jesus Elvinia</h3>
                                    <p class="text-muted">Frontend Developer, UI/UX Designer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Testimonials Section -->
                <section class="mb-5 px-3">
                    <div class="mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">What Students Say</h2>
                        <p class="text-dark fs-5 mb-0">Hear from students who have transformed their campus experience
                            with ChronoNav.</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-6 fade-in-up">
                            <div class="testimonial-card p-4 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="team-image rounded-circle me-3"
                                        style="width: 50px; height: 50px; background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuB0KNAYcteOo7ddwny6ovM5vOAOVcXV9Ihd5hQ1iR8rW0hqjWbSUOlyDyC8CRQD3twV9vAweT_u8LLIO0CpfGYuJC2grlUNDVULCh6BFswVOfI56J8T29CE8AhpuF2o0gTLsGRPpZz6HcDwnHyH1b1dM25KUOLNAA8SloSPfCtHdvHIROH-8tnJzgOOq5kv0xwGkvmdpsGzUrBcEeaw3jPkBh1tsKFJOZlR5aRtFHzNWH9nHvG5LSNkuYkTNmrjs0KnF0mGTDeEVtE');">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="text-dark fw-bold mb-0">Sophia Clark</p>
                                        <p class="text-muted small mb-0">Computer Science Major</p>
                                    </div>
                                </div>
                                <div class="testimonial-stars mb-3">
                                    <!-- 5 filled stars -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-dark mb-3 fst-italic">
                                    "This app has been a lifesaver! I no longer have to worry about getting lost on
                                    campus or missing classes. The schedule import feature is incredibly convenient and
                                    saves me so much time each semester."
                                </p>
                            </div>
                        </div>
                        <div class="col-lg-6 fade-in-up delay-1">
                            <div class="testimonial-card p-4 h-100">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="team-image rounded-circle me-3"
                                        style="width: 50px; height: 50px; background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDHVZsSf_LKpwH6z4jD4cCSwWz0u2qF4161bHeSWs9hsSd1VOb-yO6kz1t0VfAJoNawOZicO6jo5t3DpAVz8XxTZblCL5CqKuc2sGjsVfY94d2onh0Pj0j0QPJ6RVM1NDL2reaHeXmyTmsyQm7EfbaacrcD1--F03B1azlCbpjaUxgbavaARo_Xpo8Kbvnt7OktT20dIgdGhqklL9wMjBA3NMdfCYHimO2TWOVJvlr6tlQ140er3Rya-M7Pcp8UG9AO_pty2aYzw9E');">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="text-dark fw-bold mb-0">Liam Walker</p>
                                        <p class="text-muted small mb-0">Engineering Student</p>
                                    </div>
                                </div>
                                <div class="testimonial-stars mb-3">
                                    <!-- 4 filled stars, 1 outline star -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M234.5,114.38l-45.1,39.36,13.51,58.6a16,16,0,0,1-23.84,17.34l-51.11-31-51,31a16,16,0,0,1-23.84-17.34L66.61,153.8,21.5,114.38a16,16,0,0,1,9.11-28.06l59.46-5.15,23.21-55.36a15.95,15.95,0,0,1,29.44,0h0L166,81.17l59.44,5.15a16,16,0,0,1,9.11,28.06Z">
                                        </path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" fill="#bac4cf"
                                        viewBox="0 0 256 256">
                                        <path
                                            d="M239.2,97.29a16,16,0,0,0-13.81-11L166,81.17,142.72,25.81h0a15.95,15.95,0,0,0-29.44,0L90.07,81.17,30.61,86.32a16,16,0,0,0-9.11,28.06L66.61,153.8,53.09,212.34a16,16,0,0,0,23.84,17.34l51-31,51.11,31a16,16,0,0,0,23.84-17.34l-13.51-58.6,45.1-39.36A16,16,0,0,0,239.2,97.29Zm-15.22,5-45.1,39.36a16,16,0,0,0-5.08,15.71L187.35,216v0l-51.07-31a15.9,15.9,0,0,0-16.54,0l-51,31h0L82.2,157.4a16,16,0,0,0-5.08-15.71L32,102.35a.37.37,0,0,1,0-.09l59.44-5.14a16,16,0,0,0,13.35-9.75L128,32.08l23.2,55.29a16,16,0,0,0,13.35,9.75L224,102.26S224,102.32,224,102.33Z">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-dark mb-3 fst-italic">
                                    "Great app for navigating campus. The turn-by-turn directions are accurate, and the
                                    offline access is a huge plus when I'm in areas with poor reception. Would recommend
                                    to any student!"
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- About Section -->
                <section class="mb-5" id="about_section">
                    <div class="pt-5 mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">About ChronoNav</h2>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <p class="text-dark fs-5">
                                Our mission is to simplify campus life for students by providing an intuitive and
                                reliable app that streamlines their schedules and navigation. We aim to enhance the
                                student experience by reducing stress and saving time, allowing students to focus on
                                their academic and personal growth.
                            </p>
                            <div class="d-flex pt-3 justify-content-start">
                                <button class="btn btn-custom-primary px-4 py-2">
                                    <span>Learn More About Us</span>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="custom-card p-3 d-flex flex-column h-100">
                                        <div class="feature-icon mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                fill="currentColor" viewBox="0 0 256 256">
                                                <path
                                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm64-88a8,8,0,0,1-8,8H128a8,8,0,0,1-8-8V72a8,8,0,0,1,16,0v48h48A8,8,0,0,1,192,128Z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <h2 class="text-dark fw-bold fs-6">Time Management</h2>
                                            <p class="text-muted small">Optimize your schedule and never miss a class or
                                                event.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-card p-3 d-flex flex-column h-100">
                                        <div class="feature-icon mb-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                                fill="currentColor" viewBox="0 0 256 256">
                                                <path
                                                    d="M128,64a40,40,0,1,0,40,40A40,40,0,0,0,128,64Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,128Zm0-112a88.1,88.1,0,0,0-88,88c0,31.4,14.51,64.68,42,96.25a254.19,254.19,0,0,0,41.45,38.3,8,8,0,0,0,9.18,0A254.19,254.19,0,0,0,174,200.25c27.45-31.57,42-64.85,42-96.25A88.1,88.1,0,0,0,128,16Zm0,206c-16.53-13-72-60.75-72-118a72,72,0,0,1,144,0C200,161.23,144.53,209,128,222Z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <h2 class="text-dark fw-bold fs-6">Campus Navigation</h2>
                                            <p class="text-muted small">Effortlessly find your way around campus with
                                                real-time directions.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Services Section -->
                <section class="mb-5" id="services">
                    <div class="pt-5 mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">Our Services</h2>
                        <p class="text-dark fs-5 mb-0 mx-auto" style="max-width: 700px;">
                            ChronoNav offers a comprehensive suite of features designed to make campus life easier and
                            more organized.
                        </p>
                    </div>
                    <div class="row g-4 px-3">
                        <div class="col-md-6 col-lg-4 fade-in-up">
                            <div class="custom-card p-4 d-flex flex-column h-100">
                                <div class="feature-icon mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M170.48,115.7A44,44,0,0,0,140,40H72a8,8,0,0,0-8,8V200a8,8,0,0,0,8,8h80a48,48,0,0,0,18.48-92.3ZM80,56h60a28,28,0,0,1,0,56H80Zm72,136H80V128h72a32,32,0,0,1,0,64Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">OCR-Based Schedule Import</h2>
                                    <p class="text-muted">Easily import your class schedule by scanning or uploading a
                                        screenshot from your university portal.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 fade-in-up delay-1">
                            <div class="custom-card p-4 d-flex flex-column h-100">
                                <div class="feature-icon mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M128,64a40,40,0,1,0,40,40A40,40,0,0,0,128,64Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,128Zm0-112a88.1,88.1,0,0,0-88,88c0,31.4,14.51,64.68,42,96.25a254.19,254.19,0,0,0,41.45,38.3,8,8,0,0,0,9.18,0A254.19,254.19,0,0,0,174,200.25c27.45-31.57,42-64.85,42-96.25A88.1,88.1,0,0,0,128,16Zm0,206c-16.53-13-72-60.75-72-118a72,72,0,0,1,144,0C200,161.23,144.53,209,128,222Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Turn-by-Turn Navigation</h2>
                                    <p class="text-muted">Get precise directions to classrooms, labs, and other campus
                                        facilities with real-time updates.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 fade-in-up delay-2">
                            <div class="custom-card p-4 d-flex flex-column h-100">
                                <div class="feature-icon mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        fill="currentColor" viewBox="0 0 256 256">
                                        <path
                                            d="M213.92,210.62a8,8,0,1,1-11.84,10.76l-52-57.15a60,60,0,0,0-57.41,7.24,8,8,0,1,1-9.42-12.93A75.43,75.43,0,0,1,128,144c1.28,0,2.55,0,3.82.1L104.9,114.49A108,108,0,0,0,61,135.31,8,8,0,0,1,49.73,134,8,8,0,0,1,51,122.77a124.27,124.27,0,0,1,41.71-21.66L69.37,75.4a155.43,155.43,0,0,0-40.29,24A8,8,0,0,1,18.92,87,171.87,171.87,0,0,1,58,62.86L42.08,45.38A8,8,0,1,1,53.92,34.62ZM128,192a12,12,0,1,0,12,12A12,12,0,0,0,128,192ZM237.08,87A172.3,172.3,0,0,0,106,49.4a8,8,0,1,0,2,15.87A158.33,158.33,0,0,1,128,64a156.25,156.25,0,0,1,98.92,35.37A8,8,0,0,0,237.08,87ZM195,135.31a8,8,0,0,0,11.24-1.3,8,8,0,0,0-1.3-11.24,124.25,124.25,0,0,0-51.73-24.2A8,8,0,1,0,150,114.24,108.12,108.12,0,0,1,195,135.31Z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="d-flex flex-column">
                                    <h2 class="text-dark fw-bold fs-5 mb-2">Offline Mode</h2>
                                    <p class="text-muted">Access your schedule and campus maps even without an internet
                                        connection, perfect for areas with poor reception.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- FAQs Section -->
                <section class="mb-5" id="faqs">
                    <div class="pt-5 mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">Frequently Asked Questions</h2>
                        <p class="text-dark fs-5 mb-0">Find answers to common questions about ChronoNav.</p>
                    </div>
                    <div class="px-3">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item accordion-custom mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqOne" aria-expanded="false" aria-controls="faqOne">
                                        <span class="text-dark fw-medium">How do I import my schedule?</span>
                                    </button>
                                </h2>
                                <div id="faqOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>You can import your schedule by taking a screenshot of your university portal
                                            schedule or uploading an existing image. Our OCR technology will
                                            automatically extract your class information and add it to your ChronoNav
                                            calendar.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item accordion-custom mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqTwo" aria-expanded="false" aria-controls="faqTwo">
                                        <span class="text-dark fw-medium">Can I use the app offline?</span>
                                    </button>
                                </h2>
                                <div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Yes! ChronoNav works offline once you've downloaded your campus map and
                                            schedule. You'll still get turn-by-turn navigation and access to your
                                            calendar even without an internet connection.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item accordion-custom mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqThree" aria-expanded="false" aria-controls="faqThree">
                                        <span class="text-dark fw-medium">Is the app accessible?</span>
                                    </button>
                                </h2>
                                <div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Absolutely. ChronoNav is designed with accessibility in mind, featuring voice
                                            guidance, high contrast modes, and screen reader compatibility to ensure all
                                            students can navigate campus with ease.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Section -->
                <section class="mb-5" id="contact">
                    <div class="pt-5 mb-5 text-center">
                        <h2 class="text-dark fw-bold display-5 mb-3 section-title">Get In Touch</h2>
                        <p class="text-dark fs-5 mb-0">Have questions or feedback? We'd love to hear from you.</p>
                    </div>

                    <!-- Contact Form -->
                    <div class="px-3">
                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-medium">Your Name</label>
                                <input type="text" class="form-control form-control-custom"
                                    placeholder="Enter your name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark fw-medium">Your Email</label>
                                <input type="email" class="form-control form-control-custom"
                                    placeholder="Enter your email">
                            </div>
                        </div>
                        <div class="row g-4 mb-3">
                            <div class="col-12">
                                <label class="form-label text-dark fw-medium">Message</label>
                                <textarea class="form-control form-control-custom"
                                    placeholder="Enter your message"></textarea>
                            </div>
                        </div>
                        <div class="d-flex px-0 py-3 justify-content-start">
                            <button class="btn btn-custom-primary px-4 py-2">
                                <span>Send Message</span>
                            </button>
                        </div>
                        <p class="text-muted px-0 pb-3 pt-1">You can also reach us at support@chrononav.com</p>

                        <!-- Social Media Links -->
                        <div class="d-flex flex-wrap gap-3 px-0">
                            <a href="#" class="social-icon text-muted text-decoration-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" fill="currentColor"
                                    viewBox="0 0 256 256">
                                    <path
                                        d="M247.39,68.94A8,8,0,0,0,240,64H209.57A48.66,48.66,0,0,0,168.1,40a46.91,46.91,0,0,0-33.75,13.7A47.9,47.9,0,0,0,120,88v6.09C79.74,83.47,46.81,50.72,46.46,50.37a8,8,0,0,0-13.65,4.92c-4.31,47.79,9.57,79.77,22,98.18a110.93,110.93,0,0,0,21.88,24.2c-15.23,17.53-39.21,26.74-39.47,26.84a8,8,0,0,0-3.85,11.93c.75,1.12,3.75,5.05,11.08,8.72C53.51,229.7,65.48,232,80,232c70.67,0,129.72-54.42,135.75-124.44l29.91-29.9A8,8,0,0,0,247.39,68.94Zm-45,29.41a8,8,0,0,0-2.32,5.14C196,166.58,143.28,216,80,216c-10.56,0-18-1.4-23.22-3.08,11.51-6.25,27.56-17,37.88-32.48A8,8,0,0,0,92,169.08c-.47-.27-43.91-26.34-44-96,16,13,45.25,33.17,78.67,38.79A8,8,0,0,0,136,104V88a32,32,0,0,1,9.6-22.92A30.94,30.94,0,0,1,167.9,56c12.66.16,24.49,7.88,29.44,19.21A8,8,0,0,0,204.67,80h16Z">
                                    </path>
                                </svg>
                            </a>
                            <a href="#" class="social-icon text-muted text-decoration-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" fill="currentColor"
                                    viewBox="0 0 256 256">
                                    <path
                                        d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160ZM176,24H80A56.06,56.06,0,0,0,24,80v96a56.06,56.06,0,0,0,56,56h96a56.06,56.06,0,0,0,56-56V80A56.06,56.06,0,0,0,176,24Zm40,152a40,40,0,0,1-40,40H80a40,40,0,0,1-40-40V80A40,40,0,0,1,80,40h96a40,40,0,0,1,40,40ZM192,76a12,12,0,1,1-12-12A12,12,0,0,1,192,76Z">
                                    </path>
                                </svg>
                            </a>
                            <a href="#" class="social-icon text-muted text-decoration-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" fill="currentColor"
                                    viewBox="0 0 256 256">
                                    <path
                                        d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm8,191.63V152h24a8,8,0,0,0,0-16H136V112a16,16,0,0,1,16-16h16a8,8,0,0,0,0-16H152a32,32,0,0,0-32,32v24H96a8,8,0,0,0,0,16h24v63.63a88,88,0,1,1,16,0Z">
                                    </path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="d-flex justify-content-center mb-5">
                    <div class="text-white rounded-3 p-5 text-center w-100"
                        style="max-width: 800px; background: linear-gradient(135deg, #2E78C6 0%, rgb(37, 191, 252) 100%);">
                        <h2 class="fw-bold mb-3">Ready to Simplify Your Campus Life?</h2>
                        <p class="mb-4 fs-5">Download ChronoNav today and take control of your schedule and navigation.
                        </p>
                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                            <button class="btn btn-light px-4 py-2 rounded fw-bold">Download for Android</button>
                            <button class="btn btn-outline-light px-4 py-2 rounded fw-bold">Learn More</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-auto">
            <div class="layout-content-container mx-auto">
                <div class="text-center px-3 py-5">
                    <div class="d-flex flex-wrap justify-content-center gap-4 mb-4 footer-links">
                        <a class="text-muted text-decoration-none" href="#about_section">About Us</a>
                        <a class="text-muted text-decoration-none" href="#services">Services</a>
                        <a class="text-muted text-decoration-none" href="#faqs">FAQs</a>
                        <a class="text-muted text-decoration-none" href="#contact">Contact</a>
                        <button class="open-button btn bg-white b-0 p-0 by-0" onclick="openPrivacy()">
                            <a class="text-muted text-decoration-none" href="#">Privacy Policy</a>
                        </button>
                        <button class="open-button btn bg-white b-0 p-0 by-0" onclick="openTerms()">
                            <a class="text-muted text-decoration-none" href="#">Terms of Service</a>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                        <a href="#" class="text-muted social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M247.39,68.94A8,8,0,0,0,240,64H209.57A48.66,48.66,0,0,0,168.1,40a46.91,46.91,0,0,0-33.75,13.7A47.9,47.9,0,0,0,120,88v6.09C79.74,83.47,46.81,50.72,46.46,50.37a8,8,0,0,0-13.65,4.92c-4.31,47.79,9.57,79.77,22,98.18a110.93,110.93,0,0,0,21.88,24.2c-15.23,17.53-39.21,26.74-39.47,26.84a8,8,0,0,0-3.85,11.93c.75,1.12,3.75,5.05,11.08,8.72C53.51,229.7,65.48,232,80,232c70.67,0,129.72-54.42,135.75-124.44l29.91-29.9A8,8,0,0,0,247.39,68.94Zm-45,29.41a8,8,0,0,0-2.32,5.14C196,166.58,143.28,216,80,216c-10.56,0-18-1.4-23.22-3.08,11.51-6.25,27.56-17,37.88-32.48A8,8,0,0,0,92,169.08c-.47-.27-43.91-26.34-44-96,16,13,45.25,33.17,78.67,38.79A8,8,0,0,0,136,104V88a32,32,0,0,1,9.6-22.92A30.94,30.94,0,0,1,167.9,56c12.66.16,24.49,7.88,29.44,19.21A8,8,0,0,0,204.67,80h16Z">
                                </path>
                            </svg>
                        </a>
                        <a href="#" class="text-muted social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160ZM176,24H80A56.06,56.06,0,0,0,24,80v96a56.06,56.06,0,0,0,56,56h96a56.06,56.06,0,0,0,56-56V80A56.06,56.06,0,0,0,176,24Zm40,152a40,40,0,0,1-40,40H80a40,40,0,0,1-40-40V80A40,40,0,0,1,80,40h96a40,40,0,0,1,40,40ZM192,76a12,12,0,1,1-12-12A12,12,0,0,1,192,76Z">
                                </path>
                            </svg>
                        </a>
                        <a href="#" class="text-muted social-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor"
                                viewBox="0 0 256 256">
                                <path
                                    d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm8,191.63V152h24a8,8,0,0,0,0-16H136V112a16,16,0,0,1,16-16h16a8,8,0,0,0,0-16H152a32,32,0,0,0-32,32v24H96a8,8,0,0,0,0,16h24v63.63a88,88,0,1,1,16,0Z">
                                </path>
                            </svg>
                        </a>
                    </div>
                    <p class="text-muted mb-0">© 2025 ChronoNav. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="privacy-modal mt-2" id="privacyModal">
        <div class="modal-content mt-5">
            <div class="privacy-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="privacy-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">Privacy Policy</h2>
                        <p class="mb-0 opacity-75">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="close-btn" onclick="closePrivacy()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="privacy-body">
                <p class="mb-4">Welcome to <strong>CHRONONAV</strong>! Your privacy is very important to us. This
                    Privacy
                    Policy explains how we collect, use, and protect your information when you use our mobile and web
                    application.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-info-circle"></i>
                    </span>
                    Information We Collect
                </h4>
                <p>We collect only the information necessary to provide our services:</p>
                <ul class="custom-list">
                    <li><strong>Account Information:</strong> Name, email address, and student ID (if provided during
                        registration)</li>
                    <li><strong>Schedule Data:</strong> Uploaded study loads (PDFs or images) processed using Optical
                        Character Recognition (OCR)</li>
                    <li><strong>Location Data:</strong> GPS location for navigation within the campus</li>
                    <li><strong>Device Information:</strong> Basic device and app usage data (for troubleshooting and
                        improvements)</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-cogs"></i>
                    </span>
                    How We Use Your Information
                </h4>
                <p>Your data is used to:</p>
                <ul class="custom-list">
                    <li>Generate and organize your personalized schedule</li>
                    <li>Provide real-time navigation and directions within the campus</li>
                    <li>Send reminders and notifications for upcoming classes</li>
                    <li>Improve app features, performance, and user experience</li>
                </ul>
                <div class="highlight-box">
                    <p class="mb-0"><strong>We do not sell, rent, or share your information with third parties.</strong>
                    </p>
                </div>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-database"></i>
                    </span>
                    Data Storage and Security
                </h4>
                <ul class="custom-list">
                    <li>All schedule and navigation data are securely stored in encrypted databases</li>
                    <li>Access is limited to authorized CHRONONAV developers and administrators</li>
                    <li>Offline data (such as cached maps) is stored only on your device and is cleared when you
                        uninstall
                        the app</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-share-alt"></i>
                    </span>
                    Data Sharing
                </h4>
                <p>We may share anonymized usage statistics (e.g., most used features, error logs) to improve the
                    system.
                </p>
                <p>We will never disclose personal information unless:</p>
                <ul class="custom-list">
                    <li>Required by law, or</li>
                    <li>Necessary to protect the rights, property, or safety of CHRONONAV users</li>
                </ul>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-user-check"></i>
                    </span>
                    Your Rights
                </h4>
                <p>You have the right to:</p>
                <ul class="custom-list">
                    <li><strong>Access</strong> your personal information stored in CHRONONAV</li>
                    <li><strong>Update or correct</strong> your account details</li>
                    <li><strong>Request deletion</strong> of your account and related data at any time</li>
                </ul>
                <p>To exercise these rights, please contact us at: <strong>chrononav.support@yourdomain.com</strong></p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-child"></i>
                    </span>
                    Children's Privacy
                </h4>
                <p>CHRONONAV is designed for university students and staff. We do not knowingly collect personal data
                    from
                    children under 13 years old.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    Updates to this Privacy Policy
                </h4>
                <p>We may update this Privacy Policy from time to time to reflect changes in technology, laws, or our
                    services. We will notify users of significant updates via in-app notifications or email.</p>

                <h4 class="section-title">
                    <span class="privacy-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Contact Us
                </h4>
                <p>If you have any questions, feedback, or concerns about this Privacy Policy, please contact us at:</p>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email:</strong> support@ChronoNav.com
                    </div>
                </div>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Address:</strong> Sanciangko St, Cebu City, 6000 Cebu
                    </div>
                </div>
            </div>

            <div class="privacy-footer">
                <button class="btn btn-primary" onclick="closePrivacy()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="terms-modal mt-2" id="terms_Modal">
        <div class="modal-content mt-5">
            <div class="terms-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="terms-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <h2 class="mb-1">Terms of Service</h2>
                        <p class="mb-0 opacity-75">Last updated: September 30, 2025</p>
                    </div>
                </div>
                <button class="close-btn" onclick="closeTerms()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="terms-body">
                <p class="mb-4">Welcome to <strong>CHRONONAV</strong>! These Terms of Service ("Terms") govern your
                    use
                    of
                    the CHRONONAV mobile and web application. By downloading, accessing, or using CHRONONAV, you
                    agree
                    to
                    comply with these Terms. Please read them carefully.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    Acceptance of Terms
                </h4>
                <p>By using CHRONONAV, you confirm that you:</p>
                <ul class="custom-list">
                    <li>Are at least 13 years of age (or the minimum required by your institution)</li>
                    <li>Agree to follow these Terms and our Privacy Policy</li>
                    <li>Use CHRONONAV only for lawful academic and personal purposes</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-list-alt"></i>
                    </span>
                    Description of Service
                </h4>
                <p>CHRONONAV provides:</p>
                <ul class="custom-list">
                    <li>OCR-based schedule import from official study loads</li>
                    <li>Personalized timetable management</li>
                    <li>Campus navigation and turn-by-turn directions</li>
                    <li>Class reminders and alerts</li>
                    <li>Limited offline navigation functionality</li>
                </ul>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-user-shield"></i>
                    </span>
                    User Responsibilities
                </h4>
                <p>You agree not to:</p>
                <ul class="custom-list">
                    <li>Upload false or misleading information</li>
                    <li>Share your account credentials with others</li>
                    <li>Use CHRONONAV to engage in cheating, harassment, or illegal activities</li>
                    <li>Tamper with or attempt to hack the system</li>
                </ul>
                <p>You are responsible for ensuring your device is compatible with the app.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-database"></i>
                    </span>
                    Data and Privacy
                </h4>
                <p>Our use of your data is described in the <strong>Privacy Policy</strong>. By using CHRONONAV, you
                    consent
                    to the collection and use of your information as outlined there.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-check-double"></i>
                    </span>
                    Accuracy of Information
                </h4>
                <p>While CHRONONAV strives to provide accurate schedules and navigation, we do not guarantee that:
                </p>
                <ul class="custom-list">
                    <li>All schedules imported via OCR will be 100% error-free</li>
                    <li>Campus navigation routes will always reflect real-time construction, closures, or events
                    </li>
                </ul>
                <p>Users should verify critical details with their school's official sources.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-copyright"></i>
                    </span>
                    Intellectual Property
                </h4>
                <p>All rights, trademarks, and content within CHRONONAV belong to the development team and/or the
                    affiliated
                    university. You may not copy, modify, or redistribute CHRONONAV without permission.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                    Limitation of Liability
                </h4>
                <p>CHRONONAV is provided <strong>"as is."</strong> We are not responsible for:</p>
                <ul class="custom-list">
                    <li>Missed classes, delays, or wrong directions caused by inaccurate data</li>
                    <li>Damages caused by reliance on the app in critical situations</li>
                </ul>
                <p>Your use of the app is at your own risk.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-sync-alt"></i>
                    </span>
                    Service Modifications
                </h4>
                <p>We reserve the right to update, modify, or discontinue CHRONONAV at any time, with or without
                    notice.
                </p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-ban"></i>
                    </span>
                    Termination
                </h4>
                <p>We may suspend or terminate your access if you violate these Terms. You may also delete your
                    account
                    at
                    any time.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-balance-scale"></i>
                    </span>
                    Governing Law
                </h4>
                <p>These Terms shall be governed by the laws of the Republic of the Philippines.</p>

                <h4 class="section-title">
                    <span class="terms-icon">
                        <i class="fas fa-envelope"></i>
                    </span>
                    Contact Us
                </h4>
                <p>If you have questions about these Terms, please contact us:</p>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <strong>Email:</strong> support@ChronoNav.com
                    </div>
                </div>
                <div class="contact-info">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <strong>Address:</strong> Sanciangko St, Cebu City, 6000 Cebu
                    </div>
                </div>
            </div>

            <div class="terms-footer">
                <button class="btn btn-primary" onclick="closeTerms()">
                    <i class="fas fa-times me-2"></i> Close
                </button>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Function to open the privacy policy modal
        function openPrivacy() {
            document.getElementById('privacyModal').classList.add('active');
            // Prevent the default link behavior
            event.preventDefault();
        }

        // Function to close the privacy policy modal
        function closePrivacy() {
            document.getElementById('privacyModal').classList.remove('active');
        }

        // Close modal when clicking outside the content
        document.getElementById('privacyModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closePrivacy();
            }
        });

        //--------------------------------------------------------//
        // Function to open the terms of service modal
        function openTerms() {
            document.getElementById('terms_Modal').classList.add('active');
            // Prevent the default link behavior
            event.preventDefault();
        }

        // Function to close the terms of service modal
        function closeTerms() {
            document.getElementById('terms_Modal').classList.remove('active');
        }

        // Close modal when clicking outside the content
        document.getElementById('terms_Modal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeTerms();
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>