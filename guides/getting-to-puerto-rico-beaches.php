<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Getting to Puerto Rico Beaches: Complete Transportation Guide';
$pageDescription = 'Learn how to reach Puerto Rico beaches by car, ferry, public transit, and more. Includes costs, tips, and detailed directions for mainland and island beaches.';

// Fetch featured beaches for CTAs
$featuredBeaches = query("
    SELECT id, name, municipality, slug
    FROM beaches
    WHERE slug IN ('flamenco-beach-culebra', 'crash-boat-beach-aguadilla', 'seven-seas-beach-fajardo', 'balneario-de-carolina-carolina', 'sun-bay-vieques')
    LIMIT 5
");

$relatedGuides = [
    ['title' => 'Best Time to Visit Puerto Rico Beaches', 'slug' => 'best-time-visit-puerto-rico-beaches'],
    ['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],
    ['title' => 'Culebra vs Vieques: Which Island to Visit?', 'slug' => 'culebra-vs-vieques']
];

$faqs = [
    [
        'question' => 'Do I need a car to visit Puerto Rico beaches?',
        'answer' => 'While not absolutely necessary, renting a car gives you the most flexibility to explore beaches across the island. Some popular beaches near San Juan are accessible by Uber or public transit, but remote beaches require a vehicle.'
    ],
    [
        'question' => 'How much does a car rental cost in Puerto Rico?',
        'answer' => 'Car rentals typically range from $40-80 per day for economy cars, with prices higher during peak season (December-April). Booking in advance and avoiding airport locations can save money. Budget for gas ($3.50-4.00/gallon) and toll roads ($0.75-2.00).'
    ],
    [
        'question' => 'How do I get to Culebra and Vieques?',
        'answer' => 'Both islands are accessible by passenger ferry from Ceiba or small aircraft from San Juan (SJU) or Ceiba. Ferries cost $2-4 per person and require advance reservations. Flights take 25-30 minutes and cost $100-200 round trip.'
    ],
    [
        'question' => 'Is Uber available to beaches in Puerto Rico?',
        'answer' => 'Uber operates in San Juan metro area and major tourist zones. You can reach beaches like Balneario de Carolina, Isla Verde, and Ocean Park easily. However, Uber is limited in rural areas and may not operate on Culebra or Vieques.'
    ],
    [
        'question' => 'Are Puerto Rico beaches accessible by public transportation?',
        'answer' => 'The AMA (public bus system) serves some beaches in the San Juan metro area, but service is limited. Públicos (shared vans) can reach some towns near beaches but don\'t go directly to beach entrances. For comprehensive beach exploration, a rental car is recommended.'
    ],
    [
        'question' => 'Do I need a 4WD vehicle to reach beaches?',
        'answer' => 'Most beaches are accessible with a standard 2WD vehicle. However, some remote beaches like Playa Sucia (Cabo Rojo) have rough unpaved access roads where 4WD or high clearance is beneficial but not always required.'
    ],
    [
        'question' => 'How far are beaches from San Juan airport?',
        'answer' => 'Isla Verde beach is just 10 minutes from SJU airport. Ocean Park and Condado are 15-20 minutes. Luquillo is 45 minutes east, while Rincon on the west coast is 2.5-3 hours. Culebra and Vieques require ferry or flight connections.'
    ],
    [
        'question' => 'Can I take a taxi to beaches in Puerto Rico?',
        'answer' => 'Yes, but taxis can be expensive for beach trips. Expect $30-50 from San Juan hotels to nearby beaches one-way. For day trips, negotiate a round-trip rate or hourly rate. Uber is often more affordable in areas where it operates.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> - Puerto Rico Beach Finder</title>
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <?php
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/getting-to-puerto-rico-beaches.php', '2024-01-15');
    echo howToSchema(
        'How to Get to Puerto Rico Beaches',
        'Complete guide to reaching beaches across Puerto Rico using various transportation methods',
        [
            ['name' => 'Book Your Flight', 'text' => 'Fly into Luis Muñoz Marín International Airport (SJU) in San Juan, the main gateway to Puerto Rico.'],
            ['name' => 'Choose Transportation', 'text' => 'Decide between renting a car for flexibility, using Uber/taxis for nearby beaches, or public transit for metro area beaches.'],
            ['name' => 'Reserve Car Rental', 'text' => 'If renting, book online in advance for better rates. Pick up at airport or off-site locations to save money.'],
            ['name' => 'Plan Ferry Reservations', 'text' => 'For Culebra or Vieques, book ferry tickets at least 2 weeks in advance through the official ferry system website.'],
            ['name' => 'Download Navigation Apps', 'text' => 'Install Google Maps or Waze with offline maps for Puerto Rico, as some beach areas have limited cell coverage.'],
            ['name' => 'Check Road Conditions', 'text' => 'Before heading to remote beaches, verify road conditions and whether 4WD is recommended.'],
            ['name' => 'Arrange Return Transportation', 'text' => 'If using Uber or taxi, confirm return availability or arrange pickup time in advance, especially for remote beaches.']
        ]
    );
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Getting to Puerto Rico Beaches', 'url' => 'https://puertoricobeachfinder.com/guides/getting-to-puerto-rico-beaches.php']
    ]);
    ?>
</head>
<body class="bg-gray-50" data-theme="light">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-600 to-green-700 text-white py-16">
        <div class="container mx-auto px-4 container-padding">
            <nav class="text-sm mb-6 text-green-100">
                <a href="/" class="hover:text-white">Home</a>
                <span class="mx-2">&gt;</span>
                <a href="/guides/" class="hover:text-white">Guides</a>
                <span class="mx-2">&gt;</span>
                <span>Getting to Beaches</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Getting to Puerto Rico Beaches</h1>
            <p class="text-xl text-green-50 max-w-3xl">
                Your complete guide to transportation options for reaching every beach in Puerto Rico,
                from rental cars to ferries to public transit.
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="guide-layout">
        <!-- Table of Contents -->
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#car-rental" class="guide-toc-link">Car Rentals</a>
                        <a href="#driving" class="guide-toc-link">Driving Tips</a>
                        <a href="#uber-taxi" class="guide-toc-link">Uber & Taxis</a>
                        <a href="#public-transit" class="guide-toc-link">Public Transit</a>
                        <a href="#ferries" class="guide-toc-link">Island Ferries</a>
                        <a href="#flights" class="guide-toc-link">Inter-Island Flights</a>
                        <a href="#costs" class="guide-toc-link">Cost Breakdown</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

            <!-- Article Content -->
        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico offers incredible beach diversity, from the pristine shores of Culebra to the surf breaks of Rincón.
                        Getting to these beaches requires planning, especially if you want to explore beyond the San Juan metro area.
                        This comprehensive guide covers every transportation option available, helping you maximize your beach time while
                        minimizing stress and costs.
                    </p>

                    <h2 id="car-rental" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Renting a Car: Maximum Freedom</h2>

                    <p class="mb-4">
                        For most visitors, <strong>renting a car is the best option</strong> for beach exploration in Puerto Rico.
                        The island's beaches are spread across 100 miles from east to west, and many of the most beautiful spots
                        are far from public transportation routes. A rental car gives you the flexibility to visit multiple beaches
                        in a day, leave when you want, and carry all your beach gear comfortably.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Where to Rent</h3>

                    <p class="mb-4">
                        Most visitors rent from <strong>Luis Muñoz Marín International Airport (SJU)</strong> in San Juan.
                        All major rental companies operate here: Enterprise, Hertz, Budget, Avis, National, and Dollar.
                        However, airport rentals often include higher fees and taxes. To save 10-20%, consider renting from
                        off-airport locations in nearby Isla Verde or Carolina, then taking a short Uber to pick up your car.
                    </p>

                    <p class="mb-4">
                        <strong>Pro tip:</strong> Book at least 2-3 weeks in advance for the best rates, especially during
                        peak season (December through April). Prices can double if you wait until arrival. Compare prices on
                        AutoSlash, Kayak, and directly through rental company websites—sometimes booking direct offers better cancellation policies.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Costs and Vehicle Types</h3>

                    <p class="mb-4">
                        Expect to pay <strong>$40-80 per day</strong> for an economy or compact car during regular season,
                        with prices climbing to $80-150 during peak holidays. SUVs and 4WD vehicles cost $70-120 per day.
                        Most beaches are accessible with a standard 2WD sedan, though a few remote spots like Playa Sucia
                        in Cabo Rojo have rough dirt roads where higher clearance helps.
                    </p>

                    <p class="mb-4">
                        <strong>Insurance considerations:</strong> Your credit card may provide rental car coverage—check before
                        purchasing the rental company's collision damage waiver (CDW), which adds $15-30 per day. However, many
                        credit card policies exclude coverage in Puerto Rico, so verify carefully. Basic liability insurance is
                        mandatory and typically included in the base rate.
                    </p>

                    <?php if (!empty($featuredBeaches[0])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Start Here: Nearby Beach</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach.php?id=<?php echo $featuredBeaches[0]['id']; ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[0]['name']); ?>
                            </a> in <?php echo h($featuredBeaches[0]['municipality']); ?> is easily accessible
                            and makes a perfect first stop after picking up your rental car.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h2 id="driving" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Driving in Puerto Rico</h2>

                    <p class="mb-4">
                        Driving in Puerto Rico is relatively straightforward. Roads are signed in Spanish and English,
                        traffic laws mirror U.S. mainland standards, and your U.S. driver's license is valid. The main
                        highway system includes <strong>PR-52 (south to Ponce)</strong>, <strong>PR-22 (west toward Arecibo)</strong>,
                        and <strong>PR-53 (east to Fajardo)</strong>—all toll roads that cost $0.75-2.00 per toll plaza.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Navigation and GPS</h3>

                    <p class="mb-4">
                        <strong>Google Maps and Waze work excellently</strong> in Puerto Rico and are essential for beach navigation.
                        Download offline maps before heading to remote areas, as cell coverage can be spotty in mountainous regions
                        and on smaller islands. Waze is particularly helpful for real-time traffic alerts in San Juan metro area.
                    </p>

                    <p class="mb-4">
                        Beach addresses can be tricky—many don't have formal street addresses. Instead, searches like "Playa Flamenco, Culebra"
                        or "Balneario de Luquillo" work well. For remote beaches, look up the coordinates or use our beach detail pages
                        which provide exact GPS locations and driving directions.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Parking and Security</h3>

                    <p class="mb-4">
                        Most public beaches (balnearios) have <strong>official parking lots</strong> charging $3-5 per day.
                        Popular beaches like Luquillo, Crash Boat, and Flamenco have ample parking, though they fill up on weekends
                        and holidays—arrive before 10 AM for guaranteed spots. At more remote beaches, parking may be informal roadside
                        or dirt lots. Never leave valuables visible in your car; break-ins do occur at beach parking areas.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Gas Stations and Costs</h3>

                    <p class="mb-4">
                        Gas prices range <strong>$3.50-4.00 per gallon</strong>, slightly higher than U.S. mainland averages.
                        Gas stations are plentiful in urban areas but sparse in rural regions. Fill up before heading to remote
                        beaches on the west or south coasts. Most stations accept credit cards, though some smaller ones are cash-only.
                    </p>

                    <h2 id="uber-taxi" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Uber and Taxis</h2>

                    <p class="mb-4">
                        <strong>Uber operates throughout the San Juan metro area</strong> and major tourist zones including Isla Verde,
                        Condado, Old San Juan, Carolina, and parts of Fajardo. It's the most cost-effective option for reaching nearby
                        beaches without a rental car. A ride from San Juan hotels to Isla Verde Beach costs $8-12, while trips to
                        Balneario de Luquillo run $35-45 one-way.
                    </p>

                    <?php if (!empty($featuredBeaches[1])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Uber-Accessible Beach</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach.php?id=<?php echo $featuredBeaches[1]['id']; ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[1]['name']); ?>
                            </a> is within Uber's service area and perfect for a car-free beach day.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Uber Limitations</h3>

                    <p class="mb-4">
                        Uber coverage is limited outside metro areas. You won't find rides in rural west coast towns like Rincón
                        or on the islands of Culebra and Vieques. Additionally, <strong>return rides can be challenging</strong>
                        from remote beaches—you may arrive easily but struggle to get a ride back. Always check the app for available
                        drivers at your destination before committing to a one-way trip.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Traditional Taxis</h3>

                    <p class="mb-4">
                        Taxis are readily available at the airport and major hotels but can be expensive. Expect <strong>$30-50
                        one-way</strong> from San Juan to nearby beaches. Taxis don't use meters—negotiate the fare before departing.
                        For day trips, consider negotiating an hourly rate ($40-60/hour) or round-trip price where the driver waits
                        for you at the beach.
                    </p>

                    <h2 id="public-transit" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Public Transportation</h2>

                    <p class="mb-4">
                        Puerto Rico's public transit system, <strong>AMA (Autoridad Metropolitana de Autobuses)</strong>,
                        operates primarily in the San Juan metro area. A few routes serve beaches, but service is infrequent,
                        buses don't run on Sundays or holidays, and routes change periodically. Fare is just $0.75, making it
                        the most budget-friendly option, but the time investment is substantial.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Beach-Accessible Routes</h3>

                    <p class="mb-4">
                        <strong>Route A5</strong> connects Old San Juan to Isla Verde Beach via Condado and Ocean Park, running
                        every 30-60 minutes. <strong>Route C45</strong> goes to Balneario de Carolina. Check the official AMA website
                        or Google Maps for current schedules, as routes and times frequently change.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Públicos (Shared Vans)</h3>

                    <p class="mb-4">
                        <strong>Públicos</strong> are shared passenger vans that run fixed routes between towns, functioning as
                        informal public transit. They're useful for reaching towns near beaches but rarely go directly to beach
                        entrances. Service is unpredictable, vans depart when full, and there are no published schedules. Fares
                        are negotiable but typically $3-10 depending on distance. This option works best if you speak Spanish and
                        have flexible timing.
                    </p>

                    <h2 id="ferries" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Ferry Service to Culebra and Vieques</h2>

                    <p class="mb-4">
                        Reaching the stunning beaches of Culebra and Vieques requires either a ferry or flight from the mainland.
                        The <strong>Puerto Rico Maritime Transportation Authority</strong> operates passenger and cargo ferries from
                        Ceiba (on the east coast) to both islands. This is the most economical option but requires advance planning.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Ferry Costs and Schedule</h3>

                    <p class="mb-4">
                        <strong>Passenger tickets cost $2.00 each way</strong> for residents and $2.50 for visitors. Vehicle transport
                        (if available) costs $15-19 each way. Ferries run multiple times daily, typically departing between 6:00 AM
                        and 4:30 PM. The journey takes about 60 minutes to Culebra and 70 minutes to Vieques.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Booking Tickets</h3>

                    <p class="mb-4">
                        <strong>Reserve ferry tickets at least 2-3 weeks in advance</strong>, especially during peak season and weekends.
                        Tickets go on sale online through the official maritime authority website. The system can be temperamental—be
                        prepared to try multiple times or early in the morning when new slots open. Same-day standby tickets are sometimes
                        available but not guaranteed.
                    </p>

                    <?php if (!empty($featuredBeaches[2])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Island Beach Worth the Ferry</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach.php?id=<?php echo $featuredBeaches[2]['id']; ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[2]['name']); ?>
                            </a> is a spectacular destination accessible via ferry.
                            The boat ride adds to the adventure.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Getting to Ceiba Ferry Terminal</h3>

                    <p class="mb-4">
                        The ferry terminal is located in Ceiba, about <strong>60 minutes east of San Juan</strong> via highway PR-52
                        and PR-3. If you're not bringing a car to the island, you can drive to Ceiba, park at the terminal ($5-10/day),
                        and take the passenger ferry. Once on Culebra or Vieques, rent a golf cart, Jeep, or scooter to explore beaches.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vehicle Ferry Considerations</h3>

                    <p class="mb-4">
                        Taking your rental car on the ferry is <strong>challenging and often not worth it</strong>. Vehicle slots are
                        extremely limited, require separate reservations, and many rental companies prohibit taking cars to the islands.
                        It's easier and often cheaper to rent a vehicle directly on Culebra or Vieques for your stay there.
                    </p>

                    <h2 id="flights" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Inter-Island Flights</h2>

                    <p class="mb-4">
                        For travelers who prefer speed over savings, <strong>small aircraft connect San Juan to Culebra and Vieques</strong>
                        in just 25-30 minutes. Several airlines operate these routes including Cape Air, Vieques Air Link, and Air Flamenco.
                        Flights depart from either Luis Muñoz Marín International (SJU) or the smaller Ceiba airport.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Flight Costs and Booking</h3>

                    <p class="mb-4">
                        Round-trip tickets cost <strong>$100-200 per person</strong> depending on season and how far in advance you book.
                        Book directly through airline websites or via Google Flights. These are small propeller planes seating 9-10 passengers
                        with strict 40-pound baggage limits—pack light or pay excess baggage fees.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Advantages of Flying</h3>

                    <p class="mb-4">
                        Flights save significant time—a same-day beach trip to Culebra becomes feasible. The views during takeoff and landing
                        are spectacular, offering aerial perspectives of turquoise waters and coral reefs. Flights also avoid ferry booking
                        headaches and aren't subject to ocean weather cancellations (though wind can delay small planes).
                    </p>

                    <h2 id="costs" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Transportation Cost Breakdown</h2>

                    <p class="mb-4">
                        Here's a realistic cost comparison for different beach transportation scenarios, helping you budget appropriately:
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">San Juan Metro Area Beaches (Daily)</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li><strong>Walking:</strong> $0 (Condado, Ocean Park, Isla Verde if staying nearby)</li>
                            <li><strong>Public bus:</strong> $1.50 round trip</li>
                            <li><strong>Uber/Lyft:</strong> $15-25 round trip</li>
                            <li><strong>Taxi:</strong> $40-60 round trip</li>
                            <li><strong>Rental car:</strong> $50-70 (daily rate + gas + parking)</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">East Coast Beaches (Luquillo, Fajardo)</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li><strong>Uber:</strong> $70-90 round trip (limited availability for return)</li>
                            <li><strong>Taxi:</strong> $120-160 round trip or $50/hour for 4-5 hours</li>
                            <li><strong>Rental car:</strong> $60-80 (daily rate + $10 gas + $5 parking)</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Culebra/Vieques (Full Day from San Juan)</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li><strong>Ferry + rental car to Ceiba:</strong> $60-80 (car rental) + $4 ferry + $10 gas + $10 parking = $85-105</li>
                            <li><strong>Ferry + island golf cart rental:</strong> $4 ferry + $60-80 golf cart = $65-85</li>
                            <li><strong>Round-trip flight:</strong> $100-200 per person + $50-70 island vehicle rental = $150-270</li>
                        </ul>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Weekly Beach Explorer Budget</h3>

                    <p class="mb-4">
                        For a week-long beach-focused trip with daily excursions, expect these transportation totals:
                    </p>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Car rental (7 days):</strong> $280-560</li>
                        <li><strong>Gas:</strong> $50-80</li>
                        <li><strong>Parking:</strong> $20-35</li>
                        <li><strong>Tolls:</strong> $10-20</li>
                        <li><strong>Optional Culebra flight:</strong> $100-200/person</li>
                        <li><strong>Total:</strong> $460-895 (plus optional flights)</li>
                    </ul>

                    <?php if (!empty($featuredBeaches[3])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Budget-Friendly Beach</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach.php?id=<?php echo $featuredBeaches[3]['id']; ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[3]['name']); ?>
                            </a> offers free parking and easy access,
                            keeping your transportation costs minimal.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h2 id="tips" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Money-Saving Transportation Tips</h2>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Book car rentals 3+ weeks ahead</strong> for rates 30-50% lower than last-minute bookings</li>
                        <li><strong>Rent from off-airport locations</strong> to avoid airport fees ($10-30/day)</li>
                        <li><strong>Use AutoSlash</strong> to track rental prices and rebook automatically if rates drop</li>
                        <li><strong>Fill up gas before returning</strong> rental cars—prepaid fuel options are expensive</li>
                        <li><strong>Combine multiple beaches in one day</strong> to maximize rental car value</li>
                        <li><strong>Visit San Juan metro beaches</strong> on days you don't have a rental to save daily costs</li>
                        <li><strong>Book ferry tickets early</strong>—they're incredibly cheap but sell out fast</li>
                        <li><strong>Travel midweek</strong> when rental rates and beach parking are often lower</li>
                        <li><strong>Share transportation costs</strong> if traveling with others—split car rental or taxi fares</li>
                    </ul>

                    <h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>

                    <div class="space-y-6">
                        <?php foreach ($faqs as $faq): ?>
                        <div class="border-l-4 border-green-600 pl-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']); ?></h3>
                            <p class="text-gray-700"><?php echo h($faq['answer']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Ready to Hit the Beach?</h2>
                        <p class="text-gray-700 mb-6">
                            Now that you know how to get there, explore our database of 230+ beaches to plan your perfect itinerary.
                        </p>
                        <a href="/" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                            Browse All Beaches
                        </a>
                    </div>
                </div>

                <!-- Related Guides -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
                    <div class="related-guides-grid">
                        <?php foreach ($relatedGuides as $guide): ?>
                        <a href="/guides/<?php echo h($guide['slug']); ?>.php" class="related-guide-card">
                            <span class="related-guide-title"><?php echo h($guide['title']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
