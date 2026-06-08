const fs = require('fs');
const path = require('path');

const dir = path.join(__dirname, 'frontend/src/components/Web');

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
    const original = code;
    
    // Remove " center" from section-header and label-wrapper in template
    code = code.replace(/class="([^"]*)section-header([^"]*)center([^"]*)"/g, 'class="$1section-header$2$3"');
    code = code.replace(/class="([^"]*)label-wrapper([^"]*)center([^"]*)"/g, 'class="$1label-wrapper$2$3"');
    
    // Fix section-header-center
    code = code.replace(/class="([^"]*)section-header-center([^"]*)"/g, 'class="$1section-header$2"');
    
    // Clean up multiple spaces
    code = code.replace(/class="([^"]*)\s\s+([^"]*)"/g, 'class="$1 $2"');
    code = code.replace(/class="\s+([^"]*)"/g, 'class="$1"');
    code = code.replace(/class="([^"]*)\s+"/g, 'class="$1"');

    // Also remove from CSS to avoid unused styles
    code = code.replace(/\.section-header\.center\s*{[^}]*}/g, '');
    code = code.replace(/\.label-wrapper\.center\s*{[^}]*}/g, '');
    
    // Convert .section-header-center to .section-header in CSS if it exists
    code = code.replace(/\.section-header-center/g, '.section-header');

    if (code !== original) {
        fs.writeFileSync(file, code, 'utf8');
        console.log("Updated: " + file);
    }
});

console.log("Removed center classes globally.");
