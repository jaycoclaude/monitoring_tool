<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Hub - Active Coding Session</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'SF Pro Display', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #0d1117;
            --secondary: #161b22;
            --accent: #58a6ff;
            --accent-glow: rgba(88, 166, 255, 0.7);
            --success: #7ee787;
            --success-glow: rgba(126, 231, 135, 0.7);
            --warning: #f2cc60;
            --text: #e6edf3;
            --text-secondary: #8b949e;
            --border: #30363d;
            --card-bg: rgba(22, 27, 34, 0.9);
        }
        
        .light-mode {
            --primary: #ffffff;
            --secondary: #f6f8fa;
            --accent: #0969da;
            --accent-glow: rgba(9, 105, 218, 0.7);
            --success: #1a7f37;
            --success-glow: rgba(26, 127, 55, 0.7);
            --warning: #9a6700;
            --text: #1f2328;
            --text-secondary: #656d76;
            --border: #d0d7de;
            --card-bg: rgba(246, 248, 250, 0.9);
        }
        
        body {
            background: var(--primary);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .matrix-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.03;
        }
        
        .container {
            text-align: center;
            z-index: 10;
            padding: 2rem;
            max-width: 1000px;
            width: 95%;
        }
        
        .header {
            margin-bottom: 3rem;
            position: relative;
        }
        
        .theme-toggle {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 0.7rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text);
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px var(--accent-glow);
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .logo-icon {
            font-size: 3rem;
            color: var(--accent);
            filter: drop-shadow(0 0 10px var(--accent-glow));
            animation: pulse 3s infinite;
        }
        
        h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            text-shadow: 0 0 15px var(--accent-glow);
            background: linear-gradient(to right, var(--accent), var(--success));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 3px;
            font-weight: 800;
        }
        
        .tagline {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: var(--success);
            font-weight: 300;
        }
        
        .status-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 2rem 0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .status-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(88, 166, 255, 0.1), transparent);
            animation: shine 8s infinite;
        }
        
        .status {
            font-size: 3rem;
            color: var(--success);
            text-shadow: 0 0 15px var(--success-glow);
            margin: 1rem 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            color: #ff6b6b;
            animation: blink 1.5s infinite;
        }
        
        .live-dot {
            width: 12px;
            height: 12px;
            background: #ff6b6b;
            border-radius: 50%;
            box-shadow: 0 0 10px #ff6b6b;
        }
        
        .content-area {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 3rem;
            justify-content: center;
        }
        
        .code-box {
            flex: 1;
            min-width: 700px;
            background: var(--secondary);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: left;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .code-box:hover {
            transform: translateY(-5px);
        }
        
        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }
        
        .code-title {
            color: var(--accent);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        
        .code-tabs {
            display: flex;
            gap: 0.5rem;
        }
        
        .code-tab {
            padding: 0.5rem 1rem;
            background: var(--primary);
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            color: var(--text);
        }
        
        .code-tab.active {
            background: var(--accent);
            color: white;
            box-shadow: 0 0 10px var(--accent-glow);
        }
        
        .code-tab:hover:not(.active) {
            background: rgba(88, 166, 255, 0.1);
        }
        
        .code-content {
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 1rem;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre;
            color: var(--text);
            background: rgba(13, 17, 23, 0.7);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            max-height: 400px;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .light-mode .code-content {
            background: rgba(246, 248, 250, 0.9);
        }
        
        .code-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .code-content::-webkit-scrollbar-track {
            background: var(--primary);
            border-radius: 4px;
        }
        
        .code-content::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 4px;
        }
        
        .keyword {
            color: #ff7b72;
        }
        
        .function {
            color: #d2a8ff;
        }
        
        .variable {
            color: #79c0ff;
        }
        
        .string {
            color: #7ee787;
        }
        
        .comment {
            color: #8b949e;
            font-style: italic;
        }
        
        .number {
            color: #f2cc60;
        }
        
        .light-mode .keyword {
            color: #cf222e;
        }
        
        .light-mode .function {
            color: #8250df;
        }
        
        .light-mode .variable {
            color: #0550ae;
        }
        
        .light-mode .string {
            color: #0a3069;
        }
        
        .light-mode .comment {
            color: #656d76;
        }
        
        .light-mode .number {
            color: #953800;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            background: var(--accent);
            animation: float 15s infinite linear;
        }
        
        .terminal {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--border);
            font-family: 'Fira Code', 'Courier New', monospace;
            text-align: left;
            max-height: 200px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .terminal-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .terminal-content {
            color: var(--success);
            line-height: 1.5;
        }
        
        .terminal-line {
            margin-bottom: 0.5rem;
            animation: type 0.5s ease;
        }
        
        .cursor {
            display: inline-block;
            width: 8px;
            height: 16px;
            background: var(--success);
            margin-left: 2px;
            animation: blink 1s infinite;
            vertical-align: middle;
        }
        
        .footer {
            margin-top: 3rem;
            color: var(--text-secondary);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .social-links a {
            color: var(--text-secondary);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }
        
        .social-links a:hover {
            color: var(--accent);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
        
        @keyframes shine {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }
        
        @keyframes type {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            h1 { font-size: 2.8rem; }
            .tagline { font-size: 1.3rem; }
            .status { font-size: 2rem; }
            .code-box { min-width: 100%; }
            .content-area { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="matrix-bg" id="matrixBg"></div>
    <div class="floating-shapes" id="floatingShapes"></div>
    
    <div class="container">
        <div class="header">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <div class="logo">
                <i class="fas fa-laptop-code logo-icon"></i>
            </div>
            <h1>DEV HUB</h1>
            <p class="tagline">Building the Future with Code</p>
        </div>
        
        <div class="status-container">
            <h2>ACTIVE CODING SESSION</h2>
            <div class="status">
                <span>WE ARE IN FLOW STATE</span>
                <div class="live-indicator">
                    <div class="live-dot"></div>
                    <span>LIVE</span>
                </div>
            </div>
            <p>Deep work in progress. Please respect the focus zone.</p>
        </div>
        
        <div class="content-area">
            <div class="code-box">
                <div class="code-header">
                    <div class="code-title">
                        <i class="fab fa-react"></i>
                        <span>UserProfile.jsx</span>
                    </div>
                    <div class="code-tabs">
                        <div class="code-tab active">Component</div>
                        <div class="code-tab">Styles</div>
                        <div class="code-tab">Tests</div>
                    </div>
                </div>
                <div class="code-content">
<span class="keyword">import</span> <span class="variable">React</span>, { <span class="variable">useState</span>, <span class="variable">useEffect</span> } <span class="keyword">from</span> <span class="string">'react'</span>;

<span class="keyword">const</span> <span class="function">UserProfile</span> = ({ <span class="variable">userId</span> }) => {
  <span class="keyword">const</span> [<span class="variable">user</span>, <span class="function">setUser</span>] = <span class="function">useState</span>(<span class="keyword">null</span>);
  <span class="keyword">const</span> [<span class="variable">loading</span>, <span class="function">setLoading</span>] = <span class="function">useState</span>(<span class="keyword">true</span>);

  <span class="function">useEffect</span>(() => {
    <span class="keyword">const</span> <span class="function">fetchUser</span> = <span class="keyword">async</span> () => {
      <span class="function">setLoading</span>(<span class="keyword">true</span>);
      <span class="keyword">try</span> {
        <span class="keyword">const</span> <span class="variable">response</span> = <span class="keyword">await</span> <span class="function">fetch</span>(<span class="string">`/api/users/</span>${<span class="variable">userId</span>}<span class="string">`</span>);
        <span class="keyword">const</span> <span class="variable">userData</span> = <span class="keyword">await</span> <span class="variable">response</span>.<span class="function">json</span>();
        <span class="function">setUser</span>(<span class="variable">userData</span>);
      } <span class="keyword">catch</span> (<span class="variable">error</span>) {
        <span class="function">console</span>.<span class="function">error</span>(<span class="string">'Failed to fetch user:'</span>, <span class="variable">error</span>);
      } <span class="keyword">finally</span> {
        <span class="function">setLoading</span>(<span class="keyword">false</span>);
      }
    };

    <span class="function">fetchUser</span>();
  }, [<span class="variable">userId</span>]);

  <span class="keyword">if</span> (<span class="variable">loading</span>) {
    <span class="keyword">return</span> <span class="variable">&lt;div</span> <span class="variable">className</span>=<span class="string">"loading"</span><span class="variable">&gt;</span>Loading...<span class="variable">&lt;/div&gt;</span>;
  }

  <span class="keyword">return</span> (
    <span class="variable">&lt;div</span> <span class="variable">className</span>=<span class="string">"user-profile"</span><span class="variable">&gt;</span>
      <span class="variable">&lt;img</span> <span class="variable">src</span>={<span class="variable">user</span>.<span class="variable">avatar</span>} <span class="variable">alt</span>={<span class="variable">user</span>.<span class="variable">name</span>} /&gt;
      <span class="variable">&lt;h2&gt;</span>{<span class="variable">user</span>.<span class="variable">name</span>}<span class="variable">&lt;/h2&gt;</span>
      <span class="variable">&lt;p&gt;</span>{<span class="variable">user</span>.<span class="variable">email</span>}<span class="variable">&lt;/p&gt;</span>
    <span class="variable">&lt;/div&gt;</span>
  );
};

<span class="keyword">export</span> <span class="keyword">default</span> <span class="variable">UserProfile</span>;
                </div>
            </div>
        </div>
        
        <div class="terminal">
            <div class="terminal-header">
                <i class="fas fa-terminal"></i>
                <span>dev-terminal — -zsh — 80x24</span>
            </div>
            <div class="terminal-content" id="terminalContent">
                <!-- Terminal lines will be added by JavaScript -->
            </div>
        </div>
        
        <div class="footer">
            <div>
                <p><i class="fas fa-info-circle"></i> For inquiries, contact: dev-lead@company.com</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-slack"></i></a>
                    <a href="#"><i class="fab fa-figma"></i></a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;
        
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light-mode');
            if (body.classList.contains('light-mode')) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        });

        // Create floating shapes in the background
        const floatingShapes = document.getElementById('floatingShapes');
        for (let i = 0; i < 10; i++) {
            const shape = document.createElement('div');
            shape.className = 'shape';
            const size = Math.random() * 80 + 20;
            shape.style.width = `${size}px`;
            shape.style.height = `${size}px`;
            shape.style.background = i % 3 === 0 ? 'var(--accent)' : i % 3 === 1 ? 'var(--success)' : '#d2a8ff';
            shape.style.left = `${Math.random() * 100}vw`;
            shape.style.animationDuration = `${Math.random() * 20 + 10}s`;
            shape.style.animationDelay = `${Math.random() * 5}s`;
            floatingShapes.appendChild(shape);
        }

        // Matrix background effect
        const matrixBg = document.getElementById('matrixBg');
        const chars = '01{}[]();<>/*-=+&|!~%^';
        const fontSize = 14;
        const columns = Math.floor(window.innerWidth / fontSize);
        
        for (let i = 0; i < columns; i++) {
            const column = document.createElement('div');
            column.style.position = 'absolute';
            column.style.top = '0';
            column.style.left = `${i * fontSize}px`;
            column.style.width = `${fontSize}px`;
            column.style.fontFamily = 'monospace';
            column.style.fontSize = `${fontSize}px`;
            column.style.color = 'rgba(88, 166, 255, 0.1)';
            column.style.whiteSpace = 'nowrap';
            matrixBg.appendChild(column);
            
            let position = 0;
            setInterval(() => {
                const char = chars[Math.floor(Math.random() * chars.length)];
                column.textContent = char;
                position += fontSize;
                if (position > window.innerHeight) {
                    position = 0;
                }
                column.style.top = `${position}px`;
            }, 100 + Math.random() * 100);
        }

        // Terminal simulation
        const terminalContent = document.getElementById('terminalContent');
        const terminalLines = [
            { text: "npm run dev", delay: 500 },
            { text: "> Starting development server...", delay: 800 },
            { text: "> Compiled successfully!", delay: 600 },
            { text: "> Webpack compiled in 1243ms", delay: 700 },
            { text: "> No issues found.", delay: 500 },
            { text: "> [HMR] Waiting for update signal...", delay: 900 }
        ];
        
        let currentLine = 0;
        
        function addTerminalLine() {
            if (currentLine < terminalLines.length) {
                const line = document.createElement('div');
                line.className = 'terminal-line';
                line.textContent = terminalLines[currentLine].text;
                terminalContent.appendChild(line);
                terminalContent.scrollTop = terminalContent.scrollHeight;
                currentLine++;
                setTimeout(addTerminalLine, terminalLines[currentLine - 1].delay);
            } else {
                // Add blinking cursor after all lines
                const cursorLine = document.createElement('div');
                cursorLine.className = 'terminal-line';
                cursorLine.innerHTML = '<span class="cursor"></span>';
                terminalContent.appendChild(cursorLine);
            }
        }
        
        // Start terminal animation
        setTimeout(addTerminalLine, 1000);
        
        // Tab switching functionality
        const tabs = document.querySelectorAll('.code-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });
        
        // Add subtle pulsing effect to the status
        const statusElement = document.querySelector('.status');
        setInterval(() => {
            statusElement.style.textShadow = `0 0 ${15 + Math.random() * 10}px var(--success-glow)`;
        }, 1000);
    </script>
</body>
</html>