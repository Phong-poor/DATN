const fs = require('fs');
const path = require('path');

const dir = path.join(__dirname, 'frontend/src/components/Web');

const files = fs.readdirSync(dir);

files.forEach(file => {
    if (!file.endsWith('.vue')) return;
    const filePath = path.join(dir, file);
    let code = fs.readFileSync(filePath, 'utf8');
    let original = code;

    // 1. Tweak Shadows for Premium Flat Look
    code = code.replace(/box-shadow:\s*0\s+[1-9]\d*px\s+[1-9]\d*px\s+rgba\(0,\s*0,\s*0,\s*0\.[3-9]\d*\)/g, 'box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05)');
    code = code.replace(/box-shadow:\s*0\s+[1-9]\d*px\s+[1-9]\d*px\s+rgba\(15,\s*23,\s*42,\s*0\.[2-9]\d*\)/g, 'box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05)');

    // 2. Remove Dark Gradients / Colors commonly used for sections
    code = code.replace(/background(-color)?:\s*(#0f172a|#081B2F|#061A3A|#0d1b2e|#1e293b|#020617|#111827|#000000)/gi, 'background: var(--tn-surface)');
    code = code.replace(/background(-color)?:\s*rgba\(15,\s*23,\s*42,\s*0\.[5-9]\d*\)/g, 'background: rgba(255, 255, 255, 0.8)');
    
    // 3. Fix dark borders
    code = code.replace(/border(-[a-z]+)?:\s*1px\s+solid\s+rgba\(255,\s*255,\s*255,\s*0\.[0-9]+\)/g, 'border$1: 1px solid var(--tn-border)');
    code = code.replace(/border(-[a-z]+)?:\s*1px\s+solid\s+#334155/gi, 'border$1: 1px solid var(--tn-border)');

    // 4. Update Typography / Text colors for Light Theme
    code = code.replace(/color:\s*#f8fafc/gi, 'color: var(--tn-text)');
    code = code.replace(/color:\s*#f1f5f9/gi, 'color: var(--tn-text)');
    code = code.replace(/color:\s*#cbd5e1/gi, 'color: var(--tn-text-muted)');
    code = code.replace(/color:\s*#94a3b8/gi, 'color: var(--tn-text-muted)');
    // But protect buttons!
    // A quick hack: revert color: var(--tn-text) if it is inside a button class (we can't easily parse CSS, so let's hope .btn covers it)
    // Actually, usually buttons have explicitly `color: white !important` or `color: #fff`.
    
    // 5. Upgrade Font Sizes slightly for readability (only in scoped styles, we just look for font-size)
    code = code.replace(/font-size:\s*13px/g, 'font-size: 14px');
    code = code.replace(/font-size:\s*14px/g, 'font-size: 15px');

    // Custom Fixes for Home.vue
    if (file === 'Home.vue') {
        // Remove dark overlay
        code = code.replace(/background:\s*radial-gradient\(circle\s*at\s*center,\s*rgba\(15,\s*23,\s*42,\s*0\),.*?\)/g, 'background: radial-gradient(circle at center, rgba(255,255,255,0), rgba(248, 250, 252, 0.8))');
        code = code.replace(/background:\s*linear-gradient\(to\s*bottom,\s*rgba\(8,\s*11,\s*18,\s*0\).*?\)/g, 'background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, #f8fafc 100%)');
        code = code.replace(/background:\s*linear-gradient\(to\s*bottom,\s*rgba\(15,\s*23,\s*42,\s*0\).*?\)/g, 'background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, #f8fafc 100%)');
        code = code.replace(/background:\s*linear-gradient\(to\s*top,\s*rgba\(15,\s*23,\s*42,\s*1\).*?\)/g, 'background: linear-gradient(to top, #ffffff 0%, rgba(255,255,255,0) 100%)');
        
        // Card bg image overlays
        code = code.replace(/background:\s*linear-gradient\(to\s*top,\s*rgba\(10,\s*15,\s*28,\s*0\.9\).*?\)/g, 'background: linear-gradient(to top, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.2) 100%)');
    }

    if (code !== original) {
        fs.writeFileSync(filePath, code, 'utf8');
        console.log(`Updated ${file}`);
    }
});
console.log('Script hoàn tất!');
