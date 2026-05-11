const fs = require('fs');
const path = require('path');

const targetDropdown = `<template v-if="!$page.props.auth.user">
                                <button type="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5"
                                    @click="openLogin">
                                    Masuk
                                </button>
                            </template>
                            <template v-else>
                                <Link href="/profil/riwayat-pesanan"
                                    class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Profil
                                </Link>
                                <Link href="/logout" method="post" as="button" class="text-black w-full px-3 py-2 text-left hover:bg-black/5 block">
                                    Keluar
                                </Link>
                            </template>`;

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    if (isDirectory) {
      walkDir(dirPath, callback);
    } else {
      callback(dirPath);
    }
  });
}

walkDir('resources/js/pages', function(filePath) {
  if (!filePath.endsWith('.vue')) return;
  if (filePath.includes('pemilik')) return; // skip admin pages
  
  let content = fs.readFileSync(filePath, 'utf8');
  
  const dropdownRegex = /<button[^>]*@click="openLogin"[^>]*>[\s\S]*?<\/button>\s*<Link href="\/profil\/riwayat-pesanan"[^>]*>[\s\S]*?<\/Link>/;
  if (content.match(dropdownRegex)) {
    content = content.replace(dropdownRegex, targetDropdown);
    fs.writeFileSync(filePath, content);
    console.log(`Updated ${filePath}`);
  }
});
console.log('Done');
