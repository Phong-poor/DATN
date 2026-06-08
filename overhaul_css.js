const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'frontend/src/style.css');
let code = fs.readFileSync(file, 'utf8');

// Ensure section and grid-container are universally defined in style.css
const globalLayout = `
/* ============================================================
   GLOBAL LAYOUT & SPACING SYSTEM
   ============================================================ */
.section {
  padding: 80px 0;
  background-color: var(--tn-bg);
}

.section-alt {
  padding: 80px 0;
  background-color: var(--tn-surface);
}

.grid-container,
.container {
  max-width: 1300px;
  width: 100%;
  margin: 0 auto;
  padding: 0 24px;
}

/* Section Headers */
.section-header {
  margin-bottom: 48px;
  text-align: center;
}

.section-header h2 {
  font-size: 32px;
  font-weight: 800;
  color: var(--tn-text);
  margin-bottom: 12px;
}

.section-header p {
  font-size: 16px;
  color: var(--tn-text-muted);
  max-width: 600px;
  margin: 0 auto;
}

.ambient-label {
  font-size: 12px;
  font-weight: 800;
  color: var(--tn-primary);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 8px;
  display: inline-block;
}

/* Generic Premium Cards */
.card-premium {
  background: var(--tn-surface);
  border: 1px solid var(--tn-border);
  border-radius: 16px;
  padding: 24px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
}

.card-premium:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
  border-color: var(--tn-border-hover);
}

/* Typography Enhancements */
h1, h2, h3, h4, h5, h6 {
  color: var(--tn-text);
  font-weight: 800;
}
p, span, div {
  color: var(--tn-text-muted);
}
`;

if (!code.includes('GLOBAL LAYOUT & SPACING SYSTEM')) {
    code += '\n' + globalLayout;
}

fs.writeFileSync(file, code, 'utf8');
console.log("style.css updated with global layout system.");
