<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';
$pageTitle = 'Beach Photography Tips for Puerto Rico';
$pageDescription = 'Capture stunning beach photos with expert tips on lighting, composition, equipment, golden hour photography, and drone regulations in Puerto Rico.';
$relatedGuides = [['title' => 'Best Time to Visit', 'slug' => 'best-time-visit-puerto-rico-beaches'],['title' => 'Beach Packing List', 'slug' => 'beach-packing-list'],['title' => 'Snorkeling Guide', 'slug' => 'snorkeling-guide']];
$faqs = [['question' => 'What is the best time of day for beach photography?', 'answer' => 'Golden hour (hour after sunrise and before sunset) provides warm, soft light perfect for beach photos. Blue hour (twilight) offers moody atmospheric shots. Avoid harsh midday sun which creates unflattering shadows.'],['question' => 'Can I fly drones at Puerto Rico beaches?', 'answer' => 'Yes, but with restrictions. Register drones over 0.55 lbs with FAA. Don\'t fly over people, respect privacy, avoid national parks/wildlife refuges. Max altitude 400 feet. Check local regulations as some beaches prohibit drones.'],['question' => 'How do I protect my camera from sand and water?', 'answer' => 'Use UV filter to protect lens, carry in sealed bag when not shooting, clean with rocket blower (never blow with mouth), use rain sleeve in ocean spray, consider waterproof housing for surf photography.'],['question' => 'What camera settings work best for beach photos?', 'answer' => 'Use low ISO (100-400) in bright light, fast shutter (1/250+) for action, aperture f/8-11 for landscapes. Shoot RAW for maximum editing flexibility. Use exposure compensation +1 to +2 stops to prevent underexposure from bright sand/water.'],['question' => 'Do I need professional equipment for great beach photos?', 'answer' => 'No! Modern smartphones take excellent beach photos. Focus on composition, lighting, and timing rather than gear. Polarizing filter helps any camera reduce glare. Photography skill matters more than equipment cost.']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($pageTitle); ?> - Puerto Rico Beach Finder</title>
<meta name="description" content="<?php echo h($pageDescription); ?>">
<link rel="stylesheet" href="/assets/css/tailwind.min.css"><link rel="stylesheet" href="/assets/css/styles.css">
<?php echo articleSchema($pageTitle,$pageDescription,'https://puertoricobeachfinder.com/guides/beach-photography-tips.php','2024-01-15');
echo howToSchema('How to Photograph Puerto Rico Beaches','Expert photography guide for beach images',[['name'=>'Scout Location','text'=>'Visit beach before golden hour to find best compositions, interesting foreground elements, and vantage points.'],['name'=>'Use Golden Hour Light','text'=>'Shoot during first hour after sunrise or last hour before sunset for warm, flattering light.'],['name'=>'Add Foreground Interest','text'=>'Include rocks, palm trees, shells, or people in foreground to create depth and context.'],['name'=>'Use Polarizing Filter','text'=>'Reduce glare from water and wet sand, enhance blue skies and water clarity.'],['name'=>'Experiment with Angles','text'=>'Shoot from low angles for drama, high vantage points for context, eye-level for intimacy.'],['name'=>'Protect Equipment','text'=>'Keep camera in sealed bag between shots, use UV filter, clean regularly with proper tools.']]);
echo faqSchema($faqs);
echo breadcrumbSchema([['name'=>'Home','url'=>'https://puertoricobeachfinder.com/'],['name'=>'Guides','url'=>'https://puertoricobeachfinder.com/guides/'],['name'=>'Beach Photography','url'=>'https://puertoricobeachfinder.com/guides/beach-photography-tips.php']]);?>
</head>
<body class="bg-gray-50" data-theme="light">
<?php include __DIR__.'/../components/header.php';?>
<section class="bg-gradient-to-br from-green-600 to-green-700 text-white py-16">
<div class="container mx-auto px-4 container-padding">
<nav class="text-sm mb-6 text-green-100"><a href="/" class="hover:text-white">Home</a><span class="mx-2">&gt;</span><a href="/guides/" class="hover:text-white">Guides</a><span class="mx-2">&gt;</span><span>Beach Photography</span></nav>
<h1 class="text-4xl md:text-5xl font-bold mb-4">Beach Photography Tips for Puerto Rico</h1>
<p class="text-xl text-green-50 max-w-3xl">Master beach photography with expert techniques for lighting, composition, and equipment to capture Puerto Rico's stunning coastlines.</p>
</div></section>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
<nav class="space-y-2"><a href="#golden-hour" class="guide-toc-link">Golden Hour</a><a href="#composition" class="guide-toc-link">Composition</a><a href="#equipment" class="guide-toc-link">Equipment</a><a href="#settings" class="guide-toc-link">Camera Settings</a><a href="#underwater" class="guide-toc-link">Underwater</a><a href="#drones" class="guide-toc-link">Drone Rules</a><a href="#faq" class="guide-toc-link">FAQ</a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<p class="lead text-xl text-gray-700 mb-8">Puerto Rico's beaches offer photographers endless opportunities—from turquoise waters to dramatic cliff shores, golden sunsets to vibrant coral reefs. This guide covers essential techniques, equipment recommendations, and local regulations to help you capture professional-quality beach photographs whether using smartphones or DSLRs.</p>
<h2 id="golden-hour" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Golden Hour and Lighting</h2>
<p class="mb-4"><strong>Lighting makes or breaks beach photography.</strong> The hour after sunrise and before sunset provides warm, directional light that enhances colors and creates pleasing shadows. Midday sun produces harsh, unflattering light with washed-out skies and dark shadows.</p>
<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Golden Hour Strategy</h3>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6"><li>Arrive 30 minutes early to scout compositions</li><li>Shoot with sun behind you for front-lit scenes</li><li>Backlit shots create silhouettes and sun flares</li><li>Side lighting emphasizes textures in sand and water</li><li>Golden hour lasts 45-60 minutes—work efficiently</li></ul>
<h2 id="composition" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Composition Techniques</h2>
<ul class="list-disc list-inside space-y-3 text-gray-700 mb-6"><li><strong>Rule of thirds:</strong> Place horizon on upper or lower third line</li><li><strong>Leading lines:</strong> Use shoreline, palm shadows, or pier to guide eye</li><li><strong>Foreground interest:</strong> Include rocks, shells, or vegetation for depth</li><li><strong>Frame subjects:</strong> Shoot through palm trees or rock formations</li><li><strong>Human element:</strong> Add scale with people in distance</li><li><strong>Reflections:</strong> Capture sky reflections in wet sand during receding tide</li></ul>
<h2 id="equipment" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Photography Equipment</h2>
<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Essential Gear</h3>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6"><li><strong>Polarizing filter:</strong> Reduces glare, enhances colors ($30-100)</li><li><strong>Tripod:</strong> For long exposures, sharp landscapes ($50-200)</li><li><strong>Lens cloth:</strong> Remove salt spray frequently</li><li><strong>Rocket blower:</strong> Remove sand without touching lens</li><li><strong>Waterproof bag:</strong> Protect equipment from elements</li></ul>
<h2 id="settings" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Camera Settings</h2>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6"><li><strong>ISO:</strong> 100-400 in daylight (low noise)</li><li><strong>Aperture:</strong> f/8-11 for landscapes (deep focus)</li><li><strong>Shutter:</strong> 1/250+ for action, 1-2 sec for silky water</li><li><strong>White balance:</strong> Cloudy/shade adds warmth</li><li><strong>Exposure compensation:</strong> +1 to +2 stops (bright sand confuses meter)</li></ul>
<h2 id="underwater" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Underwater Photography</h2>
<p class="mb-4">GoPro cameras ($200-400) or waterproof housings ($100-500) enable underwater beach photography showing snorkelers, fish, and coral.</p>
<h2 id="drones" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Drone Photography Rules</h2>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6"><li>Register with FAA if drone weighs over 0.55 lbs</li><li>Don't fly over people or crowds</li><li>Maximum altitude 400 feet</li><li>Prohibited in national parks and wildlife refuges</li><li>Respect privacy—don't hover over sunbathers</li><li>Check NOTAMs for temporary flight restrictions</li></ul>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']);?></h3><p class="text-gray-700"><?php echo h($faq['answer']);?></p></div>
<?php endforeach;?></div>
<div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
<h2 class="text-2xl font-bold text-gray-900 mb-4">Find Photogenic Beaches</h2>
<p class="text-gray-700 mb-6">Browse beaches with stunning landscapes perfect for photography.</p>
<a href="/" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">Explore Beaches</a>
</div></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="/guides/<?php echo h($guide['slug']);?>.php" class="related-guide-card"><span class="related-guide-title"><?php echo h($guide['title']);?></span></a>
<?php endforeach;?></div></div>
</article></div></main>
<?php include __DIR__.'/../components/footer.php';?>
</body></html>
