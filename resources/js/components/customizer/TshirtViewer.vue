<!--
  KompOnen TshirtViewer — Penampil Model 3D
  ==========================================
  Menampilkan model 3D (.glb/.gltf) menggunakan Three.js dengan fitur:
  - Muat model dari path yang diberikan, atau gunakan placeholder sederhana
  - Otomatis center & scale model ke tengah viewport
  - Ganti warna model secara reaktif via prop `color`
  - Terapkan texture desain dari canvas Fabric.js ke permukaan model
  - Orbit controls: rotasi, zoom in/out
  - Konfigurasi fleksibel untuk berbagai model 3D
-->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, shallowRef } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

/**
 * Props yang diterima oleh komponen.
 * - color: warna HEX untuk diterapkan ke seluruh mesh model
 * - designCanvas: elemen canvas HTML dari Fabric.js untuk texture desain
 * - modelPath: path ke file model 3D (.glb/.gltf), opsional
 */
const props = defineProps<{
    color: string;
    designCanvas: HTMLCanvasElement | null;
    modelPath?: string;
}>();

const emit = defineEmits<{
    (e: 'renderer-ready', renderer: THREE.WebGLRenderer): void;
}>();

const containerRef = ref<HTMLDivElement | null>(null);

// Gunakan shallowRef untuk objek Three.js agar menghindari overhead
// dari reaktivitas dalam (deep reactivity) Vue
const renderer = shallowRef<THREE.WebGLRenderer | null>(null);
const scene = shallowRef<THREE.Scene | null>(null);
const camera = shallowRef<THREE.PerspectiveCamera | null>(null);
const controls = shallowRef<OrbitControls | null>(null);
const designTexture = shallowRef<THREE.CanvasTexture | null>(null);
const animationId = ref<number>(0);

/** Referensi ke grup model yang dimuat, untuk manipulasi nanti */
const modelGroup = shallowRef<THREE.Group | null>(null);

// ─── Warna Latar Belakang Scene ───
const BG_COLOR = 0xf5f0e8;

/**
 * Membuat placeholder kaos sederhana menggunakan ExtrudeGeometry.
 * Dipakai jika modelPath tidak disediakan atau gagal dimuat.
 */
function createPlaceholderTshirt(): THREE.Group {
    const group = new THREE.Group();

    const shape = new THREE.Shape();
    shape.moveTo(-0.55, 0.55);
    shape.lineTo(-0.75, 0.50);
    shape.lineTo(-0.75, 0.25);
    shape.lineTo(-0.55, 0.20);
    shape.lineTo(-0.50, -0.65);
    shape.quadraticCurveTo(-0.25, -0.70, 0, -0.68);
    shape.quadraticCurveTo(0.25, -0.70, 0.50, -0.65);
    shape.lineTo(0.55, 0.20);
    shape.lineTo(0.75, 0.25);
    shape.lineTo(0.75, 0.50);
    shape.lineTo(0.55, 0.55);
    shape.quadraticCurveTo(0.30, 0.70, 0, 0.65);
    shape.quadraticCurveTo(-0.30, 0.70, -0.55, 0.55);

    const geometry = new THREE.ExtrudeGeometry(shape, {
        depth: 0.08,
        bevelEnabled: true,
        bevelThickness: 0.015,
        bevelSize: 0.015,
        bevelSegments: 3,
    });
    geometry.center();

    const material = new THREE.MeshStandardMaterial({
        color: props.color || '#CCCCCC',
        roughness: 0.75,
        metalness: 0.0,
        side: THREE.DoubleSide,
    });

    const mesh = new THREE.Mesh(geometry, material);
    mesh.name = 'tshirt-body';
    group.add(mesh);

    // Plane overlay untuk desain di area dada
    const planeGeom = new THREE.PlaneGeometry(0.55, 0.55);
    const planeMat = new THREE.MeshStandardMaterial({
        transparent: true,
        opacity: 1,
        roughness: 0.8,
        metalness: 0.0,
        depthWrite: false,
    });
    const planeMesh = new THREE.Mesh(planeGeom, planeMat);
    planeMesh.name = 'design-overlay';
    planeMesh.position.set(0, 0.06, 0.065);
    group.add(planeMesh);

    return group;
}

/**
 * Memuat model GLTF/GLB dari path yang diberikan.
 * Otomatis:
 * - Center model ke origin (0,0,0)
 * - Scale model agar tingginya ~2 unit
 * - Tag semua mesh sebagai 'tshirt-body' untuk perubahan warna
 * - Tambahkan overlay plane untuk desain di area dada
 */
function loadGLTFModel(path: string): Promise<THREE.Group> {
    return new Promise((resolve, reject) => {
        const loader = new GLTFLoader();
        loader.load(
            path,
            (gltf) => {
                const model = gltf.scene;

                // Hitung bounding box untuk center & scale
                model.updateMatrixWorld(true);
                const box = new THREE.Box3().setFromObject(model);
                const size = box.getSize(new THREE.Vector3());
                const center = box.getCenter(new THREE.Vector3());

                // Pindahkan model ke origin dengan menggunakan wrapper
                // Hal ini menghindari isu penskalaan di sekitar pivot yang salah (offset pivot)
                const wrapper = new THREE.Group();
                model.position.x = -center.x;
                model.position.y = -center.y;
                model.position.z = -center.z;
                wrapper.add(model);

                // Scale agar muat di viewport (~2 unit tinggi)
                const maxDim = Math.max(size.x, size.y, size.z) || 1;
                const scaleFactor = 2.0 / maxDim;
                wrapper.scale.setScalar(scaleFactor);

                // Hitung ulang bounding box setelah transformasi
                wrapper.updateMatrixWorld(true);
                const scaledBox = new THREE.Box3().setFromObject(wrapper);
                const scaledSize = scaledBox.getSize(new THREE.Vector3());
                const scaledCenter = scaledBox.getCenter(new THREE.Vector3());

                // Tag semua mesh sebagai 'tshirt-body' & clone material
                wrapper.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        child.name = 'tshirt-body';

                        if (Array.isArray(child.material)) {
                            child.material = child.material.map((m) => {
                                const c = m.clone();
                                if ('color' in c) c.color.set(props.color || '#CCCCCC');
                                if ('roughness' in c) c.roughness = Math.max(c.roughness as number, 0.4);
                                return c;
                            });
                        } else {
                            child.material = child.material.clone();
                            const mat = child.material as THREE.MeshStandardMaterial;
                            if (mat.color) mat.color.set(props.color || '#CCCCCC');
                            if ('roughness' in mat) mat.roughness = Math.max(mat.roughness as number, 0.4);
                        }
                    }
                });

                // Tambahkan overlay plane untuk desain di area dada
                const overlayW = scaledSize.x * 0.4;
                const overlayH = scaledSize.y * 0.35;
                const planeGeom = new THREE.PlaneGeometry(overlayW, overlayH);
                const planeMat = new THREE.MeshStandardMaterial({
                    transparent: true,
                    opacity: 1,
                    roughness: 0.8,
                    metalness: 0.0,
                    depthWrite: false,
                    polygonOffset: true,
                    polygonOffsetFactor: -1,
                });
                const planeMesh = new THREE.Mesh(planeGeom, planeMat);
                planeMesh.name = 'design-overlay';
                
                // Posisi di depan-tengah area dada (sedikit di atas pusat)
                // Karena model dibungkus wrapper, posisinya relatif terhadap wrapper
                planeMesh.position.set(
                    scaledCenter.x,
                    scaledCenter.y + scaledSize.y * 0.1,
                    scaledBox.max.z + 0.01,
                );
                wrapper.add(planeMesh);

                resolve(wrapper);
            },
            undefined,
            (error) => {
                console.warn('[TshirtViewer] Gagal memuat model GLTF, menggunakan placeholder:', error);
                reject(error);
            },
        );
    });
}

/**
 * Memuat model 3D (GLTF atau placeholder) lalu tambahkan ke scene.
 * Otomatis menyesuaikan posisi kamera agar model berada tepat di tengah.
 */
async function loadModel(s: THREE.Scene) {
    let model: THREE.Group;
    let isGLTF = false;

    if (props.modelPath) {
        try {
            model = await loadGLTFModel(props.modelPath);
            isGLTF = true;
        } catch {
            model = createPlaceholderTshirt();
        }
    } else {
        model = createPlaceholderTshirt();
    }

    s.add(model);
    modelGroup.value = model;

    // Sesuaikan kamera agar model berada tepat di tengah viewport
    if (camera.value && controls.value) {
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        // Arahkan kamera ke pusat model
        controls.value.target.copy(center);

        // Posisi kamera: mundur sejauh proporsional dari model
        const distance = isGLTF
            ? Math.max(size.x, size.y) * 2.0
            : 2.8;
        camera.value.position.set(center.x, center.y, center.z + distance);
        camera.value.updateProjectionMatrix();
        controls.value.update();
    }

    // Terapkan texture desain jika sudah tersedia
    if (props.designCanvas) {
        applyDesignTexture(props.designCanvas);
    }
}

/**
 * Inisialisasi scene Three.js: renderer, kamera, lampu, dan kontrol orbit.
 */
function initScene() {
    if (!containerRef.value) return;

    const container = containerRef.value;
    const width = container.clientWidth;
    const height = container.clientHeight;

    // --- Renderer ---
    const r = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        preserveDrawingBuffer: true, // Diperlukan untuk download screenshot
    });
    r.setSize(width, height);
    r.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    r.setClearColor(BG_COLOR, 1);
    r.toneMapping = THREE.ACESFilmicToneMapping;
    r.toneMappingExposure = 1.0;
    container.appendChild(r.domElement);
    renderer.value = r;

    // --- Scene ---
    const s = new THREE.Scene();
    s.background = new THREE.Color(BG_COLOR);
    scene.value = s;

    // --- Kamera ---
    const cam = new THREE.PerspectiveCamera(40, width / height, 0.1, 100);
    cam.position.set(0, 0, 2.8);
    camera.value = cam;

    // --- Pencahayaan ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    s.add(ambientLight);

    const dirLight1 = new THREE.DirectionalLight(0xffffff, 1.2);
    dirLight1.position.set(2, 3, 4);
    s.add(dirLight1);

    const dirLight2 = new THREE.DirectionalLight(0xffffff, 0.4);
    dirLight2.position.set(-2, 1, -2);
    s.add(dirLight2);

    const hemiLight = new THREE.HemisphereLight(0xffeedd, 0xf5f0e8, 0.5);
    s.add(hemiLight);

    // --- Kontrol Orbit (rotasi & zoom) ---
    const ctrl = new OrbitControls(cam, r.domElement);
    ctrl.enableDamping = true;
    ctrl.dampingFactor = 0.08;
    ctrl.enablePan = false;
    ctrl.minDistance = 1.2;
    ctrl.maxDistance = 6;
    ctrl.minPolarAngle = Math.PI / 6;
    ctrl.maxPolarAngle = Math.PI / 1.5;
    ctrl.target.set(0, 0, 0);
    controls.value = ctrl;

    // --- Muat model 3D ---
    loadModel(s);

    // --- Emit renderer ke parent ---
    emit('renderer-ready', r);

    // --- Loop animasi ---
    function animate() {
        animationId.value = requestAnimationFrame(animate);
        ctrl.update();

        // Update texture desain setiap frame
        if (designTexture.value) {
            designTexture.value.needsUpdate = true;
        }

        r.render(s, cam);
    }
    animate();
}

/**
 * Terapkan canvas Fabric.js sebagai texture pada overlay desain di model 3D.
 */
function applyDesignTexture(canvas: HTMLCanvasElement) {
    if (!scene.value) return;

    const tex = new THREE.CanvasTexture(canvas);
    tex.flipY = true;
    tex.needsUpdate = true;
    designTexture.value = tex;

    // Cari plane overlay dan terapkan texture
    scene.value.traverse((child) => {
        if (child instanceof THREE.Mesh && child.name === 'design-overlay') {
            const mat = child.material as THREE.MeshStandardMaterial;
            mat.map = tex;
            mat.needsUpdate = true;
        }
    });
}

/**
 * Perbarui warna semua mesh model (kecuali overlay desain).
 */
function updateColor(hex: string) {
    if (!scene.value) return;

    scene.value.traverse((child) => {
        if (child instanceof THREE.Mesh && child.name === 'tshirt-body') {
            const mat = child.material;
            if (Array.isArray(mat)) {
                mat.forEach((m) => {
                    if ('color' in m) { m.color.set(hex); m.needsUpdate = true; }
                });
            } else if ('color' in mat) {
                (mat as THREE.MeshStandardMaterial).color.set(hex);
                mat.needsUpdate = true;
            }
        }
    });
}

/** Tangani resize viewport */
function handleResize() {
    if (!containerRef.value || !renderer.value || !camera.value) return;

    const width = containerRef.value.clientWidth;
    const height = containerRef.value.clientHeight;

    camera.value.aspect = width / height;
    camera.value.updateProjectionMatrix();
    renderer.value.setSize(width, height);
}

// ─── Watchers ───
watch(() => props.color, (c) => { if (c) updateColor(c); });
watch(() => props.designCanvas, (c) => { if (c) applyDesignTexture(c); });

// ─── Lifecycle ───
onMounted(() => {
    initScene();
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    if (animationId.value) cancelAnimationFrame(animationId.value);
    if (renderer.value) { renderer.value.dispose(); renderer.value.domElement.remove(); }
    if (controls.value) controls.value.dispose();
});

defineExpose({ getRenderer: () => renderer.value });
</script>

<template>
    <div ref="containerRef" class="tshirt-viewer"></div>
</template>

<style scoped>
.tshirt-viewer {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.tshirt-viewer :deep(canvas) {
    display: block;
    width: 100% !important;
    height: 100% !important;
}
</style>
