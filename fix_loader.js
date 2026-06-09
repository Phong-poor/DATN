const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'frontend/src/components/Layout/GlobalLoader.vue');
let code = fs.readFileSync(file, 'utf8');

// Change overlay background to light
code = code.replace(/background: radial-gradient\(circle at center, rgba\(15, 23, 42, 0\.75\) 0%, rgba\(8, 10, 15, 0\.96\) 100%\);/g, 
  'background: radial-gradient(circle at center, rgba(255, 255, 255, 0.75) 0%, rgba(248, 250, 252, 0.96) 100%);');

// Update text logo to light theme text color
code = code.replace(/background: linear-gradient\(135deg, #ffffff 30%, #93c5fd 100%\);/g, 
  'background: linear-gradient(135deg, #0f172a 30%, #2563eb 100%);');

// Update shadow for light theme
code = code.replace(/text-shadow: 0 1px 4px rgba\(0, 0, 0, 0\.3\);/g, 
  'text-shadow: 0 1px 4px rgba(255, 255, 255, 0.8);');

// Change loading-label gradient to match light theme
code = code.replace(/background: linear-gradient\(to right, #cbd5e1, #94a3b8\);/g, 
  'background: linear-gradient(to right, #64748b, #475569);');

fs.writeFileSync(file, code, 'utf8');
console.log("GlobalLoader updated!");
