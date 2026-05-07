const fs = require('fs');
const pages = [
  'resources/js/pages/Galeri.vue',
  'resources/js/pages/Keranjang.vue',
  'resources/js/pages/profil/EditProfil.vue',
  'resources/js/pages/profil/RiwayatPesanan.vue',
  'resources/js/pages/profil/UbahKataSandi.vue',
];
pages.forEach(p => {
  let c = fs.readFileSync(p, 'utf8');
  // Add Footer import if not present
  if (!c.includes("import Footer from")) {
    c = c.replace(/(import (?:CartDrawer|LoginModal)[^\n\r]+[\n\r]+)/, (m) => m + "import Footer from '@/components/Footer.vue';\n");
  }
  // Replace inline footer with Footer component
  c = c.replace(/[ \t]*<!-- Footer -->[\s\S]*?<\/footer>/g, '        <!-- Footer -->\n        <Footer />');
  fs.writeFileSync(p, c);
  console.log('Updated', p);
});
