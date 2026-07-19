const fs = require('fs');
const path = require('path');

const vueDir = path.join(__dirname, 'resources/js/Pages');

function traverse(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            traverse(fullPath);
        } else if (fullPath.endsWith('.vue')) {
            processFile(fullPath);
        }
    }
}

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Check if script setup exists
    if (!content.includes('<script setup>')) return;
    
    let changed = false;
    
    // Check for usePage
    if (!content.includes('usePage') && content.includes('@inertiajs/vue3')) {
        content = content.replace(/(import\s+{.*?)(\s*}\s+from\s+['"]@inertiajs\/vue3['"];?)/, (match, p1, p2) => {
            return p1 + ', usePage' + p2;
        });
        changed = true;
    } else if (!content.includes('usePage')) {
        content = content.replace(/<script setup>/, "<script setup>\nimport { usePage } from '@inertiajs/vue3';");
        changed = true;
    }
    
    // Check for can
    if (!content.includes('const can = (permission)')) {
        const canFunc = `
const can = (permission) => {
    return usePage().props.auth.permissions.includes(permission) || usePage().props.auth.roles.includes('admin');
};
`;
        // insert after imports
        content = content.replace(/(<script setup>[\s\S]*?import.*?from.*?;)/, `$1\n${canFunc}`);
        // fallback if no imports
        if (!content.includes(canFunc)) {
            content = content.replace(/<script setup>/, `<script setup>\n${canFunc}`);
        }
        changed = true;
    }
    
    if (changed) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Injected into ${filePath}`);
    }
}

traverse(vueDir);
