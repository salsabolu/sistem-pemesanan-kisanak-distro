const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walkDir(dirPath, callback) : callback(dirPath);
  });
}

walkDir('resources/js/pages/pemilik', function(filePath) {
  if (!filePath.endsWith('.vue')) return;
  
  let content = fs.readFileSync(filePath, 'utf8');
  
  if (content.includes('AdminSidebar')) return;

  const asideRegex = /<aside[\s\S]*?<\/aside>/;
  if (content.match(asideRegex)) {
    content = content.replace(asideRegex, '<AdminSidebar />');
  }

  const scriptRegex = /<script setup[^>]*>/;
  const match = content.match(scriptRegex);
  if (match) {
    const depth = filePath.replace(/\\/g, '/').split('/').length - 4; 
    let prefix = '../'.repeat(Math.max(1, depth));
    content = content.replace(match[0], match[0] + "\nimport AdminSidebar from '" + prefix + "components/AdminSidebar.vue';");
  }

  fs.writeFileSync(filePath, content);
});
console.log('Done');
