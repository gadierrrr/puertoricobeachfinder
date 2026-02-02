<?php
/**
 * Beach Match Quiz
 * Helps users find their perfect beach based on preferences
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';

// Page metadata
$pageTitle = 'Beach Match Quiz';
$pageDescription = 'Find your perfect Puerto Rico beach! Answer a few quick questions and get personalized beach recommendations.';

// Include header
include __DIR__ . '/components/header.php';

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Beach Match Quiz']
];
?>

<!-- Quiz Hero -->
<section class="hero-gradient text-white py-12 md:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Breadcrumbs -->
        <div class="mb-6">
            <?php include __DIR__ . '/components/breadcrumbs.php'; ?>
        </div>
        <h1 class="text-3xl md:text-5xl font-bold mb-4">
            Find Your Perfect Beach
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto">
            Answer 5 quick questions and we'll match you with the best Puerto Rico beaches for your preferences!
        </p>
    </div>
</section>

<!-- Quiz Container -->
<section class="py-8 md:py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Quiz Card -->
        <div id="quiz-container" class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-2xl shadow-glass overflow-hidden">

            <!-- Progress Bar -->
            <div class="bg-white/10 h-2">
                <div id="progress-bar" class="h-full bg-brand-yellow transition-all duration-300" style="width: 0%"></div>
            </div>

            <!-- Quiz Content -->
            <div class="p-6 md:p-8">

                <!-- Question 1: Activity -->
                <div class="quiz-step" data-step="1">
                    <div class="text-center mb-8">
                        <span class="text-sm text-brand-yellow font-semibold">Question 1 of 5</span>
                        <h2 class="text-2xl font-bold text-brand-text mt-2">What's your main activity?</h2>
                        <p class="text-brand-muted mt-1">What do you want to do at the beach?</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option" data-question="activity" data-value="swimming">
                            <span class="text-4xl mb-2">🏊</span>
                            <span class="font-medium text-brand-text">Swimming</span>
                            <span class="text-sm text-brand-muted">Calm water, relaxing dips</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="surfing">
                            <span class="text-4xl mb-2">🏄</span>
                            <span class="font-medium text-brand-text">Surfing</span>
                            <span class="text-sm text-brand-muted">Waves & water sports</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="snorkeling">
                            <span class="text-4xl mb-2">🤿</span>
                            <span class="font-medium text-brand-text">Snorkeling</span>
                            <span class="text-sm text-brand-muted">Clear water, marine life</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="relaxing">
                            <span class="text-4xl mb-2">🧘</span>
                            <span class="font-medium text-brand-text">Relaxing</span>
                            <span class="text-sm text-brand-muted">Sunbathing, reading</span>
                        </button>
                    </div>

                    <button class="quiz-next-btn w-full mt-6 bg-brand-yellow hover:bg-yellow-300 disabled:bg-white/10 disabled:cursor-not-allowed text-brand-darker py-3 rounded-lg font-medium transition-colors" onclick="nextStep()" disabled>
                        Continue <span class="ml-1">→</span>
                    </button>
                </div>

                <!-- Question 2: Group -->
                <div class="quiz-step hidden" data-step="2">
                    <div class="text-center mb-8">
                        <span class="text-sm text-brand-yellow font-semibold">Question 2 of 5</span>
                        <h2 class="text-2xl font-bold text-brand-text mt-2">Who's going with you?</h2>
                        <p class="text-brand-muted mt-1">This helps us find family-friendly or romantic spots</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option" data-question="group" data-value="solo">
                            <span class="text-4xl mb-2">👤</span>
                            <span class="font-medium text-brand-text">Solo</span>
                            <span class="text-sm text-brand-muted">Just me, myself, and I</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="couple">
                            <span class="text-4xl mb-2">💑</span>
                            <span class="font-medium text-brand-text">Couple</span>
                            <span class="text-sm text-brand-muted">Romantic getaway</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="family">
                            <span class="text-4xl mb-2">👨‍👩‍👧‍👦</span>
                            <span class="font-medium text-brand-text">Family</span>
                            <span class="text-sm text-brand-muted">Kids coming along</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="friends">
                            <span class="text-4xl mb-2">👥</span>
                            <span class="font-medium text-brand-text">Friends</span>
                            <span class="text-sm text-brand-muted">Group adventure</span>
                        </button>
                    </div>

                    <button class="quiz-next-btn w-full mt-6 bg-brand-yellow hover:bg-yellow-300 disabled:bg-white/10 disabled:cursor-not-allowed text-brand-darker py-3 rounded-lg font-medium transition-colors" onclick="nextStep()" disabled>
                        Continue <span class="ml-1">→</span>
                    </button>
                </div>

                <!-- Question 3: Facilities -->
                <div class="quiz-step hidden" data-step="3">
                    <div class="text-center mb-8">
                        <span class="text-sm text-brand-yellow font-semibold">Question 3 of 5</span>
                        <h2 class="text-2xl font-bold text-brand-text mt-2">What facilities do you need?</h2>
                        <p class="text-brand-muted mt-1">Select all that apply</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option-multi" data-question="facilities" data-value="restrooms">
                            <span class="text-3xl mb-2">🚻</span>
                            <span class="font-medium text-brand-text">Restrooms</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="parking">
                            <span class="text-3xl mb-2">🅿️</span>
                            <span class="font-medium text-brand-text">Parking</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="food">
                            <span class="text-3xl mb-2">🍔</span>
                            <span class="font-medium text-brand-text">Food/Drinks</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="lifeguard">
                            <span class="text-3xl mb-2">🛟</span>
                            <span class="font-medium text-brand-text">Lifeguard</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="shade">
                            <span class="text-3xl mb-2">⛱️</span>
                            <span class="font-medium text-brand-text">Shade/Palapas</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="none">
                            <span class="text-3xl mb-2">🏝️</span>
                            <span class="font-medium text-brand-text">None needed</span>
                        </button>
                    </div>

                    <button id="facilities-next" class="w-full mt-6 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-medium transition-colors">
                        Continue →
                    </button>
                </div>

                <!-- Question 4: Crowd -->
                <div class="quiz-step hidden" data-step="4">
                    <div class="text-center mb-8">
                        <span class="text-sm text-brand-yellow font-semibold">Question 4 of 5</span>
                        <h2 class="text-2xl font-bold text-brand-text mt-2">How do you feel about crowds?</h2>
                        <p class="text-brand-muted mt-1">Some beaches are more popular than others</p>
                    </div>

                    <div class="space-y-3">
                        <button class="quiz-option-wide" data-question="crowd" data-value="popular">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🎉</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">Popular & Social</span>
                                    <span class="text-sm text-brand-muted">I enjoy busy beaches with people around</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="crowd" data-value="moderate">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">👥</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">Balanced</span>
                                    <span class="text-sm text-brand-muted">Some people around but not too crowded</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="crowd" data-value="secluded">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏝️</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">Secluded & Peaceful</span>
                                    <span class="text-sm text-brand-muted">I prefer quiet, less-visited beaches</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Question 5: Location -->
                <div class="quiz-step hidden" data-step="5">
                    <div class="text-center mb-8">
                        <span class="text-sm text-brand-yellow font-semibold">Question 5 of 5</span>
                        <h2 class="text-2xl font-bold text-brand-text mt-2">Where will you be staying?</h2>
                        <p class="text-brand-muted mt-1">We'll find beaches near your area</p>
                    </div>

                    <div class="space-y-3">
                        <button class="quiz-option-wide" data-question="location" data-value="san_juan">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏙️</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">San Juan Area</span>
                                    <span class="text-sm text-brand-muted">San Juan, Carolina, Isla Verde</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="west">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🌅</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">West Coast</span>
                                    <span class="text-sm text-brand-muted">Rincón, Aguadilla, Mayagüez</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="east">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🌴</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">East Coast</span>
                                    <span class="text-sm text-brand-muted">Fajardo, Luquillo, Humacao</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="south">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">☀️</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">South Coast</span>
                                    <span class="text-sm text-brand-muted">Ponce, Guánica, Cabo Rojo</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="islands">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏝️</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">Islands</span>
                                    <span class="text-sm text-brand-muted">Vieques, Culebra</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="anywhere">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🗺️</span>
                                <div class="text-left">
                                    <span class="font-medium block text-brand-text">Anywhere</span>
                                    <span class="text-sm text-brand-muted">I'm flexible, show me all options</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="quiz-loading" class="hidden text-center py-12">
                    <div class="loading-spinner loading-spinner-lg text-brand-yellow mx-auto mb-4"></div>
                    <h2 class="text-xl font-bold text-brand-text">Finding your perfect beaches...</h2>
                    <p class="text-brand-muted mt-2">Analyzing 230+ beaches to find your matches</p>
                </div>

                <!-- Results -->
                <div id="quiz-results" class="hidden">
                    <div class="text-center mb-8">
                        <i data-lucide="trophy" class="w-12 h-12 mx-auto text-brand-yellow mb-4" aria-hidden="true"></i>
                        <h2 class="text-2xl font-bold text-brand-text">Your Beach Matches!</h2>
                        <p class="text-brand-muted mt-1">Based on your preferences, here are your top beaches</p>
                    </div>

                    <div id="results-list" class="space-y-4">
                        <!-- Results populated by JavaScript -->
                    </div>

                    <div class="mt-8 pt-6 border-t border-white/10 text-center">
                        <button onclick="restartQuiz()" class="text-brand-yellow hover:text-yellow-300 font-medium">
                            ← Take the quiz again
                        </button>
                    </div>
                </div>

                <!-- Navigation -->
                <div id="quiz-nav" class="flex justify-between mt-8 pt-6 border-t border-white/10">
                    <button id="prev-btn" class="text-brand-muted hover:text-brand-text font-medium hidden" onclick="prevStep()">
                        ← Back
                    </button>
                    <div></div>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-white/5 backdrop-blur-sm border border-white/10 rounded-lg p-4">
            <div class="flex gap-3">
                <i data-lucide="lightbulb" class="w-6 h-6 text-brand-yellow shrink-0" aria-hidden="true"></i>
                <div>
                    <h3 class="font-medium text-brand-text">How it works</h3>
                    <p class="text-brand-muted text-sm mt-1">
                        Our algorithm analyzes beach features like water conditions, amenities, crowd levels,
                        and location to find beaches that match your preferences.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.quiz-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
    cursor: pointer;
}

.quiz-option:hover {
    border-color: rgba(253, 224, 71, 0.5);
    background: rgba(253, 224, 71, 0.1);
}

.quiz-option.selected {
    border-color: #fde047;
    background: rgba(253, 224, 71, 0.15);
}

.quiz-option-multi {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
    cursor: pointer;
}

.quiz-option-multi:hover {
    border-color: rgba(253, 224, 71, 0.5);
}

.quiz-option-multi.selected {
    border-color: #fde047;
    background: rgba(253, 224, 71, 0.15);
}

.quiz-option-wide {
    display: block;
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
    cursor: pointer;
    text-align: left;
}

.quiz-option-wide:hover {
    border-color: rgba(253, 224, 71, 0.5);
    background: rgba(253, 224, 71, 0.1);
}

.quiz-option-wide.selected {
    border-color: #fde047;
    background: rgba(253, 224, 71, 0.15);
}

.result-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
}

.result-card:hover {
    border-color: rgba(253, 224, 71, 0.5);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.match-score {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.875rem;
}

.match-score.excellent { background: linear-gradient(135deg, #059669, #10b981); color: white; }
.match-score.great { background: linear-gradient(135deg, #06b6d4, #22d3ee); color: white; }
.match-score.good { background: linear-gradient(135deg, #fde047, #facc15); color: #132024; }
</style>

<script>
// Quiz state
const quizState = {
    currentStep: 1,
    totalSteps: 5,
    answers: {
        activity: null,
        group: null,
        facilities: [],
        crowd: null,
        location: null
    }
};

// Initialize quiz
document.addEventListener('DOMContentLoaded', () => {
    // Single select options
    document.querySelectorAll('.quiz-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const question = btn.dataset.question;
            const value = btn.dataset.value;

            // Update state
            quizState.answers[question] = value;

            // Update UI
            btn.closest('.quiz-step').querySelectorAll('.quiz-option').forEach(b => {
                b.classList.remove('selected');
            });
            btn.classList.add('selected');

            // Auto-advance after short delay
            setTimeout(() => nextStep(), 300);
        });
    });

    // Multi-select options
    document.querySelectorAll('.quiz-option-multi').forEach(btn => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.value;

            // Handle "none needed" option
            if (value === 'none') {
                quizState.answers.facilities = ['none'];
                btn.closest('.quiz-step').querySelectorAll('.quiz-option-multi').forEach(b => {
                    b.classList.remove('selected');
                });
                btn.classList.add('selected');
            } else {
                // Remove 'none' if other option selected
                const idx = quizState.answers.facilities.indexOf('none');
                if (idx > -1) {
                    quizState.answers.facilities.splice(idx, 1);
                    btn.closest('.quiz-step').querySelector('[data-value="none"]')?.classList.remove('selected');
                }

                // Toggle selection
                btn.classList.toggle('selected');
                const valueIdx = quizState.answers.facilities.indexOf(value);
                if (valueIdx > -1) {
                    quizState.answers.facilities.splice(valueIdx, 1);
                } else {
                    quizState.answers.facilities.push(value);
                }
            }
        });
    });

    // Wide options (single select)
    document.querySelectorAll('.quiz-option-wide').forEach(btn => {
        btn.addEventListener('click', () => {
            const question = btn.dataset.question;
            const value = btn.dataset.value;

            quizState.answers[question] = value;

            btn.closest('.quiz-step').querySelectorAll('.quiz-option-wide').forEach(b => {
                b.classList.remove('selected');
            });
            btn.classList.add('selected');

            // Auto-advance or submit
            setTimeout(() => {
                if (quizState.currentStep === quizState.totalSteps) {
                    submitQuiz();
                } else {
                    nextStep();
                }
            }, 300);
        });
    });

    // Facilities continue button
    document.getElementById('facilities-next')?.addEventListener('click', () => {
        if (quizState.answers.facilities.length > 0) {
            nextStep();
        } else {
            showToast('Please select at least one option', 'warning');
        }
    });

    updateProgress();
});

function nextStep() {
    if (quizState.currentStep < quizState.totalSteps) {
        document.querySelector(`[data-step="${quizState.currentStep}"]`).classList.add('hidden');
        quizState.currentStep++;
        document.querySelector(`[data-step="${quizState.currentStep}"]`).classList.remove('hidden');
        updateProgress();
        updateNavigation();
    }
}

function prevStep() {
    if (quizState.currentStep > 1) {
        document.querySelector(`[data-step="${quizState.currentStep}"]`).classList.add('hidden');
        quizState.currentStep--;
        document.querySelector(`[data-step="${quizState.currentStep}"]`).classList.remove('hidden');
        updateProgress();
        updateNavigation();
    }
}

function updateProgress() {
    const progress = ((quizState.currentStep - 1) / quizState.totalSteps) * 100;
    document.getElementById('progress-bar').style.width = `${progress}%`;
}

function updateNavigation() {
    const prevBtn = document.getElementById('prev-btn');
    if (quizState.currentStep > 1) {
        prevBtn.classList.remove('hidden');
    } else {
        prevBtn.classList.add('hidden');
    }
}

async function submitQuiz() {
    // Hide current step, show loading
    document.querySelector(`[data-step="${quizState.currentStep}"]`).classList.add('hidden');
    document.getElementById('quiz-nav').classList.add('hidden');
    document.getElementById('quiz-loading').classList.remove('hidden');

    // Complete progress bar
    document.getElementById('progress-bar').style.width = '100%';

    try {
        // Call API
        const response = await fetch('/api/quiz/match.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(quizState.answers)
        });

        const data = await response.json();

        if (data.success) {
            displayResults(data.matches);
        } else {
            throw new Error(data.error || 'Failed to get results');
        }
    } catch (error) {
        console.error('Quiz error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        restartQuiz();
    }
}

function displayResults(matches) {
    document.getElementById('quiz-loading').classList.add('hidden');

    const resultsList = document.getElementById('results-list');
    resultsList.innerHTML = matches.map((beach, index) => `
        <div class="result-card">
            <img src="${beach.cover_image || '/images/beaches/placeholder-beach.webp'}"
                 alt="${beach.name}"
                 class="w-24 h-24 object-cover rounded-lg shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-brand-text">${beach.name}</h3>
                        <p class="text-sm text-brand-muted">${beach.municipality}</p>
                    </div>
                    <div class="match-score ${beach.score >= 90 ? 'excellent' : beach.score >= 75 ? 'great' : 'good'}">
                        ${beach.score}%
                    </div>
                </div>
                ${beach.match_reasons ? `
                    <div class="flex flex-wrap gap-1 mt-2">
                        ${beach.match_reasons.slice(0, 3).map(reason => `
                            <span class="text-xs bg-brand-yellow/20 text-brand-yellow px-2 py-0.5 rounded-full">${reason}</span>
                        `).join('')}
                    </div>
                ` : ''}
                <div class="mt-3">
                    <button onclick="openBeachDrawer('${beach.id}')"
                            class="text-sm text-brand-yellow hover:text-yellow-300 font-medium">
                        View Details →
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('quiz-results').classList.remove('hidden');
}

function restartQuiz() {
    // Reset state
    quizState.currentStep = 1;
    quizState.answers = {
        activity: null,
        group: null,
        facilities: [],
        crowd: null,
        location: null
    };

    // Reset UI
    document.querySelectorAll('.quiz-option, .quiz-option-multi, .quiz-option-wide').forEach(btn => {
        btn.classList.remove('selected');
    });

    // Show first step
    document.querySelectorAll('.quiz-step').forEach(step => step.classList.add('hidden'));
    document.querySelector('[data-step="1"]').classList.remove('hidden');

    document.getElementById('quiz-loading').classList.add('hidden');
    document.getElementById('quiz-results').classList.add('hidden');
    document.getElementById('quiz-nav').classList.remove('hidden');

    updateProgress();
    updateNavigation();
}
</script>

<?php
include __DIR__ . '/components/footer.php';
?>
