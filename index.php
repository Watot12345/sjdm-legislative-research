<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sangguniang Panlungsod ng San Jose del Monte </title>
  <meta name="description" content="Official Legislative Research, Policy Analysis and Impact Evaluation Portal of the Sangguniang Panlungsod ng San Jose del Monte, Bulacan.">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <style>
    :root {
      --navy-950: #0A1F3D;
      --blue-700: #1E4D8C;
      --blue-600: #2563A6;
      --blue-500: #3B7DC4;
      --gold-500: #C9A227;
      --gold-400: #DDB94A;
      --gold-100: #F5E9C6;
      --paper: #F7F8FA;
      --paper-alt: #EEF3F9;
      --ink-900: #12202E;
      --ink-600: #4B5B6B;
      --line: #E1E7EE;
      --glass-bg: rgba(255, 255, 255, 0.82);
      --glass-border: rgba(255, 255, 255, 0.4);
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      color: var(--ink-900);
      background: var(--paper);
      -webkit-font-smoothing: antialiased;

      /* ---- single background seal (large, centered, low opacity) ---- */
      background-image: url('City.jpg');
      background-size: 780px 780px;
      background-repeat: no-repeat;
      background-position: center 120px;
      background-attachment: fixed;
      position: relative;
    }

    /* subtle overlay to keep readability */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(247, 248, 250, 0.78);
      backdrop-filter: blur(1px);
      z-index: -1;
      pointer-events: none;
    }

    /* all main content sections get a glass/soft background so text is readable */
    .wrap, .hero-inner, .sp-section, .final-cta, footer {
      position: relative;
      z-index: 2;
    }

    /* make cards & sections slightly more opaque so they stand out from the seal bg */
    .stat-card, .vm-card, .value-card, .highlight-card {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(4px);
      border: 1px solid var(--glass-border);
      box-shadow: 0 8px 24px rgba(0,0,0,0.04);
    }

    .sp-section {
      background: rgba(10, 31, 61, 0.92);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255, 255, 255, 0.15);
    }

    header {
      background: rgba(247, 248, 250, 0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--line);
    }

    .hero {
      background: rgba(10, 31, 61, 0.88);
      backdrop-filter: blur(2px);
    }
    .hero-illustration {
      opacity: 0.6;
    }
    .hero-scrim {
      background: linear-gradient(180deg, rgba(10,19,38,0.4) 0%, rgba(10,19,38,0.7) 60%, rgba(10,19,38,0.92) 100%);
    }

    .ring-divider .line {
      background: linear-gradient(90deg, transparent, var(--gold-500) 50%, transparent);
    }

    .section-alt {
      background: rgba(238, 243, 249, 0.80);
      backdrop-filter: blur(4px);
    }

    .final-cta {
      background: linear-gradient(180deg, rgba(238, 243, 249, 0.88), rgba(247, 248, 250, 0.92));
      backdrop-filter: blur(4px);
    }

    footer {
      background: rgba(10, 31, 61, 0.94);
      backdrop-filter: blur(6px);
    }

    /* ---------- existing styles (unchanged) ---------- */
    h1, h2, h3, .display {
      font-family: 'Fraunces', serif;
      letter-spacing: -0.01em;
    }
    .mono {
      font-family: 'IBM Plex Mono', monospace;
      letter-spacing: 0.06em;
    }
    a { color: inherit; text-decoration: none; }
    img { max-width: 100%; display: block; }
    .wrap { max-width: 1360px; margin: 0 auto; padding: 0 32px; }

    .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; }
    .reveal.in { opacity: 1; transform: translateY(0); }
    @media (prefers-reduced-motion: reduce) {
      .reveal { opacity: 1; transform: none; transition: none; }
      html { scroll-behavior: auto; }
    }

    header {
      position: sticky; top: 0; z-index: 60;
    }
    .header-inner {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 32px; max-width: 1360px; margin: 0 auto;
    }
    .brand { display: flex; align-items: center; gap: 13px; min-width: 0; }
    .brand img {
      width: 46px; height: 46px; border-radius: 50%;
      box-shadow: 0 0 0 3px #fff, 0 0 0 4px var(--gold-500);
      flex-shrink: 0;
      background: white;
    }
    .brand-text { line-height: 1.2; min-width: 0; }
    .brand-text .eyebrow {
      font-size: 10.5px; font-weight: 600; color: var(--blue-600);
      text-transform: uppercase; letter-spacing: 0.09em;
    }
    .brand-text h1 {
      font-size: 16.5px; font-weight: 600; margin: 2px 0 0; color: var(--navy-950);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    nav.primary-nav { display: flex; align-items: center; gap: 30px; }
    nav.primary-nav a {
      font-size: 14px; font-weight: 500; color: var(--ink-600);
      position: relative; padding: 4px 0;
    }
    nav.primary-nav a:hover { color: var(--blue-700); }
    nav.primary-nav a::after {
      content: ''; position: absolute; left: 0; bottom: -2px; width: 0; height: 2px;
      background: var(--gold-500); transition: width .25s ease;
    }
    nav.primary-nav a:hover::after { width: 100%; }
    .header-right { display: flex; align-items: center; gap: 14px; }
    .btn-login {
      background: var(--blue-700); color: #fff; padding: 9px 20px;
      border-radius: 8px; font-weight: 600; font-size: 13.5px;
      box-shadow: 0 2px 10px rgba(30,77,140,0.25);
      transition: background .2s ease, transform .2s ease;
      white-space: nowrap;
    }
    .btn-login:hover { background: var(--navy-950); transform: translateY(-1px); }
    .btn-login i { margin-right: 7px; }
    .nav-toggle {
      display: none; background: none; border: 1px solid var(--line); border-radius: 8px;
      width: 38px; height: 38px; font-size: 16px; color: var(--navy-950); cursor: pointer;
    }
    .mobile-nav {
      display: none; flex-direction: column; gap: 2px;
      max-height: 0; overflow: hidden; transition: max-height .3s ease;
      border-top: 1px solid var(--line); background: rgba(255,255,255,0.96);
      backdrop-filter: blur(8px);
    }
    .mobile-nav.open { max-height: 260px; }
    .mobile-nav a { padding: 13px 32px; font-size: 14.5px; font-weight: 500; color: var(--ink-600); border-bottom: 1px solid var(--line); }
    .mobile-nav a:hover { color: var(--blue-700); background: var(--paper-alt); }

    .hero {
      position: relative; overflow: hidden;
      color: #fff;
    }
    .hero-illustration { position: absolute; inset: 0; opacity: 0.5; }
    .hero-scrim {
      position: absolute; inset: 0;
      background: linear-gradient(180deg, rgba(10,19,38,0.5) 0%, rgba(10,19,38,0.75) 60%, rgba(10,19,38,0.95) 100%);
    }
    .hero-inner {
      position: relative; z-index: 2;
      max-width: 820px; margin: 0 auto; padding: 96px 32px 92px;
      text-align: center;
    }
    .hero .eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 11.5px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
      color: var(--gold-400); margin-bottom: 18px;
    }
    .hero .eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold-500); }
    .hero h2 {
      font-size: clamp(30px, 4.6vw, 48px); font-weight: 600; line-height: 1.14;
      margin: 0 0 18px; color: #fff;
    }
    .hero p.lede {
      font-size: 17px; line-height: 1.65; color: #CBD8E8; max-width: 600px; margin: 0 auto 30px;
    }
    .btn-hero {
      display: inline-flex; align-items: center; gap: 10px;
      background: var(--gold-500); color: var(--navy-950); font-weight: 700;
      padding: 14px 30px; border-radius: 9px; font-size: 15px;
      box-shadow: 0 8px 24px rgba(201,162,39,0.35);
      transition: background .2s ease, transform .2s ease;
    }
    .btn-hero:hover { background: var(--gold-400); transform: translateY(-2px); }

    .ring-divider {
      display: flex; align-items: center; justify-content: center; gap: 14px;
      padding: 22px 0; color: var(--gold-500);
      background: rgba(255,255,255,0.5);
      backdrop-filter: blur(6px);
    }
    .ring-divider .line { height: 1px; width: 100%; max-width: 220px; background: linear-gradient(90deg, transparent, var(--gold-500) 50%, transparent); }
    .ring-divider i { font-size: 14px; }

    section { padding: 64px 0; }
    .section-alt { background: rgba(238, 243, 249, 0.80); backdrop-filter: blur(4px); }
    .section-head { margin-bottom: 32px; max-width: 680px; }
    .eyebrow-label {
      font-size: 11.5px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--blue-600); margin-bottom: 10px; display: block;
    }
    .section-title {
      font-size: clamp(24px,3vw,32px); font-weight: 600; color: var(--navy-950);
      margin: 0 0 12px; line-height: 1.22;
    }
    .section-lede { font-size: 15.5px; line-height: 1.7; color: var(--ink-600); max-width: 660px; }

    .about-grid {
      display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 56px; align-items: center;
    }
    .stat-card {
      background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);
      border: 1px solid var(--glass-border); border-radius: 12px;
      padding: 18px 18px; box-shadow: 0 6px 18px rgba(18,32,46,0.05);
    }
    .stat-card .num { font-family: 'IBM Plex Mono', monospace; font-size: 23px; font-weight: 600; color: var(--blue-700); }
    .stat-card .lab { font-size: 12.5px; color: var(--ink-600); margin-top: 5px; }
    .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

    .vm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; }
    .vm-card {
      background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);
      border-radius: 16px; padding: 32px 28px;
      border: 1px solid var(--glass-border); box-shadow: 0 10px 26px rgba(18,32,46,0.06);
      position: relative; overflow: hidden;
    }
    .vm-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
      background: linear-gradient(90deg, var(--blue-700), var(--gold-500));
    }
    .vm-icon {
      width: 48px; height: 48px; border-radius: 50%;
      background: var(--paper-alt); color: var(--blue-700);
      display: flex; align-items: center; justify-content: center; font-size: 19px;
      margin-bottom: 16px;
    }
    .vm-card h3 { font-size: 19px; font-weight: 600; color: var(--navy-950); margin: 0 0 11px; }
    .vm-card p { font-size: 14.5px; line-height: 1.72; color: var(--ink-600); margin: 0; }

    .sp-section {
      background: rgba(10,31,61,0.92); backdrop-filter: blur(6px);
      color: #fff; border-radius: 22px;
      padding: 48px 56px; position: relative; overflow: hidden;
      display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;
      border: 1px solid rgba(255,255,255,0.08);
    }
    .sp-section::after {
      content: ''; position: absolute; right: -60px; top: -60px; width: 240px; height: 240px;
      border-radius: 50%; border: 1px solid rgba(201,162,39,0.25);
    }
    .sp-section .eyebrow-label { color: var(--gold-400); }
    .sp-section h2 { color: #fff; }
    .sp-section p { color: #B9C6D8; font-size: 15.5px; line-height: 1.75; margin: 0; }
    .sp-quote {
      padding-left: 20px; border-left: 3px solid var(--gold-500);
      font-family: 'Fraunces', serif; font-size: 18px; font-style: italic; color: var(--gold-100);
    }

    .values-grid { display: grid; grid-template-columns: repeat(6,1fr); gap: 16px; }
    .value-card {
      background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);
      border: 1px solid var(--glass-border); border-radius: 14px; padding: 22px 18px;
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .value-card:hover { transform: translateY(-4px); box-shadow: 0 14px 28px rgba(18,32,46,0.09); }
    .value-card i { font-size: 20px; color: var(--gold-500); margin-bottom: 12px; }
    .value-card h3 { font-size: 14.5px; font-weight: 600; margin: 0 0 6px; color: var(--navy-950); }
    .value-card p { font-size: 12.5px; line-height: 1.55; color: var(--ink-600); margin: 0; }

    .highlights-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
    .highlight-card {
      background: rgba(255,255,255,0.92); backdrop-filter: blur(4px);
      border: 1px solid var(--glass-border); border-radius: 12px; padding: 20px 18px; text-align: left;
    }
    .highlight-card .hi-icon { color: var(--blue-700); font-size: 16px; margin-bottom: 10px; }
    .highlight-card .hi-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ink-600); font-weight: 600; }
    .highlight-card .hi-value { font-family: 'Fraunces', serif; font-size: 17px; font-weight: 600; color: var(--navy-950); margin-top: 5px; }

    .final-cta {
      text-align: center; background: linear-gradient(180deg, rgba(238,243,249,0.88), rgba(247,248,250,0.92));
      backdrop-filter: blur(4px);
      padding: 56px 0;
    }
    .final-cta h2 { margin-bottom: 10px; }
    .final-cta p { margin: 0 auto 24px; }

    footer {
      background: rgba(10,31,61,0.94); backdrop-filter: blur(6px);
      color: #B9C6D8; padding: 48px 0 22px;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 44px; margin-bottom: 36px; }
    .footer-brand { display: flex; gap: 13px; align-items: center; margin-bottom: 14px; }
    .footer-brand img { width: 44px; height: 44px; border-radius: 50%; box-shadow: 0 0 0 2px rgba(255,255,255,0.5), 0 0 0 3px var(--gold-500); background: white; }
    .footer-brand h3 { color: #fff; font-size: 15.5px; margin: 0; font-weight: 600; }
    .footer-brand span { font-size: 11.5px; color: var(--gold-400); text-transform: uppercase; letter-spacing: 0.08em; }
    footer h4 { color: #fff; font-size: 12.5px; text-transform: uppercase; letter-spacing: 0.09em; margin: 0 0 14px; }
    footer p, footer li { font-size: 13.5px; line-height: 1.8; color: #B9C6D8; }
    footer ul { list-style: none; padding: 0; margin: 0; }
    footer a:hover { color: var(--gold-400); }
    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;
      display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;
      font-size: 12px; color: #7C8CA3;
    }

    @media (max-width:1100px) {
      .values-grid { grid-template-columns: repeat(3,1fr); }
    }
    @media (max-width:960px) {
      nav.primary-nav { display: none; }
      .nav-toggle { display: block; }
      .mobile-nav { display: flex; }
      .about-grid { grid-template-columns: 1fr; gap: 36px; }
      .vm-grid { grid-template-columns: 1fr; }
      .sp-section { grid-template-columns: 1fr; padding: 40px 30px; }
      .highlights-grid { grid-template-columns: repeat(2,1fr); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width:600px) {
      .header-inner { padding: 10px 18px; }
      .brand-text h1 { font-size: 14px; }
      .hero-inner { padding: 72px 20px 64px; }
      section { padding: 52px 0; }
      .wrap { padding: 0 20px; }
      .values-grid { grid-template-columns: repeat(2,1fr); }
      .highlights-grid { grid-template-columns: 1fr 1fr; }
      .footer-grid { grid-template-columns: 1fr; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
      body {
        background-size: 240px 240px;
        background-position: center 80px;
      }
    }
  </style>
</head>
<body>

<!-- ================= HEADER ================= -->
<header>
  <div class="header-inner">
    <div class="brand">
      <img src="City.jpg" alt="Official Seal of the City of San Jose del Monte">
      <div class="brand-text">
        <span class="eyebrow">City Government of San Jose del Monte</span>
        <h1>Sangguniang Panlungsod</h1>
      </div>
    </div>
    <nav class="primary-nav">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#vision-mission">Vision &amp; Mission</a>
      <a href="#contact">Contact</a>
    </nav>
    <div class="header-right">
      <a href="login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i>Login</a>
      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
  <nav class="mobile-nav" id="mobileNav">
    <a href="#home">Home</a>
    <a href="#about">About</a>
    <a href="#vision-mission">Vision &amp; Mission</a>
    <a href="#contact">Contact</a>
  </nav>
</header>

<!-- ================= HERO ================= -->
<section class="hero" id="home">
  <div class="hero-illustration">
    <svg viewBox="0 0 1440 640" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
      <defs>
        <linearGradient id="skyG" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1E4D8C"/>
          <stop offset="100%" stop-color="#0A1F3D"/>
        </linearGradient>
      </defs>
      <rect width="1440" height="640" fill="url(#skyG)"/>
      <g transform="translate(720,140)" opacity="0.9">
        <circle r="10" fill="#DDB94A"/>
        <g stroke="#DDB94A" stroke-width="3">
          <line x1="0" y1="-70" x2="0" y2="-30"/>
          <line x1="0" y1="70" x2="0" y2="30"/>
          <line x1="-70" y1="0" x2="-30" y2="0"/>
          <line x1="70" y1="0" x2="30" y2="0"/>
          <line x1="-50" y1="-50" x2="-22" y2="-22"/>
          <line x1="50" y1="50" x2="22" y2="22"/>
          <line x1="-50" y1="50" x2="-22" y2="22"/>
          <line x1="50" y1="-50" x2="22" y2="-22"/>
        </g>
      </g>
      <path d="M0,420 L220,300 L420,420 Z" fill="#1E7A56" opacity="0.55"/>
      <path d="M950,420 L1180,290 L1440,420 Z" fill="#1E7A56" opacity="0.55"/>
      <g fill="#DDE8F7" opacity="0.85">
        <rect x="470" y="330" width="60" height="120"/>
        <rect x="540" y="290" width="70" height="160"/>
        <rect x="620" y="250" width="80" height="200"/>
        <rect x="710" y="300" width="60" height="150"/>
        <rect x="780" y="270" width="75" height="180"/>
        <rect x="865" y="320" width="55" height="130"/>
      </g>
      <rect x="0" y="450" width="1440" height="190" fill="#0A1F3D"/>
    </svg>
  </div>
  <div class="hero-scrim"></div>
  <div class="hero-inner">
    <span class="eyebrow"><span class="dot"></span>Legislative System Portal</span>
    <h2>Welcome to the City of San Jose del Monte Legislative System</h2>
    <p class="lede">Supporting transparent, efficient, and evidence-based local governance through digital innovation.</p>
    <a href="login.php" class="btn-hero"><i class="fa-solid fa-right-to-bracket"></i>Login to the Portal</a>
  </div>
</section>

<div class="ring-divider"><div class="line"></div><i class="fa-solid fa-star"></i><div class="line"></div></div>

<!-- ================= ABOUT ================= -->
<section id="about">
  <div class="wrap about-grid reveal">
    <div>
      <span class="eyebrow-label">About the City</span>
      <h2 class="section-title">A fast-growing gateway city built on public trust</h2>
      <p class="section-lede">
        San Jose del Monte is a component city of Bulacan in Region III &mdash; Central Luzon, and stands
        among the fastest-growing urban centers in the region. Serving as a vital gateway between
        Metro Manila and Central Luzon, the city continues to expand its infrastructure, economy,
        and public institutions to keep pace with a growing population.
      </p>
      <p class="section-lede" style="margin-top:14px;">
        The City Government is committed to transparent governance, sustainable development, and
        quality public service &mdash; principles that guide every ordinance, resolution, and policy
        that passes through this portal.
      </p>
    </div>
    <div class="stat-grid">
      <div class="stat-card"><div class="num">1752</div><div class="lab">Founded</div></div>
      <div class="stat-card"><div class="num">2000</div><div class="lab">Cityhood</div></div>
      <div class="stat-card"><div class="num">Bulacan</div><div class="lab">Province</div></div>
      <div class="stat-card"><div class="num">Region III</div><div class="lab">Central Luzon</div></div>
    </div>
  </div>
</section>

<!-- ================= VISION & MISSION ================= -->
<section id="vision-mission" class="section-alt">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow-label">Our Direction</span>
      <h2 class="section-title">Vision &amp; Mission</h2>
    </div>
    <div class="vm-grid reveal">
      <div class="vm-card">
        <div class="vm-icon"><i class="fa-solid fa-eye"></i></div>
        <h3>Vision</h3>
        <p>
          A progressive, resilient, and people-centered San Jose del Monte &mdash; a model component
          city where inclusive governance, sustainable growth, and empowered citizens shape a
          thriving future for all.
        </p>
      </div>
      <div class="vm-card">
        <div class="vm-icon"><i class="fa-solid fa-flag"></i></div>
        <h3>Mission</h3>
        <p>
          To deliver transparent, efficient, and evidence-based governance through sound legislation,
          responsive public service, and the active participation of every constituent in shaping
          local policy.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ================= ABOUT SANGGUNIANG PANLUNGSOD ================= -->
<section>
  <div class="wrap reveal">
    <div class="sp-section">
      <div>
        <span class="eyebrow-label">Legislative Body</span>
        <h2 class="section-title">About the Sangguniang Panlungsod</h2>
        <p>
          The Sangguniang Panlungsod is the legislative body of the City of San Jose del Monte,
          responsible for enacting ordinances, approving resolutions, and formulating local policies
          that promote the general welfare of its constituents. Through careful research, deliberation,
          and public consultation, the Sanggunian works to translate community needs into sound,
          enforceable local law.
        </p>
      </div>
      <p class="sp-quote">
        Good governance begins with informed legislation &mdash; and informed legislation begins
        with accessible research.
      </p>
    </div>
  </div>
</section>

<!-- ================= CORE VALUES ================= -->
<section class="section-alt">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow-label">What Guides Us</span>
      <h2 class="section-title">Core Values</h2>
    </div>
    <div class="values-grid reveal">
      <div class="value-card"><i class="fa-solid fa-shield-halved"></i><h3>Integrity</h3><p>Honesty and ethical conduct in every legislative action.</p></div>
      <div class="value-card"><i class="fa-solid fa-magnifying-glass"></i><h3>Transparency</h3><p>Open, accessible legislative processes for the public.</p></div>
      <div class="value-card"><i class="fa-solid fa-scale-balanced"></i><h3>Accountability</h3><p>Owning every decision and its impact on the community.</p></div>
      <div class="value-card"><i class="fa-solid fa-star"></i><h3>Excellence</h3><p>The highest standard in research, policy, and service.</p></div>
      <div class="value-card"><i class="fa-solid fa-hand-holding-heart"></i><h3>Public Service</h3><p>Constituents' welfare at the center of every ordinance.</p></div>
      <div class="value-card"><i class="fa-solid fa-lightbulb"></i><h3>Innovation</h3><p>Digital tools that modernize legislative work.</p></div>
    </div>
  </div>
</section>

<!-- ================= CITY HIGHLIGHTS ================= -->
<section>
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow-label">At a Glance</span>
      <h2 class="section-title">City Highlights</h2>
    </div>
    <div class="highlights-grid reveal">
      <div class="highlight-card"><div class="hi-icon"><i class="fa-solid fa-map-location-dot"></i></div><div class="hi-label">Province</div><div class="hi-value">Bulacan</div></div>
      <div class="highlight-card"><div class="hi-icon"><i class="fa-solid fa-compass"></i></div><div class="hi-label">Region</div><div class="hi-value">Central Luzon (III)</div></div>
      <div class="highlight-card"><div class="hi-icon"><i class="fa-solid fa-city"></i></div><div class="hi-label">Classification</div><div class="hi-value">Component City</div></div>
      <div class="highlight-card"><div class="hi-icon"><i class="fa-solid fa-arrow-trend-up"></i></div><div class="hi-label">Known For</div><div class="hi-value">Gateway to Metro Manila</div></div>
    </div>
  </div>
</section>

<!-- ================= FINAL CTA ================= -->
<section class="final-cta">
  <div class="wrap reveal" style="max-width:560px; margin:0 auto;">
    <h2 class="section-title">Ready to access the portal?</h2>
    <p class="section-lede" style="margin:0 auto 24px;">Authorized members of the Sangguniang Panlungsod can sign in to manage legislative research, policies, and reports.</p>
    <a href="login.php" class="btn-hero" style="background:var(--blue-700); color:#fff; box-shadow:0 8px 24px rgba(30,77,140,0.3);"><i class="fa-solid fa-right-to-bracket"></i>Login to the Portal</a>
  </div>
</section>

<!-- ================= FOOTER ================= -->
<footer id="contact">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <img src="City.jpg" alt="City of San Jose del Monte Seal">
          <div>
            <h3>Sangguniang Panlungsod</h3>
            <span>City Government of San Jose del Monte</span>
          </div>
        </div>
        <p>Legislative Research, Policy Analysis &amp; Impact Evaluation System &mdash; supporting
          transparent and evidence-based local governance.</p>
      </div>
      <div>
        <h4>Office</h4>
        <ul>
          <li><i class="fa-solid fa-location-dot" style="margin-right:8px;"></i>City Hall Complex, City of San Jose del Monte, Bulacan, Philippines</li>
          <li style="margin-top:10px;"><i class="fa-solid fa-phone" style="margin-right:8px;"></i>(044) 815-0000</li>
          <li style="margin-top:6px;"><i class="fa-solid fa-envelope" style="margin-right:8px;"></i>sanggunian@sanjosedelmonte.gov.ph</li>
        </ul>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li style="margin-top:10px;"><a href="#about">About</a></li>
          <li style="margin-top:10px;"><a href="#vision-mission">Vision &amp; Mission</a></li>
          <li style="margin-top:10px;"><a href="login.php">Login to Portal</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; 2026 City Government of San Jose del Monte &mdash; Sangguniang Panlungsod. All rights reserved.</span>
      <span>Legislative Research, Policy Analysis &amp; Impact Evaluation System</span>
    </div>
  </div>
</footer>

<script>
  // Scroll reveal animations
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      }
    });
  }, {threshold:0.12});
  revealEls.forEach(el=>io.observe(el));

  // Mobile menu toggle
  const navToggle = document.getElementById('navToggle');
  const mobileNav = document.getElementById('mobileNav');
  navToggle.addEventListener('click', ()=>{
    const isOpen = mobileNav.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', isOpen);
    navToggle.innerHTML = isOpen ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
  });
  mobileNav.querySelectorAll('a').forEach(a=>{
    a.addEventListener('click', ()=>{
      mobileNav.classList.remove('open');
      navToggle.setAttribute('aria-expanded', false);
      navToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
    });
  });
</script>

</body>
</html>