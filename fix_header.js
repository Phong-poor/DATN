const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'frontend/src/components/Layout/Header.vue');
let code = fs.readFileSync(file, 'utf8');

// 1. Announcement bar
code = code.replace(/background: #0f172a;/g, 'background: var(--tn-primary);');

// 2. Action badge border
code = code.replace(/border: 2px solid #0d1b2e;/g, 'border: 2px solid #ffffff;');

// 3. Dropdowns
code = code.replace(/background: rgba\(13, 27, 46, 0\.65\);/g, 'background: rgba(255, 255, 255, 0.95);');
code = code.replace(/border: 1px solid rgba\(255, 255, 255, 0\.1\);/g, 'border: 1px solid var(--tn-border);');
code = code.replace(/box-shadow:[\s\S]*?inset 0 1px 1px rgba\(255, 255, 255, 0\.15\);/g, 'box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08), 0 4px 14px rgba(15, 23, 42, 0.04);');
code = code.replace(/border: 1px solid rgba\(255,255,255,0\.1\);/g, 'border: 1px solid var(--tn-border);');

// 4. Drop head/ttl
code = code.replace(/color: #f1f5f9;/g, 'color: var(--tn-text);');
code = code.replace(/border-bottom: 1px solid rgba\(255,255,255,0\.06\);/g, 'border-bottom: 1px solid var(--tn-border);');

// 5. Drop item / lists
code = code.replace(/background: rgba\(255,255,255,0\.03\);/g, 'background: var(--tn-surface-2);');
code = code.replace(/border: 1px solid rgba\(255,255,255,0\.05\);/g, 'border: 1px solid transparent;');
code = code.replace(/background: rgba\(255,255,255,0\.05\);/g, 'background: var(--tn-surface-2);');
code = code.replace(/border: 1px solid rgba\(255,255,255,0\.08\);/g, 'border: 1px solid var(--tn-border);');
code = code.replace(/color: #e2e8f0;/g, 'color: var(--tn-text);');
code = code.replace(/background: rgba\(255,255,255,0\.07\);/g, 'background: var(--tn-border);');

// 6. Drop footer
code = code.replace(/border-top: 1px solid rgba\(255,255,255,0\.06\);/g, 'border-top: 1px solid var(--tn-border);');
code = code.replace(/background: rgba\(255,255,255,0\.01\);/g, 'background: var(--tn-surface);');
code = code.replace(/background: rgba\(255,255,255,0\.07\);/g, 'background: var(--tn-border);');

// 7. um-item (User menu)
code = code.replace(/color: #bfdbfe;/g, 'color: var(--tn-primary);');

// 8. Mobile drawer
code = code.replace(/background: #0d1b2e;/g, 'background: var(--tn-surface);');
code = code.replace(/border-bottom: 1px solid #f1f5f9;/g, 'border-bottom: 1px solid var(--tn-border);');
code = code.replace(/color: #cbd5e1;/g, 'color: var(--tn-text);');

fs.writeFileSync(file, code, 'utf8');
console.log("Header updated!");
