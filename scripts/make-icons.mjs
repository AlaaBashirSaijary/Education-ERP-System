// Generates the app icons (PWA, Apple touch icon, favicon) from the emblem below.
// Usage: npm run icons            (edit COLORS or the emblem() shapes to rebrand)
import sharp from 'sharp';
import { writeFile } from 'node:fs/promises';

const COLORS = { bg: '#0B2327', star: '#2BA699', centre: '#F5B301' };

/** The emblem (two squares forming an eight-pointed star + saffron centre) on a 48x48 grid. */
const emblem = () => `
  <rect x="9" y="9" width="30" height="30" rx="3" fill="${COLORS.star}"/>
  <rect x="9" y="9" width="30" height="30" rx="3" transform="rotate(45 24 24)" fill="${COLORS.star}" opacity=".55"/>
  <circle cx="24" cy="24" r="6.5" fill="${COLORS.centre}"/>`;

/**
 * @param size    canvas in px
 * @param scale   emblem size as a share of the canvas (smaller for maskable: Android may crop to a circle)
 * @param radius  corner radius as a share of the canvas (0 = full-bleed square)
 */
const svg = (size, scale, radius) => {
  const k = (size * scale) / 48;
  const o = (size - 48 * k) / 2;
  return Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
    <rect width="${size}" height="${size}" rx="${size * radius}" fill="${COLORS.bg}"/>
    <g transform="translate(${o} ${o}) scale(${k})">${emblem()}</g></svg>`);
};

const png = (name, size, scale, radius) => {
  let img = sharp(svg(size, scale, radius));
  if (radius === 0) img = img.flatten({ background: COLORS.bg }); // opaque: iOS and maskable icons must not be transparent
  return img.png({ compressionLevel: 9 }).toFile(`public/${name}`);
};

await Promise.all([
  png('icons/icon-192.png', 192, 0.66, 0.22),
  png('icons/icon-512.png', 512, 0.66, 0.22),
  png('icons/icon-maskable-512.png', 512, 0.5, 0),   // full-bleed; emblem inside the 80% safe zone
  png('apple-touch-icon.png', 180, 0.66, 0),          // iOS rounds the corners itself, so no transparency
  png('icons/favicon-32.png', 32, 0.8, 0.2),
  writeFile('public/favicon.svg', svg(64, 0.8, 0.2).toString().replace(/width="64" height="64" /, '')),
]);
console.log('Icons written to public/icons, public/apple-touch-icon.png and public/favicon.svg');
