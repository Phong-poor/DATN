const fs = require('fs');
let code = fs.readFileSync('frontend/src/components/Layout/Footer.vue', 'utf8');

// 1. Remove background dark colors and gradients
code = code.replace(/background-color:\s*var\(--cyber-dark-bg\);/g, 'background-color: #f8fafc;');
code = code.replace(/--cyber-dark-bg:\s*#070a13;/g, '--cyber-dark-bg: var(--tn-bg);');
code = code.replace(/--cyber-card-bg:\s*rgba\(11,\s*16,\s*29,\s*0\.65\);/g, '--cyber-card-bg: var(--tn-surface);');
code = code.replace(/--text-primary:\s*#ffffff;/g, '--text-primary: var(--tn-text);');
code = code.replace(/--border-light:\s*rgba\(255,\s*255,\s*255,\s*0\.05\);/g, '--border-light: var(--tn-border);');
code = code.replace(/border-top:\s*1px\s+solid\s+rgba\(0,\s*242,\s*254,\s*0\.15\);/g, 'border-top: 1px solid var(--tn-border);');

// 2. Hide Neon Glows safely
code = code.replace(/\.cyber-grid-overlay\s*\{/g, '.cyber-grid-overlay {\n  display: none;');
code = code.replace(/\.glow-orb\s*\{/g, '.glow-orb {\n  display: none;');
code = code.replace(/\.glow-cyan\s*\{/g, '.glow-cyan {\n  display: none;');
code = code.replace(/\.glow-purple\s*\{/g, '.glow-purple {\n  display: none;');

// 3. Fix badge
code = code.replace(/<span class="cyber-badge-glow">TECH<\/span>/g, '<span class="premium-badge-light">PREMIUM</span>');

// Replace the CSS of cyber-badge-glow safely
// Since it has no nested braces, we can just replace everything until the first }
code = code.replace(/\.cyber-badge-glow\s*\{[^}]*\}/g, `.premium-badge-light {
  font-size: 10px;
  font-weight: 700;
  background: var(--tn-surface-2);
  color: var(--tn-primary);
  border: 1px solid var(--tn-border);
  padding: 3px 8px;
  border-radius: 6px;
  letter-spacing: 0.05em;
}`);

// 4. Clean up logo animations safely
code = code.replace(/filter:\s*drop-shadow\(0\s*0\s*12px\s*rgba\(0,\s*242,\s*254,\s*0\.4\)\);/g, '');
code = code.replace(/animation:\s*logoBreath\s*4s\s*ease-in-out\s*infinite\s*alternate;/g, '');
// Note: we leave @keyframes logoBreath intact to avoid parsing issues. It won't hurt to have unused keyframes.

fs.writeFileSync('frontend/src/components/Layout/Footer.vue', code, 'utf8');
console.log("Footer restored and safely updated to Light Theme.");
