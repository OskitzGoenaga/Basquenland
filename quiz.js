const quizData = [
    {
        question: "Where is the Basque Country located?",
        options: ["Southern Spain and Portugal", "Northern Spain and Southwestern France", "Eastern Spain and Andorra", "Northern France"],
        correct: 1
    },
    {
        question: "What is the Basque Coast Geopark famous for?",
        options: ["Golden sand dunes", "Volcanic craters", "Rock formations revealing geological history", "Ancient Roman ruins"],
        correct: 2
    },
    {
        question: "Why is La Concha Bay in San Sebastián named so?",
        options: ["Because of its shell shape", "Because of the seashells found there", "It was named by a famous explorer", "Because of an old legend"],
        correct: 0
    },
    {
        question: "What is unique about the Basque language (Euskera)?",
        options: ["It is a dialect of Spanish", "It is similar to French", "It is derived from Latin", "It is unrelated to any other language in the world"],
        correct: 3
    },
    {
        question: "What are 'Pintxos'?",
        options: ["Large grilled steaks", "Small snacks usually served on bread", "Traditional Basque desserts", "Fish cheeks cooked in sauce"],
        correct: 1
    },
    {
        question: "Which of the following is a traditional Basque dessert?",
        options: ["Pantxineta", "Txuleton", "Marmitako", "Talo"],
        correct: 0
    },
    {
        question: "What is a 'Txapela'?",
        options: ["A traditional black beret", "A type of guitar", "A wooden shoe", "A traditional skirt"],
        correct: 0
    },
    {
        question: "What is 'Txalaparta'?",
        options: ["A type of flute", "A horn instrument", "A percussion instrument played by two people", "A traditional dance"],
        correct: 2
    },
    {
        question: "Who is Olentzero?",
        options: ["A famous Basque king", "The Basque equivalent of Santa Claus", "A legendary dragon", "A famous chef"],
        correct: 1
    },
    {
        question: "Which Basque city is home to the Guggenheim Museum?",
        options: ["San Sebastián", "Vitoria-Gasteiz", "Bilbao", "Zumaia"],
        correct: 2
    },
    {
        question: "What is the political capital of the Basque Country?",
        options: ["Bilbao", "San Sebastián", "Vitoria-Gasteiz", "Biarritz"],
        correct: 2
    },
    {
        question: "Which place was a famous filming location for 'Game of Thrones'?",
        options: ["Zumaia Coast", "La Concha Bay", "San Juan de Gaztelugatxe", "Guggenheim Museum"],
        correct: 2
    },
    {
        question: "What is 'Harrijasotzaile'?",
        options: ["Wood chopping competition", "Traditional Basque stone lifting", "Coastal rowing race", "A traditional song"],
        correct: 1
    },
    {
        question: "Where is Tilburg located?",
        options: ["North Holland", "South Holland", "North Brabant", "Friesland"],
        correct: 2
    },
    {
        question: "What historical industry made Tilburg known as the 'Wool City'?",
        options: ["Shipbuilding", "Textile production", "Diamond cutting", "Cheese making"],
        correct: 1
    },
    {
        question: "What is 'Spoorzone' known for today?",
        options: ["A modern train manufacturing plant", "A busy airport terminal", "An old railway zone transformed into a cultural hub", "A historic castle"],
        correct: 2
    },
    {
        question: "What is 013 Poppodium?",
        options: ["A traditional dance", "The largest pop music venue in the Netherlands", "A famous library", "A theme park"],
        correct: 1
    },
    {
        question: "When does the Tilburgse Kermis (largest Benelux funfair) take place?",
        options: ["January", "April", "July", "December"],
        correct: 2
    }
];

let currentQuestionIndex = 0;
let score = 0;
let playerName = "";

function initQuiz() {
    const quizApp = document.getElementById("quiz-app");
    if (!quizApp) return;

    currentQuestionIndex = 0;
    score = 0;
    playerName = localStorage.getItem("lastQuizPlayer") || "";

    quizApp.innerHTML = `
        <p class="section-label">01 &mdash; Basque Knowledge Test</p>
        <h2>Welcome to the Quiz!</h2>
        <p style="margin-bottom: 24px;">Test your knowledge about the Basque Country. Enter your name below to start and see if you can make it to the leaderboard.</p>
        <label for="player-name" style="display:block; font-size:0.85rem; color:#646d9e; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px;">Your Name</label>
        <input type="text" id="player-name" class="quiz-input" placeholder="e.g. Amaia" value="${playerName}">
        <button id="start-btn" class="main-knop">Start The Quiz</button>
        <div style="margin-top: 60px;">
            ${renderLeaderboardHTML()}
        </div>
    `;

    document.getElementById("start-btn").onclick = () => {
        const nameInput = document.getElementById("player-name").value.trim();
        if (nameInput === "") {
            alert("Please enter your name to start the quiz.");
            return;
        }
        playerName = nameInput;
        localStorage.setItem("lastQuizPlayer", playerName);
        startQuiz();
    };
}

function startQuiz() {
    const quizApp = document.getElementById("quiz-app");
    
    quizApp.innerHTML = `
        <div class="quiz-progress">
            <div class="quiz-progress-bar" id="progress-bar"></div>
        </div>
        <div id="question-header"></div>
        <div id="options-container" style="margin-bottom: 30px;"></div>
        <button id="next-btn" class="main-knop" style="display:none;">Next Question</button>
    `;

    document.getElementById("next-btn").onclick = () => {
        currentQuestionIndex++;
        if (currentQuestionIndex < quizData.length) {
            showQuestion();
        } else {
            finishQuiz();
        }
    };

    showQuestion();
}

function showQuestion() {
    const nextBtn = document.getElementById("next-btn");
    nextBtn.style.display = "none";
    
    const qData = quizData[currentQuestionIndex];
    
    // Update progress bar
    const progress = (currentQuestionIndex / quizData.length) * 100;
    document.getElementById("progress-bar").style.width = `${progress}%`;

    // Update Question Header
    const questionHeader = document.getElementById("question-header");
    questionHeader.innerHTML = `
        <p class="section-label">Question ${currentQuestionIndex + 1} of ${quizData.length}</p>
        <h2 style="margin-bottom:24px;">${qData.question}</h2>
    `;

    // Update Options
    const optionsContainer = document.getElementById("options-container");
    optionsContainer.innerHTML = ''; 

    qData.options.forEach((option, index) => {
        const btn = document.createElement("button");
        btn.className = "quiz-option";
        btn.textContent = option;

        btn.onclick = () => checkAnswer(index, btn);
        optionsContainer.appendChild(btn);
    });
}

function checkAnswer(selectedIndex, selectedBtn) {
    const qData = quizData[currentQuestionIndex];
    const optionsContainer = document.getElementById("options-container");
    const buttons = optionsContainer.getElementsByTagName("button");

    for (let i = 0; i < buttons.length; i++) {
        buttons[i].disabled = true;
        buttons[i].style.cursor = "not-allowed";
        if (i === qData.correct) {
            buttons[i].classList.add("selected-correct");
        }
    }

    if (selectedIndex === qData.correct) {
        score++;
    } else {
        selectedBtn.classList.add("selected-wrong");
    }

    const nextBtn = document.getElementById("next-btn");
    nextBtn.style.display = "inline-block";
    if (currentQuestionIndex === quizData.length - 1) {
        nextBtn.textContent = "Finish Quiz";
    }
}

function finishQuiz() {
    saveScore();
    showResults();
}

function saveScore() {
    let ranking = JSON.parse(localStorage.getItem("basqueQuizRanking")) || [];
    
    // Check if player exists
    const existingIndex = ranking.findIndex(entry => entry.name.toLowerCase() === playerName.toLowerCase());
    
    if (existingIndex !== -1) {
        // Only update if the new score is higher
        if (score > ranking[existingIndex].score) {
            ranking[existingIndex].score = score;
            ranking[existingIndex].date = new Date().toLocaleDateString();
        }
    } else {
        ranking.push({
            name: playerName,
            score: score,
            total: quizData.length,
            date: new Date().toLocaleDateString()
        });
    }

    // Sort descending
    ranking.sort((a, b) => b.score - a.score);
    localStorage.setItem("basqueQuizRanking", JSON.stringify(ranking));
}

function renderLeaderboardHTML() {
    const ranking = JSON.parse(localStorage.getItem("basqueQuizRanking")) || [];
    
    if (ranking.length === 0) {
        return `
            <h3 style="margin-top:0;">Top 10 Leaderboard</h3>
            <p style="color:#646d9e; font-style:italic;">No scores recorded yet. Be the first to play!</p>
        `;
    }

    let rowsHtml = ranking.slice(0, 10).map((entry, idx) => `
        <tr>
            <td style="font-weight:${idx < 3 ? '700' : '600'}; color:${idx === 0 ? '#d4af37' : (idx === 1 ? '#c0c0c0' : (idx === 2 ? '#cd7f32' : '#1e2768'))}">#${idx + 1}</td>
            <td>${entry.name}</td>
            <td>${entry.score} / ${entry.total || quizData.length}</td>
            <td style="color:#646d9e; font-size:0.9rem;">${entry.date || ''}</td>
        </tr>
    `).join('');

    return `
        <h3 style="margin-top:0;">Top 10 Leaderboard</h3>
        <div style="overflow-x:auto;">
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th style="width:10%;">Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
        </div>
    `;
}

function showResults() {
    const quizApp = document.getElementById("quiz-app");
    
    let message = "";
    const percentage = score / quizData.length;
    if (percentage === 1.0) {
        message = "Perfect! You're a true Basque Country expert!";
    } else if (percentage >= 0.7) {
        message = "Great job! You know a lot about the Basque Country.";
    } else if (percentage >= 0.4) {
        message = "Good effort! You've learned some interesting facts.";
    } else {
        message = "Time to read the article again to learn more about this beautiful region!";
    }

    quizApp.innerHTML = `
        <div class="quiz-progress">
            <div class="quiz-progress-bar" style="width: 100%;"></div>
        </div>
        <p class="section-label">Quiz Completed</p>
        <h2 style="font-size: clamp(2.5rem, 6vw, 4rem); color: #31d866; margin: 15px 0; line-height: 1;">${score} <span style="font-size: 1.5rem; color: #1e2768;">/ ${quizData.length}</span></h2>
        <p style="font-size: 1.1rem; color: #4a548f; line-height: 1.6; margin-bottom: 30px; font-weight:600;">${message}</p>
        
        <div style="display:flex; gap:16px; margin-bottom:40px; flex-wrap:wrap;">
            <button class="main-knop" onclick="initQuiz()">Play Again</button>
            <button class="main-knop main-knop-licht" onclick="window.location.href='index.php'">Return Home</button>
        </div>

        ${renderLeaderboardHTML()}
    `;
}

// Initialize quiz if container exists on the page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initQuiz);
} else {
    initQuiz();
}
