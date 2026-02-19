<?php
// index.php - public landing with optional DB-driven stats
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
		<title>JAJR Company — Engineering the Future</title>
		<script src="https://cdn.tailwindcss.com"></script>
		<link rel="stylesheet" href="assets/css/style.css">
		<link rel="icon" type="image/x-icon" href="assets/img/profile/jajr-logo.png">
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	</head>
	<body class="font-sans antialiased text-gray-100 bg-gray-900">
		<!-- Navbar -->
		<header class="sticky top-0 z-50 backdrop-blur-md bg-black/40 border-b border-white/5">
			<nav class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between h-16">
				<div class="flex items-center gap-4">
					<div class="text-lg font-bold tracking-tight flex items-center gap-2 logo-hover">
						<div class="w-9 h-9 rounded-md bg-gradient-to-br from-orange-400 to-black flex items-center justify-center floating">
							<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
							</svg>
						</div>
						<span class="hidden sm:inline">JAJR Company</span>
					</div>
					<ul class="hidden md:flex items-center gap-6 ml-6 text-gray-200">
						<li><a href="#about" class="nav-link">About Us</a></li>
						<li><a href="#services" class="nav-link">Services</a></li>
						<li><a href="#projects" class="nav-link">Projects</a></li>
					</ul>
				</div>
				<div class="flex items-center gap-3">
					<a href="login.php" class="btn-outline px-4 py-2 rounded-md border border-gray-600 text-gray-200 hover:text-white transition">Log In</a>
				</div>
			</nav>
		</header>

		<!-- Hero -->
		<main>
			<section class="hero-gradient text-white relative overflow-hidden min-h-screen flex flex-col">
				<!-- Animated background shapes -->
				<div class="absolute inset-0 overflow-hidden pointer-events-none">
					<div class="absolute top-20 left-10 w-72 h-72 bg-orange-500/20 rounded-full blur-3xl floating"></div>
					<div class="absolute bottom-20 right-10 w-96 h-96 bg-yellow-500/15 rounded-full blur-3xl floating-delayed"></div>
					<div class="absolute top-1/2 left-1/2 w-64 h-64 bg-orange-400/10 rounded-full blur-3xl floating" style="animation-delay: 1s;"></div>
				</div>
				
				<div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 lg:py-28 relative z-10 flex-grow flex flex-col justify-center">
					<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
						<div class="space-y-6">
							<div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 backdrop-blur-sm">
								<span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
								<span class="text-sm text-gray-300">Available for new projects</span>
							</div>
							<h1 class="hero-text text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight bg-gradient-to-r from-white via-orange-100 to-gray-300 bg-clip-text text-transparent">Engineering the Future with JAJR Company</h1>
							<p class="hero-text-delayed text-gray-100/90 max-w-xl text-lg">Providing innovative solutions and precision engineering for complex infrastructure. We transform ambitious visions into enduring reality.</p>
							<div class="hero-text-delayed flex flex-wrap gap-4 mt-4">
								<a href="login.php" class="btn-primary inline-block px-8 py-3 rounded-lg bg-gradient-to-r from-orange-400 to-orange-600 text-white font-semibold shadow-lg shadow-orange-500/30">Log In</a>
								<a href="#services" class="btn-outline inline-block px-8 py-3 rounded-lg border-2 border-white/30 text-white font-semibold hover:bg-white/10">Explore Services</a>
							</div>
							<div class="hero-text-delayed mt-6 text-sm text-gray-200/80 flex items-center gap-2">
								<svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
								</svg>
								Trusted by infrastructure leaders for safety, precision, and reliability.
							</div>
						</div>

						<div class="relative lg:pl-8">
							<!-- Enhanced Engineering SVG with animated gears -->
							<svg class="w-full h-64 lg:h-80 floating" viewBox="0 0 500 350" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden>
								<defs>
									<linearGradient id="gearGrad" x1="0" y1="0" x2="1" y2="1">
										<stop offset="0%" stop-color="#FFD66B" />
										<stop offset="100%" stop-color="#FF8C00" />
									</linearGradient>
								</defs>
								
								<!-- Large Main Gear (Left) -->
								<g transform="translate(100, 175)">
									<g class="gear">
										<circle cx="0" cy="0" r="55" stroke="url(#gearGrad)" stroke-width="3" fill="none" />
										<circle cx="0" cy="0" r="25" stroke="url(#gearGrad)" stroke-width="2" fill="none" opacity="0.6" />
										<circle cx="0" cy="0" r="8" fill="url(#gearGrad)" />
										<g stroke="url(#gearGrad)" stroke-width="4" stroke-linecap="round">
											<line x1="0" y1="-70" x2="0" y2="-55" />
											<line x1="0" y1="55" x2="0" y2="70" />
											<line x1="-70" y1="0" x2="-55" y2="0" />
											<line x1="55" y1="0" x2="70" y2="0" />
											<line x1="-49" y1="-49" x2="-39" y2="-39" />
											<line x1="39" y1="39" x2="49" y2="49" />
											<line x1="-49" y1="49" x2="-39" y2="39" />
											<line x1="39" y1="-39" x2="49" y2="-49" />
										</g>
									</g>
								</g>
								
								<!-- Medium Gear (Right, spinning opposite) -->
								<g transform="translate(320, 120)">
									<g class="gear-slow">
										<circle cx="0" cy="0" r="40" stroke="url(#gearGrad)" stroke-width="2.5" fill="none" />
										<circle cx="0" cy="0" r="18" stroke="url(#gearGrad)" stroke-width="1.5" fill="none" opacity="0.6" />
										<circle cx="0" cy="0" r="6" fill="url(#gearGrad)" />
										<g stroke="url(#gearGrad)" stroke-width="3" stroke-linecap="round">
											<line x1="0" y1="-52" x2="0" y2="-40" />
											<line x1="0" y1="40" x2="0" y2="52" />
											<line x1="-52" y1="0" x2="-40" y2="0" />
											<line x1="40" y1="0" x2="52" y2="0" />
											<line x1="-37" y1="-37" x2="-28" y2="-28" />
											<line x1="28" y1="28" x2="37" y2="37" />
											<line x1="-37" y1="37" x2="-28" y2="28" />
											<line x1="28" y1="-28" x2="37" y2="-37" />
										</g>
									</g>
								</g>
								
								<!-- Small Gear (Bottom) -->
								<g transform="translate(280, 240)">
									<g class="gear">
										<circle cx="0" cy="0" r="28" stroke="url(#gearGrad)" stroke-width="2" fill="none" opacity="0.8" />
										<circle cx="0" cy="0" r="12" stroke="url(#gearGrad)" stroke-width="1.5" fill="none" opacity="0.5" />
										<circle cx="0" cy="0" r="5" fill="url(#gearGrad)" opacity="0.8" />
										<g stroke="url(#gearGrad)" stroke-width="2.5" stroke-linecap="round" opacity="0.8">
											<line x1="0" y1="-36" x2="0" y2="-28" />
											<line x1="0" y1="28" x2="0" y2="36" />
											<line x1="-36" y1="0" x2="-28" y2="0" />
											<line x1="28" y1="0" x2="36" y2="0" />
										</g>
									</g>
								</g>
								
								<!-- Connection Lines -->
								<g stroke="url(#gearGrad)" stroke-width="2" stroke-linecap="round" opacity="0.5">
									<path d="M155 175 L280 120" stroke-dasharray="8,4">
										<animate attributeName="stroke-dashoffset" values="0;24" dur="2s" repeatCount="indefinite"/>
									</path>
									<path d="M320 160 L280 212" stroke-dasharray="6,3">
										<animate attributeName="stroke-dashoffset" values="0;18" dur="1.5s" repeatCount="indefinite"/>
									</path>
								</g>
								
								<!-- Animated pulsing dots at line endpoints -->
								<circle cx="155" cy="175" r="5" fill="#FFD66B" opacity="0.6">
									<animate attributeName="r" values="4;7;4" dur="2s" repeatCount="indefinite"/>
									<animate attributeName="opacity" values="0.6;0.2;0.6" dur="2s" repeatCount="indefinite"/>
								</circle>
								<circle cx="280" cy="120" r="4" fill="#FFD66B" opacity="0.6">
									<animate attributeName="r" values="3;6;3" dur="2s" begin="0.5s" repeatCount="indefinite"/>
								</circle>
								<circle cx="320" cy="160" r="4" fill="#FFD66B" opacity="0.5">
									<animate attributeName="r" values="3;5;3" dur="2s" begin="1s" repeatCount="indefinite"/>
								</circle>
								<circle cx="280" cy="212" r="3" fill="#FFD66B" opacity="0.5">
									<animate attributeName="r" values="3;5;3" dur="2s" begin="1.5s" repeatCount="indefinite"/>
								</circle>
							</svg>
							
							<!-- Floating decorative gear (bottom right) -->
							<div class="hidden lg:block absolute right-0 bottom-0 w-24 h-24 opacity-15">
								<svg viewBox="0 0 100 100" class="w-full h-full gear-slow">
									<circle cx="50" cy="50" r="35" stroke="url(#gearGrad)" stroke-width="2" fill="none"/>
									<circle cx="50" cy="50" r="15" stroke="url(#gearGrad)" stroke-width="1" fill="none"/>
									<g stroke="url(#gearGrad)" stroke-width="2" stroke-linecap="round">
										<line x1="50" y1="5" x2="50" y2="15" />
										<line x1="50" y1="85" x2="50" y2="95" />
										<line x1="5" y1="50" x2="15" y2="50" />
										<line x1="85" y1="50" x2="95" y2="50" />
									</g>
								</svg>
							</div>
						</div>
					</div>
				</div>

			<!-- Service Categories -->
			<div class="mt-12 flex flex-wrap justify-center gap-3 reveal">
				<span class="px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm text-gray-300 backdrop-blur-sm">Structural Engineering</span>
				<span class="px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm text-gray-300 backdrop-blur-sm">Infrastructure</span>
				<span class="px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm text-gray-300 backdrop-blur-sm">Project Management</span>
				<span class="px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm text-gray-300 backdrop-blur-sm">Sustainable Design</span>
				<span class="px-4 py-2 rounded-full bg-white/5 border border-white/10 text-sm text-gray-300 backdrop-blur-sm">Quality Assurance</span>
			</div>

			<!-- Stats Row -->
			<div class="mt-12 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto reveal">
				<div class="text-center">
					<div class="text-3xl md:text-4xl font-bold text-orange-400">15+</div>
					<div class="text-sm text-gray-400 mt-1">Years Experience</div>
				</div>
				<div class="text-center">
					<div class="text-3xl md:text-4xl font-bold text-yellow-400">200+</div>
					<div class="text-sm text-gray-400 mt-1">Projects Done</div>
				</div>
				<div class="text-center">
					<div class="text-3xl md:text-4xl font-bold text-green-400">50+</div>
					<div class="text-sm text-gray-400 mt-1">Expert Engineers</div>
				</div>
				<div class="text-center">
					<div class="text-3xl md:text-4xl font-bold text-blue-400">98%</div>
					<div class="text-sm text-gray-400 mt-1">Client Satisfaction</div>
				</div>
			</div>

			<!-- Trust Badges -->
			<div class="mt-12 text-center reveal">
				<p class="text-sm text-gray-500 mb-6 uppercase tracking-wider">Trusted by Industry Leaders</p>
				<div class="flex flex-wrap justify-center items-center gap-8 md:gap-12 opacity-60">
					<div class="flex items-center gap-2 text-gray-400">
						<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
						<span class="font-semibold">BuildCorp</span>
					</div>
					<div class="flex items-center gap-2 text-gray-400">
						<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 9h-2V7h2v5zm0 4h-2v-2h2v2z"/></svg>
						<span class="font-semibold">MetroWorks</span>
					</div>
					<div class="flex items-center gap-2 text-gray-400">
						<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
						<span class="font-semibold">CityPlan</span>
					</div>
					<div class="flex items-center gap-2 text-gray-400">
						<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
						<span class="font-semibold">Structura</span>
					</div>
				</div>
			</div>

			<!-- Scroll Down Indicator -->
			<div class="mt-12 flex justify-center reveal">
				<a href="#services" class="flex flex-col items-center gap-2 text-gray-400 hover:text-orange-400 transition-colors">
					<span class="text-sm">Scroll to explore</span>
					<svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
					</svg>
				</a>
			</div>
			</section>

			<!-- Features -->
			<section id="services" class="relative overflow-hidden bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900">
				<!-- Animated background elements -->
				<div class="absolute inset-0 overflow-hidden pointer-events-none">
					<div class="absolute top-1/4 left-1/4 w-96 h-96 bg-orange-500/5 rounded-full blur-3xl floating"></div>
					<div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-yellow-500/5 rounded-full blur-3xl floating-delayed"></div>
				</div>
				
				<!-- Decorative top border with gradient -->
				<div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-orange-400 to-transparent"></div>
				
				<div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 relative z-10">
					<div class="text-center mb-16">
						<span class="inline-block px-4 py-1 mb-4 text-sm font-semibold text-orange-400 bg-orange-400/10 rounded-full border border-orange-400/20 reveal">Why Choose Us</span>
						<h3 class="text-4xl md:text-5xl font-bold text-white mb-4 reveal">Our Core Strengths</h3>
						<p class="text-gray-400 text-lg max-w-2xl mx-auto reveal">Excellence in every project through our foundational principles and commitment to quality</p>
					</div>
					
					<div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-10">
						<!-- Precision Card -->
						<div class="strength-card reveal group" style="--delay: 0.1s;">
							<div class="card-glow"></div>
							<div class="relative z-10">
								<div class="icon-container mb-6">
									<div class="icon-bg">
										<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
										</svg>
									</div>
									<div class="icon-ring"></div>
								</div>
								<h4 class="text-2xl font-bold text-white mb-3 group-hover:text-orange-400 transition-colors duration-300">Precision</h4>
								<p class="text-gray-400 leading-relaxed mb-4">Meticulous engineering processes and rigorous QA to ensure exacting tolerances in every project we deliver.</p>
								<div class="feature-stats">
									<span class="stat-badge">99.8% Accuracy</span>
								</div>
							</div>
						</div>
						
						<!-- Innovation Card -->
						<div class="strength-card reveal group" style="--delay: 0.2s;">
							<div class="card-glow"></div>
							<div class="relative z-10">
								<div class="icon-container mb-6">
									<div class="icon-bg innovation-bg">
										<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
										</svg>
									</div>
									<div class="icon-ring innovation-ring"></div>
								</div>
								<h4 class="text-2xl font-bold text-white mb-3 group-hover:text-yellow-400 transition-colors duration-300">Innovation</h4>
								<p class="text-gray-400 leading-relaxed mb-4">Cutting-edge methods, digital twin modeling, and creative problem-solving to push boundaries.</p>
								<div class="feature-stats">
									<span class="stat-badge innovation-badge">50+ Patents</span>
								</div>
							</div>
						</div>
						
						<!-- Sustainability Card -->
						<div class="strength-card reveal group" style="--delay: 0.3s;">
							<div class="card-glow"></div>
							<div class="relative z-10">
								<div class="icon-container mb-6">
									<div class="icon-bg sustainability-bg">
										<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
										</svg>
									</div>
									<div class="icon-ring sustainability-ring"></div>
								</div>
								<h4 class="text-2xl font-bold text-white mb-3 group-hover:text-green-400 transition-colors duration-300">Sustainability</h4>
								<p class="text-gray-400 leading-relaxed mb-4">Design for long-term performance and reduced environmental impact, building a greener future.</p>
								<div class="feature-stats">
									<span class="stat-badge sustainability-badge">Carbon Neutral</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Projects -->
			<section id="projects" class="bg-gray-900 py-20 relative overflow-hidden">
				<div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-orange-400 via-yellow-400 to-orange-500"></div>
				
				<div class="max-w-7xl mx-auto px-6 lg:px-8">
					<div class="text-center mb-12">
						<h3 class="text-3xl font-bold text-white reveal">Selected Projects</h3>
						<p class="mt-4 text-gray-400 max-w-2xl mx-auto reveal">A selection of our completed and ongoing engineering projects showcasing our expertise</p>
					</div>
					
					<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
						<div class="project-card reveal group cursor-pointer">
							<div class="placeholder-gradient h-48 bg-gradient-to-br from-gray-700 via-gray-800 to-gray-900 rounded-t-lg relative overflow-hidden">
								<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
								<div class="absolute bottom-4 left-4 right-4 text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
									<h4 class="font-bold text-lg">Infrastructure Bridge</h4>
									<p class="text-sm text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-300">Complex structural engineering</p>
								</div>
							</div>
							<div class="p-4 bg-white">
								<div class="flex items-center justify-between">
									<span class="text-gray-900 font-semibold">Metro Bridge Project</span>
									<span class="text-orange-500 text-sm">2025</span>
								</div>
							</div>
						</div>
						
						<div class="project-card reveal group cursor-pointer">
							<div class="placeholder-gradient h-48 bg-gradient-to-br from-gray-700 via-gray-800 to-gray-900 rounded-t-lg relative overflow-hidden">
								<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
								<div class="absolute bottom-4 left-4 right-4 text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
									<h4 class="font-bold text-lg">Commercial Complex</h4>
									<p class="text-sm text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-300">Sustainable design solutions</p>
								</div>
							</div>
							<div class="p-4 bg-white">
								<div class="flex items-center justify-between">
									<span class="text-gray-900 font-semibold">Business Tower</span>
									<span class="text-orange-500 text-sm">2024</span>
								</div>
							</div>
						</div>
						
						<div class="project-card reveal group cursor-pointer">
							<div class="placeholder-gradient h-48 bg-gradient-to-br from-gray-700 via-gray-800 to-gray-900 rounded-t-lg relative overflow-hidden">
								<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
								<div class="absolute bottom-4 left-4 right-4 text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
									<h4 class="font-bold text-lg">Industrial Facility</h4>
									<p class="text-sm text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-300">High-precision engineering</p>
								</div>
							</div>
							<div class="p-4 bg-white">
								<div class="flex items-center justify-between">
									<span class="text-gray-900 font-semibold">Manufacturing Plant</span>
									<span class="text-orange-500 text-sm">2026</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- Footer -->
			<footer class="bg-black text-gray-300 border-t border-white/5">
				<div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
					<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
						<!-- Company Info -->
						<div class="reveal">
							<div class="flex items-center gap-3 mb-4">
								<div class="w-10 h-10 rounded-md bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
									<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
									</svg>
								</div>
								<span class="text-xl font-bold text-white">JAJR Company</span>
							</div>
							<p class="text-gray-500 leading-relaxed">Precision engineering for sustainable infrastructure. Building tomorrow's world with innovation and excellence.</p>
						</div>
						
						<!-- Contact Info -->
						<div class="reveal">
							<div class="font-semibold text-white mb-4 text-lg">Contact</div>
							<div class="space-y-3">
								<a href="mailto:jajrconstruction5@gmail.com" class="footer-link flex items-center gap-2 text-gray-400 hover:text-orange-400 transition">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
									</svg>
									jajrconstruction5@gmail.com
								</a>
								<a href="tel:+15551234567" class="footer-link flex items-center gap-2 text-gray-400 hover:text-orange-400 transition">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
									</svg>
									+1 (555) 123-4567
								</a>
							</div>
						</div>
						
						<!-- Social Links -->
						<div class="reveal">
							<div class="font-semibold text-white mb-4 text-lg">Follow Us</div>
							<div class="flex gap-4">
								<a href="https://www.facebook.com/profile.php?id=61583526880516" class="social-icon" aria-label="Facebook">
									<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
										<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
									</svg>
								</a>
								<a href="#" class="social-icon" aria-label="Instagram">
									<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
										<path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.399.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/>
									</svg>
								</a>
								<a href="#" class="social-icon" aria-label="Portfolio">
									<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
									</svg>
								</a>
							</div>
						</div>
					</div>
					
					<div class="border-t border-white/10 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
						<p class="text-sm text-gray-600">&copy; 2026 JAJR Company. All rights reserved.</p>
						<div class="flex gap-6 text-sm">
							<a href="#" class="footer-link text-gray-500 hover:text-orange-400">Privacy Policy</a>
							<a href="#" class="footer-link text-gray-500 hover:text-orange-400">Terms of Service</a>
						</div>
					</div>
				</div>
			</footer>
		</main>

		<script src="assets/js/main.js" defer></script>
	</body>
</html>
