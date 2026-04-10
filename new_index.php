<?php
// new_index.php - JAJR Attendance System Landing Page
include __DIR__ . '/conn/db_connection.php';
session_start();

// If user already logged in, redirect to employee dashboard
if (!empty($_SESSION['employee_id'])) {
    header('Location: employee/dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>JAJR Attendance System — Biometric & Geo-Fenced Workforce Security</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/img/profile/jajr-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ===== HERO SECTION STYLES ===== */
        .hero-attendance {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Construction Background with Overlay */
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.92) 0%, rgba(30, 58, 95, 0.75) 50%, rgba(30, 58, 95, 0.60) 100%);
        }

        /* Navigation */
        .nav-attendance {
            position: relative;
            z-index: 50;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-link-attendance {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link-attendance:hover {
            color: #FF6B00;
        }

        .nav-link-attendance::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #FF6B00;
            transition: width 0.3s ease;
        }

        .nav-link-attendance:hover::after {
            width: 100%;
        }

        /* Hero Content */
        .hero-content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 107, 0, 0.15);
            border: 1px solid rgba(255, 107, 0, 0.3);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: #FF8C00;
            text-transform: uppercase;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: #FF6B00;
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        .hero-label {
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 900;
            line-height: 1.1;
            color: white;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            max-width: 600px;
        }

        /* Orange CTA Buttons */
        .btn-orange-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #FF6B00 0%, #FF8C00 100%);
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(255, 107, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .btn-orange-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 107, 0, 0.5);
        }

        .btn-orange-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 32px;
            background: transparent;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .btn-orange-outline:hover {
            border-color: #FF6B00;
            color: #FF6B00;
            background: rgba(255, 107, 0, 0.1);
        }

        /* Play Button Circle */
        .play-button-container {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .play-button {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #FF6B00 0%, #FF8C00 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
            box-shadow: 0 0 30px rgba(255, 107, 0, 0.6);
        }

        .play-button:hover {
            transform: scale(1.1);
        }

        .play-button-glow {
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            background: rgba(255, 107, 0, 0.3);
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Feature Pills */
        .feature-module {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 32px;
        }

        .feature-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 107, 0, 0.4);
            border-radius: 999px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .feature-pill:hover {
            background: rgba(255, 107, 0, 0.15);
            border-color: #FF6B00;
            transform: translateY(-2px);
        }

        .feature-pill-icon {
            width: 18px;
            height: 18px;
            color: #FF6B00;
        }

        /* Trust Indicator */
        .trust-indicator {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .trust-avatars {
            display: flex;
        }

        .trust-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border: 2px solid white;
            margin-left: -8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: white;
        }

        .trust-avatar:first-child {
            margin-left: 0;
        }

        .trust-text {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .trust-text strong {
            color: white;
        }

        /* Bottom Marquee Bar */
        .marquee-bar {
            position: relative;
            z-index: 20;
            background: white;
            padding: 16px 0;
            overflow: hidden;
            border-top: 3px solid #FF6B00;
        }

        .marquee-content {
            display: flex;
            animation: marquee 20s linear infinite;
            white-space: nowrap;
        }

        .marquee-item {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            padding: 0 32px;
            font-size: 0.875rem;
            font-weight: 700;
            color: #FF6B00;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .marquee-separator {
            color: #ccc;
        }

        /* Animations */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 0.2; }
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.8s ease-out forwards;
        }

        .delay-100 { animation-delay: 0.1s; opacity: 0; }
        .delay-200 { animation-delay: 0.2s; opacity: 0; }
        .delay-300 { animation-delay: 0.3s; opacity: 0; }
        .delay-400 { animation-delay: 0.4s; opacity: 0; }

        /* Right Side Visual */
        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .construction-badge {
            position: absolute;
            top: 20%;
            right: 10%;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: float-badge 4s ease-in-out infinite;
        }

        @keyframes float-badge {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .badge-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-text {
            font-size: 0.8rem;
        }

        .badge-text strong {
            display: block;
            color: #1e3a5f;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-visual {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .btn-orange-primary,
            .btn-orange-outline {
                width: 100%;
                justify-content: center;
            }
            
            .feature-module {
                justify-content: center;
            }
            
            .trust-indicator {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">

    <!-- Navigation -->
    <nav class="nav-attendance fixed top-0 left-0 right-0">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-white font-bold text-lg tracking-tight">JAJR</span>
                        <span class="hidden sm:inline text-orange-400 font-semibold text-sm ml-2">ATTENDANCE SYSTEM</span>
                    </div>
                </div>

                <!-- Nav Links -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="nav-link-attendance text-sm">Features</a>
                    <a href="#solutions" class="nav-link-attendance text-sm">Solutions</a>
                    <a href="#pricing" class="nav-link-attendance text-sm">Pricing</a>
                    <a href="login.php" class="nav-link-attendance text-sm">Log In</a>
                    <a href="" class="btn-orange-primary text-sm py-2 px-5">Get Started</a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden text-white p-2" id="mobileMenuBtn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-attendance">
        <!-- Background -->
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>

        <!-- Content -->
        <div class="hero-content pt-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="space-y-6">
                        <!-- Badge -->
                        <div class="hero-badge animate-fade-in-up delay-100">
                            <span class="hero-badge-dot"></span>
                            <span>Now with AI-Powered Verification</span>
                        </div>

                        <!-- Label -->
                        <p class="hero-label animate-fade-in-up delay-200">
                            Biometric & Geo-Fenced Attendance
                        </p>

                        <!-- Title -->
                        <h1 class="hero-title animate-fade-in-up delay-300">
                            SECURE YOUR<br>WORKFORCE.
                        </h1>

                        <!-- Subtitle -->
                        <p class="hero-subtitle animate-fade-in-up delay-400">
                            Eliminate buddy punching and ensure accurate timekeeping for your field crew with real-time biometric validation and precise GPS site boundaries.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap gap-4 mt-8 animate-fade-in-up delay-400">
                            <a href="signup.php" class="btn-orange-primary">
                                <span>START FREE TRIAL</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                            <button class="btn-orange-outline" onclick="openDemoModal()">
                                <div class="play-button-container" style="width: 24px; height: 24px; margin-right: 4px;">
                                    <div class="play-button" style="width: 24px; height: 24px;">
                                        <svg class="w-3 h-3 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <span>WATCH DEMO</span>
                            </button>
                        </div>

                        <!-- Feature Pills -->
                        <div class="feature-module animate-fade-in-up delay-400">
                            <div class="feature-pill">
                                <svg class="feature-pill-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.131A8 8 0 008 2.469c-3.08 2.384-4.88 6.164-4.99 10.131c.01.306.065.604.163.891"/>
                                </svg>
                                <span>Biometric Face ID</span>
                            </div>
                            <div class="feature-pill">
                                <svg class="feature-pill-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>GPS Geofencing</span>
                            </div>
                            <div class="feature-pill">
                                <svg class="feature-pill-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Real-Time Sync</span>
                            </div>
                        </div>

                        <!-- Trust Indicator -->
                        <div class="trust-indicator animate-fade-in-up delay-400">
                            <div class="trust-avatars">
                                <div class="trust-avatar">JD</div>
                                <div class="trust-avatar">MK</div>
                                <div class="trust-avatar">SL</div>
                                <div class="trust-avatar" style="background: linear-gradient(135deg, #1e3a5f, #2d5a8f);">+</div>
                            </div>
                            <p class="trust-text">
                                <strong>Trusted by 200+</strong> construction companies
                            </p>
                        </div>
                    </div>

                    <!-- Right Visual -->
                    <div class="hero-visual hidden lg:block">
                        <!-- Play Button Circle -->
                        <div class="relative">
                            <div class="play-button-glow"></div>
                            <button class="play-button" onclick="openDemoModal()">
                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                            <p class="text-white/60 text-sm mt-4 text-center font-medium">Watch Demo</p>
                        </div>

                        <!-- Floating Badge -->
                        <div class="construction-badge">
                            <div class="badge-icon">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="badge-text">
                                <span class="text-gray-500">Verified Clock-in</span>
                                <strong>Site Alpha</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Marquee Bar -->
        <div class="marquee-bar">
            <div class="marquee-content">
                <span class="marquee-item">
                    JAJR ATTENDANCE SYSTEM
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    BIOMETRIC LOGS
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    GEO-FENCING SITE BOUNDARIES
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    REAL-TIME PAYROLL SYNC
                    <span class="marquee-separator">//</span>
                </span>
                <!-- Duplicate for seamless loop -->
                <span class="marquee-item">
                    JAJR ATTENDANCE SYSTEM
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    BIOMETRIC LOGS
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    GEO-FENCING SITE BOUNDARIES
                    <span class="marquee-separator">//</span>
                </span>
                <span class="marquee-item">
                    REAL-TIME PAYROLL SYNC
                    <span class="marquee-separator">//</span>
                </span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute top-0 right-0 w-1/3 h-full bg-gradient-to-l from-orange-50 to-transparent opacity-50"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-orange-100 rounded-full blur-3xl opacity-30"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 text-orange-600 text-sm font-semibold mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Powerful Features
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Everything You Need to<br><span class="text-orange-500">Manage Your Workforce</span>
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Comprehensive tools designed specifically for construction and field service teams
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Biometric Face Recognition -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-orange-500 to-orange-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Biometric Face Recognition</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Eliminate buddy punching with AI-powered facial verification. Workers clock in with a quick face scan.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            99.9% accuracy rate
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Works offline
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Spoof detection enabled
                        </li>
                    </ul>
                </div>

                <!-- Feature 2: GPS Geofencing -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-blue-500 to-blue-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">GPS Geofencing</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Define precise site boundaries. Workers can only clock in when physically present at authorized locations.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Custom radius per site
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Multiple site support
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Real-time location tracking
                        </li>
                    </ul>
                </div>

                <!-- Feature 3: Real-Time Sync -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-green-500 to-green-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Real-Time Payroll Sync</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Attendance data instantly syncs to payroll. No more manual timesheet entry or calculation errors.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Automatic overtime calc
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Export to any payroll system
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Daily/weekly reports
                        </li>
                    </ul>
                </div>

                <!-- Feature 4: Mobile App -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-purple-500 to-purple-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Mobile-First Design</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Workers clock in from their phones. No special hardware required. Works on iOS and Android.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            QR code scanning
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Offline mode support
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Push notifications
                        </li>
                    </ul>
                </div>

                <!-- Feature 5: Admin Dashboard -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-indigo-500 to-indigo-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Powerful Admin Dashboard</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Complete visibility into your workforce. Track attendance, generate reports, and manage sites from one place.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Live attendance view
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Advanced analytics
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Custom reports
                        </li>
                    </ul>
                </div>

                <!-- Feature 6: Notifications -->
                <div class="feature-card group">
                    <div class="feature-icon-wrapper bg-gradient-to-br from-pink-500 to-pink-600">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Notifications</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Stay informed with automated alerts. Get notified for late arrivals, no-shows, and overtime requests.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Email & SMS alerts
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Custom alert rules
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Manager approvals
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom CTA -->
            <div class="text-center mt-16">
                <a href="signup.php" class="btn-orange-primary text-lg px-10 py-4 inline-flex">
                    <span>Start Your Free Trial</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <p class="text-gray-500 mt-4 text-sm">No credit card required. 14-day free trial.</p>
            </div>
        </div>

        <!-- CSS for Feature Cards -->
        <style>
            .feature-card {
                background: white;
                border-radius: 16px;
                padding: 2rem;
                border: 1px solid #e5e7eb;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .feature-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #FF6B00, #FF8C00);
                transform: scaleX(0);
                transform-origin: left;
                transition: transform 0.3s ease;
            }

            .feature-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                border-color: #FF6B00;
            }

            .feature-card:hover::before {
                transform: scaleX(1);
            }

            .feature-icon-wrapper {
                width: 60px;
                height: 60px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 1.5rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            }
        </style>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-24 bg-gray-900 relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-500 via-orange-400 to-orange-500"></div>
        <div class="absolute top-1/4 right-0 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 left-0 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-500/20 text-orange-400 text-sm font-semibold mb-4 border border-orange-500/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Customer Success Stories
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Trusted by Construction<br><span class="text-orange-500">Leaders Worldwide</span>
                </h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    See how industry leaders are transforming their workforce management with JAJR
                </p>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-20">
                <div class="text-center p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-4xl md:text-5xl font-bold text-orange-500 mb-2">200+</div>
                    <div class="text-gray-400 text-sm">Companies Trust Us</div>
                </div>
                <div class="text-center p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-4xl md:text-5xl font-bold text-orange-500 mb-2">50K+</div>
                    <div class="text-gray-400 text-sm">Workers Tracked Daily</div>
                </div>
                <div class="text-center p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-4xl md:text-5xl font-bold text-orange-500 mb-2">99.9%</div>
                    <div class="text-gray-400 text-sm">Uptime Guaranteed</div>
                </div>
                <div class="text-center p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="text-4xl md:text-5xl font-bold text-orange-500 mb-2">15M+</div>
                    <div class="text-gray-400 text-sm">Clock-ins Recorded</div>
                </div>
            </div>

            <!-- Testimonials Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <blockquote class="text-gray-300 text-lg leading-relaxed mb-6">
                        "JAJR eliminated buddy punching completely. We saved over 40 hours per month on payroll processing and caught $12K in fraudulent overtime in the first quarter alone."
                    </blockquote>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-lg">
                            MR
                        </div>
                        <div>
                            <div class="text-white font-semibold">Marco Rodriguez</div>
                            <div class="text-gray-500 text-sm">Operations Director, BuildRight Construction</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <blockquote class="text-gray-300 text-lg leading-relaxed mb-6">
                        "The geofencing feature is a game-changer. Our site supervisors can see exactly who's on location in real-time. We've reduced unauthorized clock-ins by 95%."
                    </blockquote>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                            SL
                        </div>
                        <div>
                            <div class="text-white font-semibold">Sarah Lim</div>
                            <div class="text-gray-500 text-sm">Project Manager, Metro Infrastructure</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="testimonial-card">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <blockquote class="text-gray-300 text-lg leading-relaxed mb-6">
                        "Implementation was seamless. The mobile app is intuitive—our workers picked it up immediately. Support team is incredibly responsive. Best investment we've made."
                    </blockquote>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold text-lg">
                            JC
                        </div>
                        <div>
                            <div class="text-white font-semibold">James Chen</div>
                            <div class="text-gray-500 text-sm">CEO, Pacific Builders Group</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Logos -->
            <div class="mt-20 pt-12 border-t border-white/10">
                <p class="text-center text-gray-500 text-sm uppercase tracking-wider mb-8">Trusted by industry leaders</p>
                <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 opacity-50">
                    <div class="flex items-center gap-2 text-gray-400 font-semibold text-lg">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                        BuildCorp
                    </div>
                    <div class="flex items-center gap-2 text-gray-400 font-semibold text-lg">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 9h-2V7h2v5zm0 4h-2v-2h2v2z"/>
                        </svg>
                        MetroWorks
                    </div>
                    <div class="flex items-center gap-2 text-gray-400 font-semibold text-lg">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/>
                        </svg>
                        CityPlan
                    </div>
                    <div class="flex items-center gap-2 text-gray-400 font-semibold text-lg">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                        </svg>
                        Structura
                    </div>
                    <div class="flex items-center gap-2 text-gray-400 font-semibold text-lg">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        GlobalBuild
                    </div>
                </div>
            </div>
        </div>

        <!-- CSS for Testimonials -->
        <style>
            .testimonial-card {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                padding: 2rem;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .testimonial-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #FF6B00, #FF8C00);
                transform: scaleX(0);
                transform-origin: left;
                transition: transform 0.3s ease;
            }

            .testimonial-card:hover {
                transform: translateY(-8px);
                border-color: rgba(255, 107, 0, 0.4);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }

            .testimonial-card:hover::before {
                transform: scaleX(1);
            }
        </style>
    </section>

    <!-- Team Members Section -->
    <section id="team" class="py-24 bg-gray-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16">
                <div>
                    <span class="inline-flex items-center gap-2 text-orange-500 text-sm font-semibold mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Expert Team Member
                    </span>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">
                        Our Best Construction Team<br>Members Explore.
                    </h2>
                </div>
                <a href="#" class="btn-orange-primary mt-6 md:mt-0 inline-flex">
                    <span>More Members</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>

            <!-- Team Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Team Member 1 -->
                <div class="team-card">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <div class="team-image-wrapper">
                            <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400&h=400&fit=crop" alt="Savannah Nguyen" class="team-image">
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Savannah Nguyen</h3>
                            <p class="text-orange-500 font-medium text-sm mb-3">Product Designer</p>
                            <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                Construction is a broad field that involves the including residential, commercial, industrial, and civil engineering works.
                            </p>
                            <div class="flex items-center gap-3">
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <div class="team-card">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <div class="team-image-wrapper">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop" alt="Ralph Edwards" class="team-image">
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Ralph Edwards</h3>
                            <p class="text-orange-500 font-medium text-sm mb-3">Product Designer</p>
                            <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                Construction is a broad field that involves the including residential, commercial, industrial, and civil engineering works.
                            </p>
                            <div class="flex items-center gap-3">
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Member 3 -->
                <div class="team-card">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <div class="team-image-wrapper">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop" alt="Wade Warren" class="team-image">
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Wade Warren</h3>
                            <p class="text-orange-500 font-medium text-sm mb-3">Product Designer</p>
                            <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                Construction is a broad field that involves the including residential, commercial, industrial, and civil engineering works.
                            </p>
                            <div class="flex items-center gap-3">
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Member 4 -->
                <div class="team-card">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <div class="team-image-wrapper">
                            <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&h=400&fit=crop" alt="Ronald Richards" class="team-image">
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1">Ronald Richards</h3>
                            <p class="text-orange-500 font-medium text-sm mb-3">Product Designer</p>
                            <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                Construction is a broad field that involves the including residential, commercial, industrial, and civil engineering works.
                            </p>
                            <div class="flex items-center gap-3">
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                                <a href="#" class="team-social-icon">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CSS for Team Section -->
        <style>
            .team-card {
                background: white;
                border-radius: 16px;
                padding: 1.5rem;
                border: 1px solid #e5e7eb;
                transition: all 0.3s ease;
            }

            .team-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                border-color: #FF6B00;
            }

            .team-image-wrapper {
                width: 120px;
                height: 140px;
                border-radius: 12px;
                overflow: hidden;
                flex-shrink: 0;
            }

            .team-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.3s ease;
            }

            .team-card:hover .team-image {
                transform: scale(1.05);
            }

            .team-social-icon {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                border: 1px solid #FF6B00;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #FF6B00;
                transition: all 0.3s ease;
            }

            .team-social-icon:hover {
                background: #FF6B00;
                color: white;
            }
        </style>
    </section>

    <!-- Demo Video Modal -->
    <div id="demoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="relative w-full max-w-4xl mx-4">
            <button onclick="closeDemoModal()" class="absolute -top-12 right-0 text-white hover:text-orange-400 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="bg-gray-900 rounded-xl overflow-hidden shadow-2xl">
                <div class="aspect-video bg-gray-800 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-20 h-20 rounded-full bg-orange-500/20 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <p class="text-gray-400">Demo video coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="bg-black relative overflow-hidden">
        <!-- Top Border Gradient -->
        <div class="h-1 bg-gradient-to-r from-orange-500 via-orange-400 to-orange-500"></div>

        <!-- Large Brand Text Background -->
        <div class="absolute top-8 left-0 right-0 overflow-hidden pointer-events-none select-none">
            <div class="text-[8vw] md:text-[6vw] font-black text-center tracking-tighter leading-none">
                <span class="text-white">JAJR</span><span class="text-orange-500">ATTENDANCE</span>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-56 pb-12 relative z-10">
            <!-- Main Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                <!-- Column 1: Newsletter -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-6">News Letters</h3>
                    <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                        Stay updated with the latest in workforce management technology. Get tips, industry insights, and product updates delivered to your inbox.
                    </p>
                    <p class="text-orange-500 text-sm font-medium mb-4">Subscribe For Our Newsletters</p>
                    <form class="relative" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                        <input
                            type="email"
                            placeholder="Enter Your Email"
                            class="w-full bg-transparent border border-gray-700 rounded-lg px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                            required
                        >
                        <button
                            type="submit"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center hover:bg-orange-600 transition-colors"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Column 2: Our Services -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-6">Our Services</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Biometric Face Recognition
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                GPS Geofencing Solutions
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Real-Time Payroll Sync
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Mobile Attendance App
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Advanced Analytics Dashboard
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Useful Links -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-6">Useful Links</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                About Us
                            </a>
                        </li>
                        <li>
                            <a href="#features" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Our Features
                            </a>
                        </li>
                        <li>
                            <a href="#testimonials" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Testimonials
                            </a>
                        </li>
                        <li>
                            <a href="#pricing" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Pricing Plans
                            </a>
                        </li>
                        <li>
                            <a href="login.php" class="text-gray-500 hover:text-orange-500 transition-colors text-sm flex items-center gap-2 group">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700 group-hover:bg-orange-500 transition-colors"></span>
                                Login / Sign Up
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Contact Us -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-6">Contact Us</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-gray-500 text-sm">
                                1234 Construction Ave,<br>Suite 500, Metro City,<br>Philippines 1200
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:+639325365015" class="text-gray-500 hover:text-orange-500 transition-colors text-sm">
                                +63 932 536 5015
                            </a>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <a href="mailto:jajrconstruction5@gmail.com" class="text-gray-500 hover:text-orange-500 transition-colors text-sm">
                                jajrconstruction5@gmail.com
                            </a>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="flex items-center gap-4 mt-6">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-orange-500 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-orange-500 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-orange-500 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-orange-500 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-600 text-sm">
                        Copyright & Design By <span class="text-orange-500">@JAJR</span> - 2026
                    </p>
                    <div class="flex items-center gap-6">
                        <a href="#" class="text-gray-600 hover:text-orange-500 transition-colors text-sm">FAQ</a>
                        <a href="#" class="text-gray-600 hover:text-orange-500 transition-colors text-sm">Careers</a>
                        <a href="#" class="text-gray-600 hover:text-orange-500 transition-colors text-sm">Privacy Policy</a>
                        <a href="#" class="text-gray-600 hover:text-orange-500 transition-colors text-sm">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Demo Modal Functions
        function openDemoModal() {
            document.getElementById('demoModal').classList.remove('hidden');
            document.getElementById('demoModal').classList.add('flex');
        }

        function closeDemoModal() {
            document.getElementById('demoModal').classList.add('hidden');
            document.getElementById('demoModal').classList.remove('flex');
        }

        // Close modal on backdrop click
        document.getElementById('demoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDemoModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDemoModal();
            }
        });
    </script>
</body>
</html>
