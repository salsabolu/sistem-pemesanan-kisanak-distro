<!--
  Komponen TshirtViewer — Penampil Model 3D
  ==========================================
  Menampilkan model 3D (.obj/.glb/.gltf) menggunakan Three.js dengan fitur:
  - Muat model OBJ dengan texture mapping langsung dari canvas
  - Fallback ke model GLB/GLTF jika OBJ tidak tersedia
  - Orbit controls: rotasi, zoom in/out
  - Texture dari SVG pattern canvas di-map langsung ke UV model
  - Konfigurasi fleksibel untuk berbagai model 3D
-->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, shallowRef } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

/**
 * Props yang diterima oleh komponen.
 * - designCanvas: elemen canvas HTML (composite) untuk texture desain
 * - modelPath: path ke file model 3D (.obj atau .glb/.gltf)
 */
const props = defineProps<{
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
const textureMaterial = shallowRef<THREE.MeshPhongMaterial | null>(null);
const animationId = ref<number>(0);

/** Referensi ke model yang dimuat */
const modelObject = shallowRef<THREE.Object3D | null>(null);

// ─── Warna & Background ───
const BG_COLOR = 0x1a1a2e;

/**
 * Membuat placeholder kaos sederhana jika model tidak tersedia.
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

    const material = textureMaterial.value || new THREE.MeshPhongMaterial({
        color: '#CCCCCC',
        side: THREE.DoubleSide,
    });

    const mesh = new THREE.Mesh(geometry, material);
    mesh.name = 'tshirt-body';
    group.add(mesh);

    return group;
}

/**
 * Memuat model OBJ dari path yang diberikan.
 * Texture langsung di-map ke material semua mesh model (sesuai referensi main_s.js).
 */
function loadOBJModel(path: string): Promise<THREE.Object3D> {
    return new Promise((resolve, reject) => {
        const loader = new OBJLoader();
        loader.load(
            path,
            (object) => {
                // Terapkan texture material ke semua mesh
                object.traverse((node) => {
                    if ((node as THREE.Mesh).isMesh) {
                        const mesh = node as THREE.Mesh;
                        if (textureMaterial.value) {
                            mesh.material = textureMaterial.value;
                        }
                        mesh.geometry.computeVertexNormals();
                    }
                });

                resolve(object);
            },
            undefined,
            (error) => {
                console.warn('[TshirtViewer] Gagal memuat model OBJ:', error);
                reject(error);
            },
        );
    });
}

/**
 * Memuat model GLTF/GLB dari path yang diberikan (fallback).
 */
function loadGLTFModel(path: string): Promise<THREE.Group> {
    return new Promise((resolve, reject) => {
        const loader = new GLTFLoader();
        loader.load(
            path,
            (gltf) => {
                const model = gltf.scene;

                // Center & scale
                model.updateMatrixWorld(true);
                const box = new THREE.Box3().setFromObject(model);
                const size = box.getSize(new THREE.Vector3());
                const center = box.getCenter(new THREE.Vector3());

                const wrapper = new THREE.Group();
                model.position.x = -center.x;
                model.position.y = -center.y;
                model.position.z = -center.z;
                wrapper.add(model);

                const maxDim = Math.max(size.x, size.y, size.z) || 1;
                const scaleFactor = 2.0 / maxDim;
                wrapper.scale.setScalar(scaleFactor);

                // Terapkan texture ke semua mesh
                wrapper.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        if (textureMaterial.value) {
                            child.material = textureMaterial.value;
                        }
                    }
                });

                resolve(wrapper);
            },
            undefined,
            (error) => {
                console.warn('[TshirtViewer] Gagal memuat model GLTF:', error);
                reject(error);
            },
        );
    });
}

/**
 * Membuat texture material dari canvas desain.
 */
function createTextureMaterial(canvas: HTMLCanvasElement): THREE.MeshPhongMaterial {
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.needsUpdate = true;
    designTexture.value = texture;

    const material = new THREE.MeshPhongMaterial({
        map: texture,
        side: THREE.DoubleSide,
    });
    textureMaterial.value = material;

    return material;
}

/**
 * Memuat model 3D dan tambahkan ke scene.
 * Prioritas: OBJ > GLTF/GLB > Placeholder
 */
async function loadModel(s: THREE.Scene) {
    let model: THREE.Object3D;
    const isOBJ = props.modelPath?.toLowerCase().endsWith('.obj');

    if (props.modelPath) {
        try {
            if (isOBJ) {
                model = await loadOBJModel(props.modelPath);
            } else {
                model = await loadGLTFModel(props.modelPath);
            }
        } catch {
            model = createPlaceholderTshirt();
        }
    } else {
        model = createPlaceholderTshirt();
    }

    // Untuk model OBJ, perlu center & scale manual
    if (isOBJ) {
        model.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        // Scale agar muat di viewport
        const maxDim = Math.max(size.x, size.y, size.z) || 1;
        const scaleFactor = 2.5 / maxDim;
        model.scale.setScalar(scaleFactor);
        model.scale.x = -scaleFactor; // Mirror the model to fix bad UV mapping in tshirt1.obj

        // Center
        model.updateMatrixWorld(true);
        const newBox = new THREE.Box3().setFromObject(model);
        const newCenter = newBox.getCenter(new THREE.Vector3());
        model.position.sub(newCenter);
    }

    s.add(model);
    modelObject.value = model;

    // Sesuaikan kamera agar model berada di tengah viewport
    if (camera.value && controls.value) {
        model.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        controls.value.target.copy(center);

        const distance = Math.max(size.x, size.y, size.z) * 2.0;
        camera.value.position.set(
            center.x + distance * 0.5,
            center.y,
            center.z + distance,
        );
        camera.value.updateProjectionMatrix();
        controls.value.update();
    }
}

/**
 * Terapkan canvas desain sebagai texture pada model 3D.
 */
function applyDesignTexture(canvas: HTMLCanvasElement) {
    if (!scene.value) return;

    // Buat atau update texture material
    if (!textureMaterial.value) {
        createTextureMaterial(canvas);
    } else {
        const tex = new THREE.CanvasTexture(canvas);
        tex.colorSpace = THREE.SRGBColorSpace;
        tex.needsUpdate = true;
        designTexture.value = tex;
        textureMaterial.value.map = tex;
        textureMaterial.value.needsUpdate = true;
    }

    // Terapkan ke semua mesh di model
    if (modelObject.value) {
        modelObject.value.traverse((child) => {
            if ((child as THREE.Mesh).isMesh) {
                (child as THREE.Mesh).material = textureMaterial.value!;
            }
        });
    }
}

/**
 * Inisialisasi scene Three.js: renderer, kamera, lampu, kontrol orbit.
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
        preserveDrawingBuffer: true,
    });
    r.setSize(width, height);
    r.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    r.setClearColor(BG_COLOR, 1);
    r.toneMapping = THREE.ACESFilmicToneMapping;
    r.toneMappingExposure = 1.2;
    container.appendChild(r.domElement);
    renderer.value = r;

    // --- Scene ---
    const s = new THREE.Scene();
    s.background = new THREE.Color(BG_COLOR);
    scene.value = s;

    // --- Kamera ---
    const cam = new THREE.PerspectiveCamera(30, width / height, 0.1, 1200);
    cam.position.set(0, 0, 5);
    camera.value = cam;

    // --- Pencahayaan (sesuai referensi main_s.js) ---
    s.add(new THREE.AmbientLight(0x666666, 1.5));

    const lights = [
        { color: 0xffffff, intensity: 0.8, position: { x: -500, y: 320, z: 500 } },
        { color: 0xffffff, intensity: 0.5, position: { x: 200, y: 50, z: 500 } },
        { color: 0xffffff, intensity: 0.6, position: { x: 0, y: 100, z: -500 } },
        { color: 0xffffff, intensity: 0.4, position: { x: 300, y: -100, z: 300 } },
    ];

    lights.forEach((l) => {
        const dirLight = new THREE.DirectionalLight(l.color, l.intensity);
        dirLight.position.set(l.position.x, l.position.y, l.position.z);
        dirLight.lookAt(0, 0, 0);
        s.add(dirLight);
    });

    // Fill light dari bawah
    const hemiLight = new THREE.HemisphereLight(0xffeedd, 0x1a1a2e, 0.4);
    s.add(hemiLight);

    // --- Kontrol Orbit ---
    const ctrl = new OrbitControls(cam, r.domElement);
    ctrl.enableDamping = true;
    ctrl.dampingFactor = 0.08;
    ctrl.enablePan = false;
    ctrl.minDistance = 1.0;
    ctrl.maxDistance = 10;
    ctrl.minPolarAngle = Math.PI / 6;
    ctrl.maxPolarAngle = Math.PI / 1.5;
    ctrl.target.set(0, 0, 0);
    controls.value = ctrl;

    // --- Buat texture material dari designCanvas jika sudah ada ---
    if (props.designCanvas) {
        createTextureMaterial(props.designCanvas);
    }

    // --- Muat model 3D ---
    loadModel(s);

    // --- Emit renderer ke parent ---
    emit('renderer-ready', r);

    // --- Loop animasi ---
    function animate() {
        animationId.value = requestAnimationFrame(animate);
        ctrl.update();
        r.render(s, cam);
    }
    animate();
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
watch(() => props.designCanvas, (c) => {
    if (c) applyDesignTexture(c);
});

// ─── Lifecycle ───
onMounted(() => {
    initScene();
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    if (animationId.value) cancelAnimationFrame(animationId.value);
    if (renderer.value) {
        renderer.value.dispose();
        renderer.value.domElement.remove();
    }
    if (controls.value) controls.value.dispose();
});

defineExpose({
    getRenderer: () => renderer.value,
    updateTexture: () => {
        if (designTexture.value) designTexture.value.needsUpdate = true;
    }
});
</script>

<template>
    <div ref="containerRef" class="tshirt-viewer">
        <!-- Hint tooltip -->
        <div class="tshirt-viewer__hint">
            Drag to rotate • Scroll to zoom
        </div>
    </div>
</template>

<style scoped>
.tshirt-viewer {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #1a1a2e;
}

.tshirt-viewer :deep(canvas) {
    display: block;
    width: 100% !important;
    height: 100% !important;
}

.tshirt-viewer__hint {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 5;
    padding: 6px 16px;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
    pointer-events: none;
    white-space: nowrap;
}
</style>
