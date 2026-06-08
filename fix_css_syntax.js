const fs = require('fs');
const path = require('path');

const dir = path.join(__dirname, 'frontend/src/components');

function walk(directory) {
    let results = [];
    const list = fs.readdirSync(directory);
    list.forEach(file => {
        const filePath = path.join(directory, file);
        const stat = fs.statSync(filePath);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(filePath));
        } else if (file.endsWith('.vue')) {
            results.push(filePath);
        }
    });
    return results;
}

const vueFiles = walk(dir);

vueFiles.forEach(file => {
    let code = fs.readFileSync(file, 'utf8');
    
    // Fix the invalid CSS syntax created by the previous script
    // E.g. "background: var(--tn-surface); !important;" -> "background: var(--tn-surface) !important;"
    const original = code;
    code = code.replace(/;\s*!important;/g, ' !important;');
    
    if (code !== original) {
        fs.writeFileSync(file, code, 'utf8');
    }
});

console.log("Fixed CSS syntax errors.");
