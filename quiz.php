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
?>

<!-- Quiz Hero -->
<section class="hero-gradient text-white py-12 md:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
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
        <div id="quiz-container" class="bg-white rounded-2xl shadow-lg overflow-hidden">

            <!-- Progress Bar -->
            <div class="bg-gray-100 h-2">
                <div id="progress-bar" class="h-full bg-blue-600 transition-all duration-300" style="width: 0%"></div>
            </div>

            <!-- Quiz Content -->
            <div class="p-6 md:p-8">

                <!-- Question 1: Activity -->
                <div class="quiz-step" data-step="1">
                    <div class="text-center mb-8">
                        <span class="text-sm text-blue-600 font-semibold">Question 1 of 5</span>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">What's your main activity?</h2>
                        <p class="text-gray-500 mt-1">What do you want to do at the beach?</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option" data-question="activity" data-value="swimming">
                            <span class="text-4xl mb-2">🏊</span>
                            <span class="font-medium">Swimming</span>
                            <span class="text-sm text-gray-500">Calm water, relaxing dips</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="surfing">
                            <span class="text-4xl mb-2">🏄</span>
                            <span class="font-medium">Surfing</span>
                            <span class="text-sm text-gray-500">Waves & water sports</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="snorkeling">
                            <span class="text-4xl mb-2">🤿</span>
                            <span class="font-medium">Snorkeling</span>
                            <span class="text-sm text-gray-500">Clear water, marine life</span>
                        </button>
                        <button class="quiz-option" data-question="activity" data-value="relaxing">
                            <span class="text-4xl mb-2">🧘</span>
                            <span class="font-medium">Relaxing</span>
                            <span class="text-sm text-gray-500">Sunbathing, reading</span>
                        </button>
                    </div>

                    <button class="quiz-next-btn w-full mt-6 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-lg font-medium transition-colors" onclick="nextStep()" disabled>
                        Continue <span class="ml-1">→</span>
                    </button>
                </div>

                <!-- Question 2: Group -->
                <div class="quiz-step hidden" data-step="2">
                    <div class="text-center mb-8">
                        <span class="text-sm text-blue-600 font-semibold">Question 2 of 5</span>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">Who's going with you?</h2>
                        <p class="text-gray-500 mt-1">This helps us find family-friendly or romantic spots</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option" data-question="group" data-value="solo">
                            <span class="text-4xl mb-2">👤</span>
                            <span class="font-medium">Solo</span>
                            <span class="text-sm text-gray-500">Just me, myself, and I</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="couple">
                            <span class="text-4xl mb-2">💑</span>
                            <span class="font-medium">Couple</span>
                            <span class="text-sm text-gray-500">Romantic getaway</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="family">
                            <span class="text-4xl mb-2">👨‍👩‍👧‍👦</span>
                            <span class="font-medium">Family</span>
                            <span class="text-sm text-gray-500">Kids coming along</span>
                        </button>
                        <button class="quiz-option" data-question="group" data-value="friends">
                            <span class="text-4xl mb-2">👥</span>
                            <span class="font-medium">Friends</span>
                            <span class="text-sm text-gray-500">Group adventure</span>
                        </button>
                    </div>

                    <button class="quiz-next-btn w-full mt-6 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 rounded-lg font-medium transition-colors" onclick="nextStep()" disabled>
                        Continue <span class="ml-1">→</span>
                    </button>
                </div>

                <!-- Question 3: Facilities -->
                <div class="quiz-step hidden" data-step="3">
                    <div class="text-center mb-8">
                        <span class="text-sm text-blue-600 font-semibold">Question 3 of 5</span>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">What facilities do you need?</h2>
                        <p class="text-gray-500 mt-1">Select all that apply</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button class="quiz-option-multi" data-question="facilities" data-value="restrooms">
                            <span class="text-3xl mb-2">🚻</span>
                            <span class="font-medium">Restrooms</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="parking">
                            <span class="text-3xl mb-2">🅿️</span>
                            <span class="font-medium">Parking</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="food">
                            <span class="text-3xl mb-2">🍔</span>
                            <span class="font-medium">Food/Drinks</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="lifeguard">
                            <span class="text-3xl mb-2">🛟</span>
                            <span class="font-medium">Lifeguard</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="shade">
                            <span class="text-3xl mb-2">⛱️</span>
                            <span class="font-medium">Shade/Palapas</span>
                        </button>
                        <button class="quiz-option-multi" data-question="facilities" data-value="none">
                            <span class="text-3xl mb-2">🏝️</span>
                            <span class="font-medium">None needed</span>
                        </button>
                    </div>

                    <button id="facilities-next" class="w-full mt-6 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors">
                        Continue →
                    </button>
                </div>

                <!-- Question 4: Crowd -->
                <div class="quiz-step hidden" data-step="4">
                    <div class="text-center mb-8">
                        <span class="text-sm text-blue-600 font-semibold">Question 4 of 5</span>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">How do you feel about crowds?</h2>
                        <p class="text-gray-500 mt-1">Some beaches are more popular than others</p>
                    </div>

                    <div class="space-y-3">
                        <button class="quiz-option-wide" data-question="crowd" data-value="popular">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🎉</span>
                                <div class="text-left">
                                    <span class="font-medium block">Popular & Social</span>
                                    <span class="text-sm text-gray-500">I enjoy busy beaches with people around</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="crowd" data-value="moderate">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">👥</span>
                                <div class="text-left">
                                    <span class="font-medium block">Balanced</span>
                                    <span class="text-sm text-gray-500">Some people around but not too crowded</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="crowd" data-value="secluded">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏝️</span>
                                <div class="text-left">
                                    <span class="font-medium block">Secluded & Peaceful</span>
                                    <span class="text-sm text-gray-500">I prefer quiet, less-visited beaches</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Question 5: Location -->
                <div class="quiz-step hidden" data-step="5">
                    <div class="text-center mb-8">
                        <span class="text-sm text-blue-600 font-semibold">Question 5 of 5</span>
                        <h2 class="text-2xl font-bold text-gray-900 mt-2">Where will you be staying?</h2>
                        <p class="text-gray-500 mt-1">We'll find beaches near your area</p>
                    </div>

                    <div class="space-y-3">
                        <button class="quiz-option-wide" data-question="location" data-value="san_juan">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏙️</span>
                                <div class="text-left">
                                    <span class="font-medium block">San Juan Area</span>
                                    <span class="text-sm text-gray-500">San Juan, Carolina, Isla Verde</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="west">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🌅</span>
                                <div class="text-left">
                                    <span class="font-medium block">West Coast</span>
                                    <span class="text-sm text-gray-500">Rincón, Aguadilla, Mayagüez</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="east">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🌴</span>
                                <div class="text-left">
                                    <span class="font-medium block">East Coast</span>
                                    <span class="text-sm text-gray-500">Fajardo, Luquillo, Humacao</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="south">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">☀️</span>
                                <div class="text-left">
                                    <span class="font-medium block">South Coast</span>
                                    <span class="text-sm text-gray-500">Ponce, Guánica, Cabo Rojo</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="islands">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🏝️</span>
                                <div class="text-left">
                                    <span class="font-medium block">Islands</span>
                                    <span class="text-sm text-gray-500">Vieques, Culebra</span>
                                </div>
                            </div>
                        </button>
                        <button class="quiz-option-wide" data-question="location" data-value="anywhere">
                            <div class="flex items-center gap-4">
                                <span class="text-3xl">🗺️</span>
                                <div class="text-left">
                                    <span class="font-medium block">Anywhere</span>
                                    <span class="text-sm text-gray-500">I'm flexible, show me all options</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="quiz-loading" class="hidden text-center py-12">
                    <div class="loading-spinner loading-spinner-lg text-blue-600 mx-auto mb-4"></div>
                    <h2 class="text-xl font-bold text-gray-900">Finding your perfect beaches...</h2>
                    <p class="text-gray-500 mt-2">Analyzing 230+ beaches to find your matches</p>
                </div>

                <!-- Results -->
                <div id="quiz-results" class="hidden">
                    <div class="text-center mb-8">
                        <i data-lucide="trophy" class="w-12 h-12 mx-auto text-yellow-500 mb-4" aria-hidden="true"></i>
                        <h2 class="text-2xl font-bold text-gray-900">Your Beach Matches!</h2>
                        <p class="text-gray-500 mt-1">Based on your preferences, here are your top beaches</p>
                    </div>

                    <div id="results-list" class="space-y-4">
                        <!-- Results populated by JavaScript -->
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                        <button onclick="restartQuiz()" class="text-blue-600 hover:text-blue-700 font-medium">
                            ← Take the quiz again
                        </button>
                    </div>
                </div>

                <!-- Navigation -->
                <div id="quiz-nav" class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                    <button id="prev-btn" class="text-gray-500 hover:text-gray-700 font-medium hidden" onclick="prevStep()">
                        ← Back
                    </button>
                    <div></div>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-100 rounded-lg p-4">
            <div class="flex gap-3">
                <i data-lucide="lightbulb" class="w-6 h-6 text-blue-600 shrink-0" aria-hidden="true"></i>
                <div>
                    <h3 class="font-medium text-blue-900">How it works</h3>
                    <p class="text-blue-700 text-sm mt-1">
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
    border: 2px solid #e5e7eb;
    border-radius: 1rem;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.quiz-option:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.quiz-option.selected {
    border-color: #2563eb;
    background: #dbeafe;
}

.quiz-option-multi {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.quiz-option-multi:hover {
    border-color: #3b82f6;
}

.quiz-option-multi.selected {
    border-color: #2563eb;
    background: #dbeafe;
}

.quiz-option-wide {
    display: block;
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
    text-align: left;
}

.quiz-option-wide:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.quiz-option-wide.selected {
    border-color: #2563eb;
    background: #dbeafe;
}

.result-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    transition: all 0.2s ease;
}

.result-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

.match-score.excellent { background: #dcfce7; color: #166534; }
.match-score.great { background: #dbeafe; color: #1e40af; }
.match-score.good { background: #fef9c3; color: #854d0e; }
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
            <img src="${beach.cover_image || '/assets/images/placeholder.jpg'}"
                 alt="${beach.name}"
                 class="w-24 h-24 object-cover rounded-lg shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-gray-900">${beach.name}</h3>
                        <p class="text-sm text-gray-500">${beach.municipality}</p>
                    </div>
                    <div class="match-score ${beach.score >= 90 ? 'excellent' : beach.score >= 75 ? 'great' : 'good'}">
                        ${beach.score}%
                    </div>
                </div>
                ${beach.match_reasons ? `
                    <div class="flex flex-wrap gap-1 mt-2">
                        ${beach.match_reasons.slice(0, 3).map(reason => `
                            <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">${reason}</span>
                        `).join('')}
                    </div>
                ` : ''}
                <div class="mt-3">
                    <button onclick="openBeachDrawer('${beach.id}')"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
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
