document.addEventListener('DOMContentLoaded', () => {
    const bg = document.createElement('div');
    bg.className = 'bg-visuals';
    
    // Waves
    const wave1 = document.createElement('div');
    wave1.className = 'wave';
    const wave2 = document.createElement('div');
    wave2.className = 'wave';
    bg.appendChild(wave1);
    bg.appendChild(wave2);
    
    // Glitter
    const glitter = document.createElement('div');
    glitter.className = 'glitter';
    for (let i = 0; i < 40; i++) {
        const p = document.createElement('div');
        p.className = 'glitter-particle';
        const size = Math.random() * 2 + 1;
        p.style.width = size + 'px';
        p.style.height = size + 'px';
        p.style.top = Math.random() * 100 + '%';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDelay = Math.random() * 5 + 's';
        p.style.animationDuration = (Math.random() * 3 + 2) + 's';
        glitter.appendChild(p);
    }
    bg.appendChild(glitter);
    
    document.body.prepend(bg);
});
