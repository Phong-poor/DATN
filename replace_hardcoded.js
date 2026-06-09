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
    
    // Safely replace ONLY inside <style scoped> blocks
    const styleRegex = /<style scoped>([\s\S]*?)<\/style>/g;
    code = code.replace(styleRegex, (match, p1) => {
        let css = p1;
        
        // Convert pure white to our new pure white surface variable
        css = css.replace(/background(-color)?:\s*#ffffff;?/gi, 'background: var(--tn-surface);');
        
        // Convert pure #F8FAFC to our background variable
        css = css.replace(/background(-color)?:\s*#f8fafc;?/gi, 'background: var(--tn-bg);');
        
        // Convert #f1f5f9 to surface-2
        css = css.replace(/background(-color)?:\s*#f1f5f9;?/gi, 'background: var(--tn-surface-2);');
        
        // Soften hard borders
        css = css.replace(/border:\s*1px\s+solid\s+#e2e8f0;?/gi, 'border: 1px solid var(--tn-border);');

        return `<style scoped>\n${css}</style>`;
    });

    fs.writeFileSync(file, code, 'utf8');
});

console.log("Safe color variable conversion complete.");
