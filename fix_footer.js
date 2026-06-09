const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'frontend/src/components/Layout/Footer.vue');
let code = fs.readFileSync(file, 'utf8');

// 1. Remove glowing orbs and grids from HTML
code = code.replace(/<div class="glow-orb glow-cyan"><\/div>/g, '');
code = code.replace(/<div class="glow-orb glow-purple"><\/div>/g, '');
code = code.replace(/<div class="cyber-grid-overlay"><\/div>/g, '');
code = code.replace(/<span class="cyber-badge-glow">TECH<\/span>/g, '<span class="premium-badge-light">PREMIUM</span>');

// 2. Change live status widget to look clean and light
code = code.replace(/<span class="live-label">CORE SERVER: <span class="green-text">ONLINE<\/span><\/span>/g, '<span class="live-label">HỆ THỐNG: <span class="green-text">ONLINE</span></span>');

// 3. Remove soundwave animation
code = code.replace(/<div class="soundwave-container"[\s\S]*?<\/div>\s*<\/div>/g, '');

// 4. Update CSS styles for the footer to match light theme
code = code.replace(/background-color: var\(--tn-surface-2\);/g, 'background-color: #f8fafc;'); // light gray background
code = code.replace(/\.cyber-badge-glow \{[\s\S]*?\}/g, `.premium-badge-light {
  font-size: 10px;
  font-weight: 700;
  background: var(--tn-surface-2);
  color: var(--tn-primary);
  border: 1px solid var(--tn-border);
  padding: 3px 8px;
  border-radius: 6px;
  letter-spacing: 0.05em;
}`);

code = code.replace(/filter: drop-shadow\(0 0 12px rgba\(0, 242, 254, 0\.4\)\);/g, ''); // remove neon shadow on logo
code = code.replace(/animation: logoBreath 4s ease-in-out infinite alternate;/g, ''); 
code = code.replace(/@keyframes logoBreath \{[\s\S]*?\}/g, '');

code = code.replace(/\.live-pulse \{[\s\S]*?\}/g, `.live-pulse {
  width: 8px;
  height: 8px;
  background-color: var(--tn-green);
  border-radius: 50%;
}`);
code = code.replace(/@keyframes pulseDot \{[\s\S]*?\}/g, ''); // no pulse animation
code = code.replace(/\.green-text \{[\s\S]*?\}/g, `.green-text {
  color: var(--tn-green);
}`);
code = code.replace(/\.cyan-text \{[\s\S]*?\}/g, `.cyan-text {
  color: var(--tn-primary);
}`);

code = code.replace(/\.social-btn\.facebook \{[\s\S]*?\}/g, `.social-btn.facebook { background: #f1f5f9; color: #3b5998; border: 1px solid #e2e8f0; }`);
code = code.replace(/\.social-btn\.instagram \{[\s\S]*?\}/g, `.social-btn.instagram { background: #f1f5f9; color: #e1306c; border: 1px solid #e2e8f0; }`);
code = code.replace(/\.social-btn\.youtube \{[\s\S]*?\}/g, `.social-btn.youtube { background: #f1f5f9; color: #ff0000; border: 1px solid #e2e8f0; }`);
code = code.replace(/\.social-btn\.tiktok \{[\s\S]*?\}/g, `.social-btn.tiktok { background: #f1f5f9; color: #000000; border: 1px solid #e2e8f0; }`);
code = code.replace(/\.social-btn\.discord \{[\s\S]*?\}/g, `.social-btn.discord { background: #f1f5f9; color: #5865f2; border: 1px solid #e2e8f0; }`);

code = code.replace(/box-shadow: 0 4px 10px rgba\([\s\S]*?\);/g, 'box-shadow: none;'); // remove social button shadows
code = code.replace(/box-shadow: 0 8px 24px rgba\([\s\S]*?\);/g, 'box-shadow: 0 4px 12px rgba(0,0,0,0.05);'); // hover shadows

code = code.replace(/--neon-cyan/g, '--tn-primary');
code = code.replace(/filter: drop-shadow\(0 0 6px rgba\(0, 242, 254, 0\.4\)\);/g, '');

fs.writeFileSync(file, code, 'utf8');
console.log("Footer updated!");
