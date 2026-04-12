<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - ConnectX</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg">
        <div id="onboardingLoading" class="text-center py-10">
            <p class="text-gray-600 animate-pulse text-lg">Initializing your profile engine...</p>
        </div>

        <div id="onboardingContent" class="hidden">
            <h2 id="stepTitle" class="text-2xl font-bold text-gray-800 mb-2">Step Title</h2>
            <p id="stepDescription" class="text-gray-500 mb-8">Description goes here...</p>

            <form id="onboardingForm" class="space-y-6">
                <div id="questionsContainer" class="space-y-4">
                    <!-- Dynamic Questions Here -->
                </div>

                <div class="flex items-center justify-between pt-6">
                    <button type="button" id="btnBack" class="px-6 py-2 text-gray-500 hover:text-gray-700 transition hidden">
                        ← Back
                    </button>
                    <button type="submit" id="btnNext" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition ml-auto">
                        Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Session expired or not found. Please login.');
            window.location.href = '/login';
        }

        const API_BASE = '/api/v1/onboarding';
        let sessionId = null;
        let currentStepId = null;

        async function init() {
            try {
                // Start or Resume Session
                const response = await fetch(`${API_BASE}/sessions`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
                const data = await response.json();
                
                if (response.ok) {
                    sessionId = data.session_id;
                    renderStep(data.current_step);
                } else {
                    console.error('Failed to start session', data);
                    alert('Error starting onboarding. Please try again later.');
                }
            } catch (error) {
                console.error(error);
            }
        }

        function renderStep(step) {
            document.getElementById('onboardingLoading').classList.add('hidden');
            document.getElementById('onboardingContent').classList.remove('hidden');

            currentStepId = step.id;
            document.getElementById('stepTitle').textContent = step.title;
            document.getElementById('stepDescription').textContent = step.description || '';

            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';

            step.questions.forEach(q => {
                const qDiv = document.createElement('div');
                qDiv.className = 'flex flex-col space-y-2';

                const label = document.createElement('label');
                label.className = 'font-semibold text-gray-700';
                label.textContent = q.label;
                qDiv.appendChild(label);

                let input;
                if (q.type === 'select' || q.type === 'radio') {
                    input = document.createElement('select');
                    input.name = q.id;
                    input.className = 'w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50';
                    
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Select option...';
                    input.appendChild(placeholder);

                    q.options.forEach(opt => {
                        const o = document.createElement('option');
                        o.value = opt.value;
                        o.textContent = opt.label;
                        input.appendChild(o);
                    });
                } else if (q.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.name = q.id;
                    input.className = 'w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none h-24';
                } else {
                    input = document.createElement('input');
                    input.type = q.type === 'number' ? 'number' : 'text';
                    input.name = q.id;
                    input.placeholder = q.placeholder || '';
                    input.className = 'w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none';
                }

                input.required = q.required;
                qDiv.appendChild(input);
                container.appendChild(qDiv);
            });

            // Show/Hide Back Button
            const btnBack = document.getElementById('btnBack');
            if (step.order_index > 0) {
                btnBack.classList.remove('hidden');
            } else {
                btnBack.classList.add('hidden');
            }
        }

        document.getElementById('onboardingForm').onsubmit = async (e) => {
            e.preventDefault();
            const btnNext = document.getElementById('btnNext');
            const originalText = btnNext.textContent;
            btnNext.disabled = true;
            btnNext.textContent = 'Processing...';

            const formData = new FormData(e.target);
            const answers = {};
            formData.forEach((val, key) => answers[key] = val);

            try {
                const response = await fetch(`${API_BASE}/sessions/${sessionId}/answer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                        step_id: currentStepId,
                        answers: answers
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    if (data.completed) {
                        alert('Onboarding Complete!');
                        window.location.href = data.redirect_to || '/dashboard';
                    } else if (data.next_step) {
                        renderStep(data.next_step);
                    }
                } else {
                    alert('Error: ' + (data.message || 'Check your answers'));
                }
            } catch (error) {
                alert('Network error');
            } finally {
                btnNext.disabled = false;
                btnNext.textContent = originalText;
            }
        };

        document.getElementById('btnBack').onclick = async () => {
             try {
                const response = await fetch(`${API_BASE}/sessions/${sessionId}/back`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (response.ok) {
                    renderStep(data.next_step);
                }
            } catch (error) {
                console.error(error);
            }
        };

        init();
    </script>
</body>
</html>
