import * as THREE from 'three';

// One field of small blue blocks that re-forms into a different shape per section.
// Every shape uses the same particle count, so a scroll position between two sections
// is just a blend of two target arrays (plus a burst outward mid-way).
//
//   0 sphere  - hero: a ball of blocks with three orbiting chains of linked blocks
//   1 coin    - paylater: a TLKM coin with the outlined "TLKM" wordmark behind it
//   2 scatter - problem section: loose blocks, "funds you can't trace"
//   3 logo    - closing CTA: the E-Trace mark

// Blue anchors the palette, but shapes run through a gradient of amber, violet, blue,
// cyan and teal (like the reference), with a sprinkle of loose accents, so the field
// never reads as one flat blue mass.
const SPECTRUM = ['#f59e0b', '#8b5cf6', '#2563eb', '#06b6d4', '#14b8a6'].map((h) => new THREE.Color(h));
const ACCENTS = ['#f59e0b', '#fbbf24', '#a78bfa', '#22d3ee', '#2563eb', '#94a3b8'];
const _g = new THREE.Color();
function gradient(t, from = 0, to = 1) {
    const u = from + (to - from) * Math.min(1, Math.max(0, t));
    const f = u * (SPECTRUM.length - 1);
    const i = Math.min(SPECTRUM.length - 2, Math.floor(f));
    return '#' + _g.copy(SPECTRUM[i]).lerp(SPECTRUM[i + 1], f - i).getHexString();
}
const LOGO_PATHS = [
    'M 392.4 141.1 A 150 150 0 1 0 392.4 370.9',
    'M 100 212 H 398',
    'M 300 212 V 326',
    'M 100 302 H 218',
];

// Opening convergence: length of the fly-in, and how much of it is spread as per-block delay.
const INTRO_SECONDS = 3.2;
const INTRO_STAGGER = 0.4;

const rand = (a, b) => a + Math.random() * (b - a);
const pick = (arr) => arr[(Math.random() * arr.length) | 0];
const smooth = (a, b, x) => {
    const t = Math.min(1, Math.max(0, (x - a) / (b - a)));
    return t * t * (3 - 2 * t);
};

// Filled pixels of a 2D drawing, as [x, y, r, g, b, a]; minAlpha sets how faint a
// pixel may be and still count as filled.
function sampleCanvas(w, h, draw, step = 2, minAlpha = 140) {
    const c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    const ctx = c.getContext('2d');
    draw(ctx, w, h);
    const d = ctx.getImageData(0, 0, w, h).data;
    const pts = [];
    for (let y = 0; y < h; y += step) {
        for (let x = 0; x < w; x += step) {
            const i = (y * w + x) * 4;
            if (d[i + 3] > minAlpha) pts.push([x, y, d[i], d[i + 1], d[i + 2], d[i + 3]]);
        }
    }
    return pts;
}

// n points spread evenly over the raster-ordered samples (random picks clump and leave gaps).
function takeEven(pts, n) {
    const out = new Array(n);
    const step = pts.length / n;
    for (let i = 0; i < n; i++) out[i] = pts[Math.min(pts.length - 1, Math.floor(i * step + Math.random() * step))];
    return out;
}

class Shape {
    constructor(n) {
        this.n = n;
        this.pos = new Float32Array(n * 3);
        this.col = new Float32Array(n * 3);
        this.i = 0;
        this._c = new THREE.Color();
    }
    push(x, y, z, color) {
        const k = this.i * 3;
        this.pos[k] = x;
        this.pos[k + 1] = y;
        this.pos[k + 2] = z;
        if (typeof color === 'string') this._c.set(color);
        else this._c.setRGB(color[0] / 255, color[1] / 255, color[2] / 255, THREE.SRGBColorSpace);
        this.col[k] = this._c.r;
        this.col[k + 1] = this._c.g;
        this.col[k + 2] = this._c.b;
        this.i++;
    }
    get left() {
        return this.n - this.i;
    }
}

function sphereShape(n) {
    const s = new Shape(n);
    s.links = [];

    // Three tilted orbits, each a closed chain of 2x2x2-cube "blocks".
    const rings = [
        new THREE.Euler(0.35, 0, 0.2),
        new THREE.Euler(-0.55, 0.9, 0),
        new THREE.Euler(1.2, -0.4, 0.5),
    ];
    const BLOCKS = 9;
    const v = new THREE.Vector3();
    rings.forEach((euler, r) => {
        let first = -1;
        let prev = -1;
        for (let b = 0; b < BLOCKS; b++) {
            const a = (b / BLOCKS) * Math.PI * 2 + r * 0.7;
            v.set(Math.cos(a) * 3.75, 0, Math.sin(a) * 3.75).applyEuler(euler);
            const head = s.i;
            for (let q = 0; q < 8; q++) {
                s.push(
                    v.x + (q & 1 ? 0.08 : -0.08),
                    v.y + (q & 2 ? 0.08 : -0.08),
                    v.z + (q & 4 ? 0.08 : -0.08),
                    q % 3 ? '#1d4ed8' : '#1e3a8a',
                );
            }
            if (prev >= 0) s.links.push([prev, head]);
            if (first < 0) first = head;
            prev = head;
        }
        s.links.push([prev, first]);
    });

    // Shell of blocks (Fibonacci sphere) plus a lighter core.
    const shell = Math.floor(s.left * 0.8);
    const golden = Math.PI * (3 - Math.sqrt(5));
    for (let k = 0; k < shell; k++) {
        const y = 1 - (k / (shell - 1)) * 2;
        const r = Math.sqrt(1 - y * y);
        const th = golden * k;
        const R = 3 + rand(-0.06, 0.06);
        const x = Math.cos(th) * r;
        // Diagonal gradient across the ball, amber at the top through to teal at the bottom.
        const t = 0.5 - 0.5 * (y * 0.75 + x * 0.25) + rand(-0.1, 0.1);
        s.push(x * R, y * R, Math.sin(th) * r * R, Math.random() < 0.16 ? pick(ACCENTS) : gradient(t));
    }
    while (s.left > 0) {
        v.set(rand(-1, 1), rand(-1, 1), rand(-1, 1)).normalize().multiplyScalar(2.6 * Math.cbrt(Math.random()));
        s.push(v.x, v.y, v.z, gradient(0.5 - v.y / 5.2 + rand(-0.15, 0.15)));
    }
    return s;
}

function scatterShape(n) {
    const s = new Shape(n);
    while (s.left > 0) {
        // Pushed well behind the page so the blocks read as small, distant texture
        // rather than noise sitting on top of the copy.
        // x/y are scaled by distance from the camera (z = 16, 35deg fov, ~16:9) so every
        // depth layer covers the full screen evenly, edge to edge.
        const z = rand(-14, -3);
        const u = rand(-1, 1);
        const reach = (16 - z) * 0.315;
        s.push(u * reach * 1.85, rand(-1, 1) * reach * 1.05, z, Math.random() < 0.3 ? pick(ACCENTS) : gradient((u + 1) / 2 + rand(-0.2, 0.2)));
    }
    return s;
}

function coinShape(n, font) {
    const s = new Shape(n);
    const R = 2.5;
    const T = 0.5;

    // Outlined TLKM wordmark, three lines, flat behind the coin.
    const text = sampleCanvas(1100, 860, (ctx, w, h) => {
        ctx.font = `800 270px ${font}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.lineWidth = 11;
        ctx.strokeStyle = '#e5121f';
        [0.2, 0.5, 0.8].forEach((f) => ctx.strokeText('TLKM', w / 2, h * f));
    });
    takeEven(text, Math.floor(n * 0.26)).forEach(([x, y]) =>
        s.push((x / 1100 - 0.5) * 8.6, -(y / 860 - 0.5) * 7, -1.8 + rand(-0.05, 0.05), '#e5121f'),
    );

    // Coin face: light disc, red open-book mark and a dark "TS" monogram.
    const face = sampleCanvas(600, 600, (ctx, w) => {
        const c = w / 2;
        ctx.fillStyle = '#a3b1c6';
        ctx.beginPath();
        ctx.arc(c, c, c - 2, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#6b7c96';
        ctx.lineWidth = 16;
        ctx.beginPath();
        ctx.arc(c, c, c - 48, 0, Math.PI * 2);
        ctx.stroke();
        ctx.fillStyle = '#e5121f';
        ctx.beginPath();
        ctx.moveTo(125, 180); ctx.lineTo(300, 208); ctx.lineTo(475, 150);
        ctx.lineTo(475, 222); ctx.lineTo(300, 282); ctx.lineTo(125, 252);
        ctx.closePath();
        ctx.fill();
        ctx.fillStyle = '#1e293b';
        ctx.font = `800 300px ${font}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('TS', c, 410);
    });
    const tilt = new THREE.Euler(0.1, 0.36, -0.04); // turned toward the copy on the right
    const v = new THREE.Vector3();
    const put = (x, y, z, color) => {
        v.set(x, y, z).applyEuler(tilt);
        s.push(v.x, v.y, v.z, color);
    };
    takeEven(face, Math.floor(n * 0.56)).forEach(([x, y, r, g, b]) =>
        put((x / 600 - 0.5) * 2 * R, -(y / 600 - 0.5) * 2 * R, T / 2, [r, g, b]),
    );

    // Rim, then the back face.
    const rim = Math.floor(n * 0.11);
    for (let k = 0; k < rim; k++) {
        const a = rand(0, Math.PI * 2);
        put(Math.cos(a) * R, Math.sin(a) * R, rand(-T / 2, T / 2), '#6b7c96');
    }
    while (s.left > 0) {
        const a = rand(0, Math.PI * 2);
        const r = Math.sqrt(Math.random()) * R;
        put(Math.cos(a) * r, Math.sin(a) * r, -T / 2, '#a3b1c6');
    }
    return s;
}

// A 3D warning sign for the problem scene: a rounded red triangle with a raised white
// exclamation mark and three "alert" dashes off its top-right corner, tilted like a
// sticker-style 3D icon.
function warningShape(n) {
    const s = new Shape(n);
    const W = 600;
    const tri = (ctx) => {
        ctx.beginPath();
        ctx.moveTo(300, 90);
        ctx.lineTo(95, 480);
        ctx.lineTo(505, 480);
        ctx.closePath();
    };
    const body = (ctx) => {
        const g = ctx.createLinearGradient(0, 90, 0, 480);
        g.addColorStop(0, '#f87171');
        g.addColorStop(1, '#dc2626');
        ctx.fillStyle = g;
        ctx.strokeStyle = g;
        ctx.lineWidth = 70;
        ctx.lineJoin = 'round';
        tri(ctx);
        ctx.fill();
        ctx.stroke();
    };

    // Front face: body, exclamation mark and the alert dashes.
    const front = sampleCanvas(W, W, (ctx) => {
        body(ctx);
        ctx.strokeStyle = '#ffffff';
        ctx.lineCap = 'round';
        ctx.lineWidth = 50;
        ctx.beginPath();
        ctx.moveTo(300, 200);
        ctx.lineTo(300, 325);
        ctx.stroke();
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(300, 420, 30, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth = 26;
        [[425, 70, 440, 18], [470, 110, 520, 72], [492, 168, 548, 158]].forEach(([x1, y1, x2, y2]) => {
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        });
    });

    // Outline of the body only, for the thickness around the edge.
    const mask = document.createElement('canvas');
    mask.width = mask.height = W;
    const mctx = mask.getContext('2d');
    body(mctx);
    const md = mctx.getImageData(0, 0, W, W).data;
    const solid = (x, y) => x >= 0 && y >= 0 && x < W && y < W && md[(y * W + x) * 4 + 3] > 140;
    const edge = [];
    for (let y = 0; y < W; y += 2) {
        for (let x = 0; x < W; x += 2) {
            if (solid(x, y) && (!solid(x + 4, y) || !solid(x - 4, y) || !solid(x, y + 4) || !solid(x, y - 4))) edge.push([x, y]);
        }
    }

    const T = 0.6;
    const tilt = new THREE.Euler(0.12, -0.38, 0.2);
    const v = new THREE.Vector3();
    const put = (px, py, z, color) => {
        v.set((px / W - 0.5) * 5.4, -(py / W - 0.5) * 5.4, z).applyEuler(tilt);
        s.push(v.x, v.y, v.z, color);
    };

    takeEven(front, Math.floor(n * 0.58)).forEach(([x, y, r, g, b]) => {
        // The white mark sits proud of the face.
        const white = r > 230 && g > 230 && b > 230;
        put(x, y, T / 2 + (white ? 0.1 : 0), [r, g, b]);
    });
    takeEven(edge, Math.floor(n * 0.28)).forEach(([x, y]) => put(x, y, rand(-T / 2, T / 2), '#b91c1c'));
    const back = sampleCanvas(W, W, body);
    takeEven(back, s.left).forEach(([x, y, r, g, b]) => put(x, y, -T / 2, [r * 0.8, g * 0.8, b * 0.8]));
    return s;
}

// A flat pixel mosaic of a photo: the image is sampled on a grid sized so its opaque
// pixels roughly match the particle count, and each particle takes one pixel's colour.
// Transparent pixels are skipped; spare particles are parked at the centre with zero
// size (pix = 0). Rendering straightens the cubes for this shape so they read as pixels.
function imageShape(img, n, width) {
    const aspect = img.naturalWidth / img.naturalHeight;
    const probe = sampleCanvas(200, Math.round(200 / aspect), (ctx, w, h) => ctx.drawImage(img, 0, 0, w, h), 1);
    const coverage = probe.length / (200 * Math.round(200 / aspect));
    // Colours come from the sharp image; coverage from a slightly blurred copy, which
    // feathers the silhouette into a few rings of partially covered edge pixels.
    // Only pixels within two cells of real transparency count as rim; everything inside
    // the coins stays a full, still block (dark shading inside must never read as a hole).
    const sampleAt = (cols, rows) => {
        const pts = sampleCanvas(cols, rows, (ctx, w, h) => ctx.drawImage(img, 0, 0, w, h), 1, 0);
        const sharp = new Uint8Array(cols * rows);
        pts.forEach(([x, y, , , , a]) => { sharp[y * cols + x] = a; });
        const soft = new Uint8Array(cols * rows);
        sampleCanvas(cols, rows, (ctx, w, h) => {
            ctx.filter = 'blur(1.1px)';
            ctx.drawImage(img, 0, 0, w, h);
        }, 1, -1).forEach(([x, y, , , , a]) => { soft[y * cols + x] = a; });
        const nearOutside = (x, y) => {
            for (let dy = -2; dy <= 2; dy++) {
                for (let dx = -2; dx <= 2; dx++) {
                    const nx = x + dx, ny = y + dy;
                    if (nx < 0 || ny < 0 || nx >= cols || ny >= rows || sharp[ny * cols + nx] < 30) return true;
                }
            }
            return false;
        };
        return pts
            .map((p) => { p[5] = nearOutside(p[0], p[1]) ? Math.min(p[5], soft[p[1] * cols + p[0]]) : 255; return p; })
            .filter((p) => p[5] >= 30);
    };
    // 3D build: the face pixels, plus RIM_LAYERS copies of each outline pixel stepped
    // back in depth to give every coin a solid edge. The grid is sized so faces and rims
    // together fit the particle count; every face pixel always gets a particle (a
    // dropped pixel shows as a hole), so an oversized grid is shrunk and resampled.
    const RIM_LAYERS = 2;
    const layout = (pts, cols, rows) => {
        const at = new Map(pts.map((p) => [p[1] * cols + p[0], p]));
        const rim = pts.filter(([x, y]) => !at.has(y * cols + x + 1) || !at.has(y * cols + x - 1) || !at.has((y + 1) * cols + x) || !at.has((y - 1) * cols + x));
        return { at, rim, total: pts.length + rim.length * RIM_LAYERS };
    };
    let cols = Math.round(Math.sqrt((n * 0.7) / coverage * aspect));
    let rows = Math.round(cols / aspect);
    let used = sampleAt(cols, rows);
    let grid = layout(used, cols, rows);
    for (let tries = 0; grid.total > n && tries < 8; tries++) {
        cols = Math.floor(cols * Math.sqrt(n / grid.total) * 0.99);
        rows = Math.round(cols / aspect);
        used = sampleAt(cols, rows);
        grid = layout(used, cols, rows);
    }

    // Each coin gets its own depth: flood-fill the pixel grid into connected coins, keep
    // the biggest at the centre plane and push the others in front of / behind it.
    const coin = new Int32Array(cols * rows).fill(-1);
    const sizes = [];
    used.forEach(([x0, y0]) => {
        if (coin[y0 * cols + x0] >= 0) return;
        const id = sizes.length;
        let size = 0;
        const stack = [[x0, y0]];
        coin[y0 * cols + x0] = id;
        while (stack.length) {
            const [x, y] = stack.pop();
            size++;
            for (let dy = -1; dy <= 1; dy++) {
                for (let dx = -1; dx <= 1; dx++) {
                    const k = (y + dy) * cols + (x + dx);
                    if (grid.at.has(k) && coin[k] < 0) { coin[k] = id; stack.push([x + dx, y + dy]); }
                }
            }
        }
        sizes.push(size);
    });
    const order = sizes.map((_, i) => i).sort((p, q) => sizes[q] - sizes[p]);
    const depthOf = new Float32Array(sizes.length);
    order.forEach((id, rank) => { depthOf[id] = rank === 0 ? 0 : (rank % 2 ? 1 : -1) * 0.9; });

    const s = new Shape(n);
    s.pixel = true;
    s.pix = new Float32Array(n);
    s.pixelSize = (width / cols) * 0.95;
    const height = width / aspect;
    const step = width / cols;
    // Lighting on the blocks greys out pure white, so colours are lifted to read as the
    // white coins in the source image.
    const LIFT = 1.32;
    const lift = (r, g, b) => [Math.min(255, r * LIFT), Math.min(255, g * LIFT), Math.min(255, b * LIFT)];
    used.forEach(([x, y, r, g, b, a]) => {
        if (s.left <= 0) return;
        const z = depthOf[coin[y * cols + x]];
        // The logo (red book, grey "TS") sits proud of the white face.
        const lum = (0.3 * r + 0.59 * g + 0.11 * b) / 255;
        const relief = step * 1.6 * (1 - smooth(0.55, 0.85, lum));
        // Anti-aliasing in blocks: a half-covered edge pixel becomes a smaller block,
        // so the coins' rims round off instead of stepping like a staircase.
        s.pix[s.i] = 0.3 + 0.7 * smooth(30, 235, a);
        s.push((x / cols - 0.5) * width, -(y / rows - 0.5) * height, z + relief, lift(r, g, b));
    });
    grid.rim.forEach(([x, y]) => {
        const z = depthOf[coin[y * cols + x]];
        for (let l = 1; l <= RIM_LAYERS && s.left > 0; l++) {
            s.pix[s.i] = 1;
            s.push((x / cols - 0.5) * width, -(y / rows - 0.5) * height, z - l * step * 1.6, l === 1 ? '#e5e7eb' : '#cbd5e1');
        }
    });
    while (s.left > 0) s.push(0, 0, 0, '#ffffff');
    return s;
}

// Warm-to-cool ramp for the idea bulb: amber glass at the top down to a cyan screw base.
// Saturated on purpose: pale peach and lilac vanish against the light page.
const BULB = ['#f59e0b', '#f59e0b', '#f97316', '#a855f7', '#7c3aed', '#0891b2'].map((h) => new THREE.Color(h));
function bulbColor(t) {
    const f = Math.min(1, Math.max(0, t)) * (BULB.length - 1);
    const i = Math.min(BULB.length - 2, Math.floor(f));
    return '#' + _g.copy(BULB[i]).lerp(BULB[i + 1], f - i).getHexString();
}

// A tilted light bulb: round glass, tapering neck, ridged screw base and a glowing filament.
function bulbShape(n) {
    const s = new Shape(n);
    const TOP = 3.0;
    const BOTTOM = -3.2;
    const radiusAt = (y) => {
        if (y >= -0.6) return Math.sqrt(Math.max(0, 2.1 * 2.1 - (y - 0.9) ** 2));
        if (y >= -1.7) return 1.47 + ((y + 0.6) / -1.1) * (0.85 - 1.47);
        if (y >= -2.9) return 0.85 + 0.07 * Math.sin((y + 1.7) * 16);
        return 0.85 - ((y + 2.9) / -0.3) * 0.55;
    };
    const tilt = new THREE.Euler(0.15, 0.25, -0.38);
    const v = new THREE.Vector3();
    const put = (x, y, z, color) => {
        v.set(x, y, z).applyEuler(tilt);
        s.push(v.x, v.y, v.z, color);
    };

    // Filament: a small zigzag coil inside the glass.
    const fil = Math.floor(n * 0.07);
    for (let k = 0; k < fil; k++) {
        const u = k / fil;
        const y = -0.5 + u * 1.6;
        const x = Math.sin(u * Math.PI * 10) * 0.35;
        put(x + rand(-0.03, 0.03), y, rand(-0.05, 0.05), Math.random() < 0.5 ? '#f97316' : '#f59e0b');
    }

    // Surface of revolution, sampled so density stays even where the radius shrinks.
    while (s.left > 0) {
        const y = rand(BOTTOM, TOP);
        const r = radiusAt(y);
        if (Math.random() * 2.1 > r) continue;
        const a = rand(0, Math.PI * 2);
        const rr = r + rand(-0.04, 0.04);
        put(Math.cos(a) * rr, y, Math.sin(a) * rr, bulbColor((TOP - y) / (TOP - BOTTOM) + rand(-0.05, 0.05)));
    }
    return s;
}

function logoShape(n) {
    const s = new Shape(n);
    const pts = sampleCanvas(600, 600, (ctx, w) => {
        ctx.scale(w / 512, w / 512);
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 46;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        LOGO_PATHS.forEach((d) => ctx.stroke(new Path2D(d)));
    });
    // The mark stays in the cool end (violet -> blue -> cyan) so it still reads as E-Trace.
    takeEven(pts, n).forEach(([x, y]) => {
        const t = (x + y) / 1200 + rand(-0.06, 0.06);
        s.push((x / 600 - 0.5) * 6.4, -(y / 600 - 0.5) * 6.4, rand(-0.35, 0.35), gradient(t, 0.25, 0.8));
    });
    return s;
}

export class ParticleField {
    constructor(container, { count, reduceMotion, font, photo }) {
        this.container = container;
        this.reduceMotion = reduceMotion;
        this.stage = 0;
        this.mouse = { x: 0, y: 0 };
        this.rot = { x: 0, y: 0 };
        this.running = true;

        const w = container.clientWidth;
        const h = container.clientHeight;
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, w < 768 ? 1.5 : 2));
        this.renderer.setSize(w, h);
        container.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(35, w / h, 0.1, 100);
        this.camera.position.set(0, 0, 16);
        this.scene.add(new THREE.HemisphereLight('#ffffff', '#c7d2fe', 1.6));
        const sun = new THREE.DirectionalLight('#ffffff', 1.8);
        sun.position.set(5, 8, 10);
        this.scene.add(sun);

        // Stage order follows the page: hero, paylater, problem (warning sign), solution
        // (idea bulb), the middle sections (a loose scatter), closing CTA.
        this.shapes = [
            sphereShape(count),
            coinShape(count, font),
            warningShape(count),
            bulbShape(count),
            scatterShape(count),
            logoShape(count),
        ];
        this.n = count;

        // Paylater stage: once the TLKM coin photo loads, the particles rebuild it as a
        // pixel mosaic. Until then (or if it fails) the drawn coin above stands in.
        if (photo) {
            const img = new Image();
            img.decoding = 'async';
            img.onload = () => {
                // Sized by height (~4.3 units) so the coins keep the same presence whatever
                // the image's proportions.
                this.shapes[1] = imageShape(img, count, 4.3 * (img.naturalWidth / img.naturalHeight));
            };
            img.src = photo;
        }

        // Per-particle constants: burst direction, wobble phase, size and a fixed tilt.
        this.burst = new Float32Array(count * 3);
        this.phase = new Float32Array(count);
        this.basis = new Float32Array(count * 9);
        // Opening convergence: every block starts just past the left or right edge of the
        // screen (so the page opens clean) and streams in to its place, each on a slightly
        // different delay. The hero sphere sits right of centre, so the left-hand blocks
        // start further out in the group's local space.
        this.introStart = new Float32Array(count * 3);
        this.introDelay = new Float32Array(count);
        for (let i = 0; i < count; i++) {
            const fromRight = Math.random() < 0.5;
            this.introStart[i * 3] = fromRight ? rand(8, 14) : -rand(19, 27);
            this.introStart[i * 3 + 1] = rand(-6, 6);
            this.introStart[i * 3 + 2] = rand(-3, 2);
            this.introDelay[i] = Math.random() * INTRO_STAGGER;
        }
        const q = new THREE.Quaternion();
        const m = new THREE.Matrix4();
        const v = new THREE.Vector3();
        for (let i = 0; i < count; i++) {
            v.set(rand(-1, 1), rand(-1, 1), rand(-1, 1)).normalize().multiplyScalar(rand(0.8, 2.6));
            this.burst.set([v.x, v.y, v.z], i * 3);
            this.phase[i] = rand(0, Math.PI * 2);
            q.setFromEuler(new THREE.Euler(rand(0, 3), rand(0, 3), rand(0, 3)));
            m.makeRotationFromQuaternion(q);
            const e = m.elements;
            const size = 0.052 * rand(0.75, 1.25);
            this.basis.set([e[0], e[1], e[2], e[4], e[5], e[6], e[8], e[9], e[10]].map((x) => x * size), i * 9);
        }

        this.group = new THREE.Group();
        this.scene.add(this.group);
        this.material = new THREE.MeshStandardMaterial({ roughness: 0.45, metalness: 0.05, transparent: true });
        this.mesh = new THREE.InstancedMesh(new THREE.BoxGeometry(1, 1, 1), this.material, count);
        this.mesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        this.mesh.instanceColor = new THREE.InstancedBufferAttribute(new Float32Array(count * 3), 3);
        this.mesh.instanceColor.setUsage(THREE.DynamicDrawUsage);
        this.mesh.frustumCulled = false;
        this.group.add(this.mesh);

        // The hero's chains: a line between neighbouring blocks, following them as they move.
        const links = this.shapes[0].links;
        this.linkGeo = new THREE.BufferGeometry();
        this.linkGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(links.length * 6), 3));
        this.linkMat = new THREE.LineBasicMaterial({ color: '#1d4ed8', transparent: true, opacity: 0.4 });
        this.lines = new THREE.LineSegments(this.linkGeo, this.linkMat);
        this.lines.frustumCulled = false;
        this.group.add(this.lines);


        this.clock = new THREE.Clock();
        this.resize();
        this._onMouse = (e) => {
            this.mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
            this.mouse.y = (e.clientY / window.innerHeight) * 2 - 1;
        };
        this._onResize = () => this.resize();
        this._onVis = () => {
            this.running = document.visibilityState === 'visible';
        };
        window.addEventListener('mousemove', this._onMouse);
        window.addEventListener('resize', this._onResize);
        document.addEventListener('visibilitychange', this._onVis);
        this._raf = requestAnimationFrame(this._tick);
    }

    // Where each shape sits on screen, as a fraction of the visible half-width/height.
    layout() {
        const mobile = this.width < 768;
        if (mobile) {
            return [
                { x: 0, y: 0.42, s: 0.62, o: 0.55, spin: 0.12, sway: 0, turn: 1 },
                { x: 0, y: 0.3, s: 0.55, o: 0.4, spin: 0, sway: 0.2, turn: 1 },
                { x: 0, y: 0.3, s: 0.5, o: 0.45, spin: 0, sway: 0.2, turn: 1 },
                { x: 0, y: 0.3, s: 0.5, o: 0.45, spin: 0, sway: 0.2, turn: 1 },
                { x: 0, y: 0, s: 1, o: 0.22, spin: 0, sway: 0, turn: 0 },
                { x: 0, y: 0.35, s: 0.55, o: 0.45, spin: 0, sway: 0.25, turn: 1 },
            ];
        }
        // turn: 0 keeps the scatter unrotated. Its blocks sit far behind the pivot, so any
        // rotation would swing the whole cloud to one side of the screen.
        return [
            { x: 0.53, y: -0.02, s: 0.8, o: 1, spin: 0.12, sway: 0, turn: 1 },
            { x: -0.52, y: 0, s: 1, o: 1, spin: 0, sway: 0.12, turn: 1 },
            { x: 0.52, y: -0.04, s: 0.95, o: 1, spin: 0, sway: 0.15, turn: 1 },
            { x: -0.5, y: 0.02, s: 0.95, o: 1, spin: 0, sway: 0.15, turn: 1 },
            { x: 0, y: 0, s: 1, o: 0.3, spin: 0, sway: 0, turn: 0 },
            { x: 0.45, y: 0, s: 0.95, o: 1, spin: 0, sway: 0.25, turn: 1 },
        ];
    }

    resize() {
        this.width = this.container.clientWidth;
        this.height = this.container.clientHeight;
        this.renderer.setSize(this.width, this.height);
        this.camera.aspect = this.width / this.height;
        this.camera.updateProjectionMatrix();
        this.halfH = Math.tan(THREE.MathUtils.degToRad(this.camera.fov / 2)) * this.camera.position.z;
        this.halfW = this.halfH * this.camera.aspect;
        this.slots = this.layout();
    }

    setStage(s) {
        this.stage = Math.max(0, Math.min(this.shapes.length - 1, s));
    }

    // Driven by the pinned scenes: push (0..1) dollies the camera into the hero sphere,
    // turn (radians) rotates the coin while the Paylater scene plays.
    setPush(p) {
        this.push = p;
    }

    setTurn(r) {
        this.turn = r;
    }

    _tick = () => {
        this._raf = requestAnimationFrame(this._tick);
        if (!this.running) return;
        const t = this.clock.getElapsedTime();
        const intro = this.reduceMotion ? 1 : Math.min(1, t / INTRO_SECONDS);

        const a = Math.floor(this.stage);
        const b = Math.min(a + 1, this.shapes.length - 1);
        // Hold each shape near its section, morph in the middle of the gap.
        const e = smooth(0.12, 0.88, this.stage - a);
        const A = this.shapes[a];
        const B = this.shapes[b];
        const burst = this.reduceMotion ? 0 : Math.sin(e * Math.PI) * 1.4;
        const wob = this.reduceMotion ? 0 : 0.035;

        const mat = this.mesh.instanceMatrix.array;
        const col = this.mesh.instanceColor.array;
        const pa = A.pos, pb = B.pos, ca = A.col, cb = B.col;
        const basis = this.basis, bd = this.burst, ph = this.phase;
        // Photo mosaic: blend each cube from its random tilt to a straight, grid-sized
        // pixel as the pixel shape takes over (and back out as it leaves).
        const align = (A.pixel ? 1 - e : 0) + (B.pixel ? e : 0);
        const px = (A.pixel ? A.pixelSize : B.pixel ? B.pixelSize : 0) * align;
        for (let i = 0; i < this.n; i++) {
            const k = i * 3;
            const w = Math.sin(t * 1.3 + ph[i]) * wob * (1 - align);
            // Spare particles in a mosaic shrink to nothing instead of cluttering it.
            let vis = (A.pixel ? A.pix[i] : 1) * (1 - e) + (B.pixel ? B.pix[i] : 1) * e;
            // Edge pixels (partly covered) breathe gently: a soft shimmer along the rims.
            if (align > 0 && vis > 0 && vis < 0.98 && !this.reduceMotion) vis *= 1 + 0.22 * (1 - vis) * Math.sin(t * 2.4 + ph[i]);
            let x = pa[k] + (pb[k] - pa[k]) * e + bd[k] * burst + w;
            let y = pa[k + 1] + (pb[k + 1] - pa[k + 1]) * e + bd[k + 1] * burst - w;
            let z = pa[k + 2] + (pb[k + 2] - pa[k + 2]) * e + bd[k + 2] * burst;
            if (intro < 1) {
                let u = (intro - this.introDelay[i]) / (1 - INTRO_STAGGER);
                u = u < 0 ? 0 : u > 1 ? 1 : u;
                // Ease in-out, so the stream is visibly travelling in from the edges.
                u = u < 0.5 ? 4 * u * u * u : 1 - (-2 * u + 2) ** 3 / 2;
                const s = this.introStart;
                x = s[k] + (x - s[k]) * u;
                y = s[k + 1] + (y - s[k + 1]) * u;
                z = s[k + 2] + (z - s[k + 2]) * u;
            }
            const m = i * 16;
            const r = i * 9;
            const ra = (1 - align) * vis;
            const pv = px * vis;
            mat[m] = basis[r] * ra + pv; mat[m + 1] = basis[r + 1] * ra; mat[m + 2] = basis[r + 2] * ra; mat[m + 3] = 0;
            mat[m + 4] = basis[r + 3] * ra; mat[m + 5] = basis[r + 4] * ra + pv; mat[m + 6] = basis[r + 5] * ra; mat[m + 7] = 0;
            mat[m + 8] = basis[r + 6] * ra; mat[m + 9] = basis[r + 7] * ra; mat[m + 10] = basis[r + 8] * ra + pv; mat[m + 11] = 0;
            mat[m + 12] = x; mat[m + 13] = y; mat[m + 14] = z; mat[m + 15] = 1;
            col[k] = ca[k] + (cb[k] - ca[k]) * e;
            col[k + 1] = ca[k + 1] + (cb[k + 1] - ca[k + 1]) * e;
            col[k + 2] = ca[k + 2] + (cb[k + 2] - ca[k + 2]) * e;
        }
        this.mesh.instanceMatrix.needsUpdate = true;
        this.mesh.instanceColor.needsUpdate = true;

        // Chain lines only belong to the sphere.
        const linkPos = this.linkGeo.attributes.position.array;
        this.shapes[0].links.forEach(([p, q], j) => {
            linkPos.set([mat[p * 16 + 12], mat[p * 16 + 13], mat[p * 16 + 14], mat[q * 16 + 12], mat[q * 16 + 13], mat[q * 16 + 14]], j * 6);
        });
        this.linkGeo.attributes.position.needsUpdate = true;
        const sphereWeight = a === 0 ? 1 - e : 0;
        // The chain lines only draw in once the blocks have (mostly) arrived.
        this.linkMat.opacity = 0.6 * sphereWeight * smooth(0.75, 1, intro);
        this.lines.visible = sphereWeight > 0.01;

        const sa = this.slots[a];
        const sb = this.slots[b];
        const lerp = (p, q) => p + (q - p) * e;
        this.group.position.set(lerp(sa.x, sb.x) * this.halfW, lerp(sa.y, sb.y) * this.halfH, 0);
        // Zoom through each transition: the camera pushes in and the shape swells as it
        // bursts, then both settle back as the next shape forms.
        const zoom = this.reduceMotion ? 0 : Math.sin(e * Math.PI);
        this.camera.position.z = 16 - zoom * 3 - (this.push || 0) * 2.5;
        this.group.scale.setScalar(lerp(sa.s, sb.s) * (1 + zoom * 0.22));
        this.material.opacity = lerp(sa.o, sb.o) * smooth(0, 0.25, intro);

        this.rot.x += (this.mouse.y * 0.25 - this.rot.x) * 0.05;
        this.rot.y += (this.mouse.x * 0.35 - this.rot.y) * 0.05;
        const spin = this.reduceMotion ? 0 : lerp(sa.spin, sb.spin);
        const sway = this.reduceMotion ? 0 : lerp(sa.sway, sb.sway) * Math.sin(t * 0.6);
        // Spinning shapes turn freely; the coin and logo ease back to face the viewer.
        const dt = Math.min(0.1, t - (this._lastT ?? t));
        this._lastT = t;
        this.spinAngle = (this.spinAngle || 0) + spin * dt;
        const wrapped = Math.atan2(Math.sin(this.spinAngle), Math.cos(this.spinAngle));
        const spinWeight = lerp(sa.spin > 0 ? 1 : 0, sb.spin > 0 ? 1 : 0);
        const turn = lerp(sa.turn, sb.turn);
        const coin = a === 1 ? 1 - e : b === 1 ? e : 0;
        const scrollTurn = (this.turn || 0) * coin;
        this.group.rotation.set(this.rot.x * turn, (this.rot.y + wrapped * spinWeight + sway + scrollTurn) * turn, 0);
        // The unrotated scatter still answers the mouse, as a small parallax shift.
        this.group.position.x -= this.rot.y * 0.8 * (1 - turn);
        this.group.position.y += this.rot.x * 0.5 * (1 - turn);


        this.renderer.render(this.scene, this.camera);
    };

    dispose() {
        cancelAnimationFrame(this._raf);
        window.removeEventListener('mousemove', this._onMouse);
        window.removeEventListener('resize', this._onResize);
        document.removeEventListener('visibilitychange', this._onVis);
        this.renderer.dispose();
        this.renderer.domElement.remove();
    }
}
