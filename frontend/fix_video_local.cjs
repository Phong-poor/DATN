const fs = require('fs');
const path = 'c:/xampp/htdocs/DATN/frontend/src/components/Web/Home.vue';
let content = fs.readFileSync(path, 'utf8');

// Replace the video URLs
content = content.replace(
    /<video class="video-bg" autoplay loop muted playsinline>.*<\/video>/s,
    `<video class="video-bg" autoplay loop muted playsinline>
        <source src="/tech-video.mp4" type="video/mp4" />
    </video>`
);

fs.writeFileSync(path, content, 'utf8');
console.log('Replaced video with local tech video!');
