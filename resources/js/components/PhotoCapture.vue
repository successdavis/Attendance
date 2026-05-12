<script setup lang="ts">
import axios from 'axios';
import { nextTick, onUnmounted, ref, watch } from 'vue';

// ─── Props / emits ────────────────────────────────────────────────────────────

const props = defineProps<{
    /** URL of an already-saved photo coming from the server. */
    currentPhotoUrl?: string | null;

    /**
     * If provided the component uploads immediately after capture/select via
     * a plain fetch POST to this URL. The server must return { url: string }.
     * Use this on Edit pages where the user already exists.
     *
     * When omitted the component works in deferred mode: it emits the File
     * to the parent via v-model so the parent form can submit it.
     */
    uploadUrl?: string;

    /** v-model (deferred mode only). */
    modelValue?: File | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void;
    /** Emitted in immediate mode after a successful server save with the new URL. */
    (e: 'saved', url: string): void;
    /** Emitted in immediate mode after the photo is deleted. */
    (e: 'removed'): void;
}>();

// ─── Processing constants ─────────────────────────────────────────────────────

const MAX_DIM      = 400;    // max output px (width or height)
const JPEG_QUALITY = 0.88;   // ~88% — good quality, small file

// ─── UI state ────────────────────────────────────────────────────────────────

type PanelMode = 'idle' | 'upload' | 'camera';
const mode         = ref<PanelMode>('idle');
const isDragging   = ref(false);
const isProcessing = ref(false);   // canvas resize in progress
const processError = ref<string | null>(null);

// Immediate-mode upload state
const isUploading   = ref(false);
const uploadSuccess = ref(false);
const uploadError   = ref<string | null>(null);

// Remove state (immediate mode)
const isRemoving = ref(false);

/** Preview shown in the avatar circle. Starts from server URL or null. */
const preview = ref<string | null>(props.currentPhotoUrl ?? null);

// Camera state
const cameraError       = ref<string | null>(null);
const cameraErrorDetail = ref<string | null>(null);
const stream            = ref<MediaStream | null>(null);
const videoEl           = ref<HTMLVideoElement | null>(null);
const cameraReady       = ref(false);

const fileInput = ref<HTMLInputElement | null>(null);

// Keep deferred-mode preview in sync if parent clears the value
watch(() => props.modelValue, (val) => {
    if (val === null && !props.currentPhotoUrl) preview.value = null;
});

// ─── Canvas image optimisation ────────────────────────────────────────────────

function processBlob(blob: Blob, filename = 'photo.jpg'): Promise<File> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const url = URL.createObjectURL(blob);

        img.onload = () => {
            URL.revokeObjectURL(url);

            let w = img.naturalWidth;
            let h = img.naturalHeight;

            if (w > MAX_DIM || h > MAX_DIM) {
                if (w >= h) { h = Math.round((h / w) * MAX_DIM); w = MAX_DIM; }
                else        { w = Math.round((w / h) * MAX_DIM); h = MAX_DIM; }
            }

            const canvas = document.createElement('canvas');
            canvas.width  = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) return reject(new Error('Canvas unavailable'));

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w, h);
            ctx.drawImage(img, 0, 0, w, h);

            canvas.toBlob(
                (out) => {
                    if (!out) return reject(new Error('Encoding failed'));
                    resolve(new File([out], filename, { type: 'image/jpeg', lastModified: Date.now() }));
                },
                'image/jpeg',
                JPEG_QUALITY,
            );
        };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Decode failed')); };
        img.src = url;
    });
}

// ─── Immediate-mode server upload ─────────────────────────────────────────────

async function serverUpload(file: File): Promise<void> {
    if (!props.uploadUrl) {
        console.warn('[PhotoCapture] serverUpload called but uploadUrl is not set');
        return;
    }

    isUploading.value   = true;
    uploadError.value   = null;
    uploadSuccess.value = false;

    const body = new FormData();
    body.append('profile_photo', file);

    console.log('[PhotoCapture] POSTing to', props.uploadUrl, 'size=', file.size, 'type=', file.type);

    try {
        // axios is pre-configured by Inertia/Laravel with CSRF (XSRF-TOKEN cookie)
        const { data } = await axios.post<{ url: string }>(props.uploadUrl, body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        console.log('[PhotoCapture] Server OK →', data);

        const url = data?.url;
        if (!url) throw new Error('Server returned no URL');

        if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
        preview.value = url;
        uploadSuccess.value = true;
        emit('saved', url);
        setTimeout(() => { uploadSuccess.value = false; }, 3500);

    } catch (err: any) {
        console.error('[PhotoCapture] Upload error:', err);
        const msg =
            err?.response?.data?.message   // Laravel validation message
            ?? err?.response?.data?.error   // our custom error field
            ?? err?.message
            ?? 'Upload failed. Please try again.';
        uploadError.value = `${msg} (status ${err?.response?.status ?? 'unknown'})`;
    } finally {
        isUploading.value = false;
    }
}

async function serverRemove(): Promise<void> {
    if (!props.uploadUrl) return;

    isRemoving.value  = true;
    uploadError.value = null;

    try {
        await axios.delete(props.uploadUrl);
        if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
        preview.value = null;
        emit('removed');
    } catch (err: any) {
        console.error('[PhotoCapture] Remove error:', err);
        uploadError.value = err?.response?.data?.message ?? err?.message ?? 'Could not remove photo.';
    } finally {
        isRemoving.value = false;
    }
}

// ─── Shared post-process handler ──────────────────────────────────────────────

async function applyBlob(blob: Blob, filename: string): Promise<void> {
    isProcessing.value = true;
    processError.value = null;
    uploadError.value  = null;

    try {
        const file = await processBlob(blob, filename);

        if (props.uploadUrl) {
            // Immediate mode: show blob preview optimistically, then upload
            if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
            preview.value = URL.createObjectURL(file);
            await serverUpload(file);
        } else {
            // Deferred mode: emit file to parent form
            if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
            preview.value = URL.createObjectURL(file);
            emit('update:modelValue', file);
        }

        mode.value = 'idle';
    } catch (err: any) {
        processError.value = err?.message ?? 'Processing failed.';
    } finally {
        isProcessing.value = false;
    }
}

// ─── Upload / drag-drop ───────────────────────────────────────────────────────

function openFilePicker() { fileInput.value?.click(); }

async function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (fileInput.value) fileInput.value.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) { processError.value = 'Please select an image file.'; return; }
    await applyBlob(file, 'passport.jpg');
}

function onDragOver(e: DragEvent)  { e.preventDefault(); isDragging.value = true; }
function onDragLeave()             { isDragging.value = false; }
async function onDrop(e: DragEvent) {
    e.preventDefault(); isDragging.value = false;
    const file = e.dataTransfer?.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { processError.value = 'Only image files accepted.'; return; }
    await applyBlob(file, 'passport.jpg');
}

// ─── Clear photo ──────────────────────────────────────────────────────────────

async function clearPhoto() {
    if (props.uploadUrl) {
        await serverRemove();
    } else {
        if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
        preview.value = null;
        emit('update:modelValue', null);
    }
}

// ─── Camera ───────────────────────────────────────────────────────────────────

async function attachStream(ms: MediaStream): Promise<void> {
    stream.value = ms;
    await nextTick();
    const video = videoEl.value;
    if (!video) return;
    video.srcObject = ms;
    await new Promise<void>(resolve => {
        video.onloadedmetadata = () => {
            video.play().catch(() => {});
            cameraReady.value = true;
            resolve();
        };
    });
}

async function startCamera() {
    cameraError.value       = null;
    cameraErrorDetail.value = null;
    cameraReady.value       = false;
    mode.value              = 'camera';

    if (!navigator?.mediaDevices?.getUserMedia) {
        cameraError.value = 'Camera API not available.';
        cameraErrorDetail.value = window.isSecureContext === false
            ? 'Camera requires HTTPS or localhost. Ask your admin to enable HTTPS.'
            : 'Your browser does not support camera access. Try Chrome or Edge.';
        return;
    }

    let ms: MediaStream | null = null;

    // Attempt 1: with resolution hints (no facingMode — desktop webcams don't support it)
    try {
        ms = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
    } catch (err: any) {
        const name = (err as DOMException)?.name ?? '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            cameraError.value = 'Camera access was denied.';
            cameraErrorDetail.value = "Open your browser's site settings (🔒 in the address bar), set Camera to \"Allow\", then click Try Again.";
            return;
        }
        if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            cameraError.value = 'No camera detected.';
            cameraErrorDetail.value = 'Make sure a webcam is connected and not disabled in Device Manager.';
            return;
        }
        // OverconstrainedError, NotReadableError etc. → fall through to bare attempt
    }

    // Attempt 2: bare { video: true } — works on any webcam
    if (!ms) {
        try {
            ms = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        } catch (err: any) {
            const e = err as DOMException;
            cameraError.value = 'Could not open the camera.';
            cameraErrorDetail.value = `${e.name}: ${e.message}`;
            return;
        }
    }

    try { await attachStream(ms); }
    catch (err: any) {
        stopStream();
        cameraError.value = 'Camera opened but video failed to display.';
        cameraErrorDetail.value = String(err);
    }
}

async function takeSnapshot() {
    const video = videoEl.value;
    if (!video || !stream.value) return;

    const snap = document.createElement('canvas');
    snap.width  = video.videoWidth;
    snap.height = video.videoHeight;
    snap.getContext('2d')!.drawImage(video, 0, 0);

    stopStream();

    snap.toBlob(async (blob) => {
        if (!blob) { processError.value = 'Snapshot failed. Try again.'; return; }
        await applyBlob(blob, 'snapshot.jpg');
    }, 'image/jpeg', 0.96);
}

function stopStream() {
    stream.value?.getTracks().forEach(t => t.stop());
    stream.value   = null;
    cameraReady.value = false;
}

function cancelCamera() {
    stopStream();
    mode.value             = 'idle';
    cameraError.value      = null;
    cameraErrorDetail.value = null;
}

// ─── Cleanup ─────────────────────────────────────────────────────────────────

onUnmounted(() => {
    stopStream();
    if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
});

function formatBytes(n: number) {
    return n < 1024 ? `${n} B` : n < 1048576 ? `${(n/1024).toFixed(1)} KB` : `${(n/1048576).toFixed(2)} MB`;
}
</script>

<template>
    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />

    <div class="space-y-3">

        <!-- ─── Avatar + action row ───────────────────────────────────────── -->
        <div class="flex items-center gap-5">

            <!-- Circle avatar -->
            <div class="relative shrink-0">
                <div class="h-24 w-24 overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800">
                    <img v-if="preview" :src="preview" alt="Profile photo preview" class="h-full w-full object-cover" />
                    <div v-else class="flex h-full w-full items-center justify-center">
                        <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>

                <!-- Processing / uploading spinner overlay -->
                <div v-if="isProcessing || isUploading || isRemoving"
                    class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                    <svg class="h-6 w-6 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>

                <!-- Saved checkmark badge (immediate mode) -->
                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-50" enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="uploadSuccess"
                        class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </Transition>
            </div>

            <!-- Right side: description, status, buttons -->
            <div class="flex flex-1 flex-col gap-2">


                <!-- Upload status row (immediate mode) -->
                <div v-if="uploadUrl" class="text-xs font-medium">
                    <span v-if="isUploading" class="text-indigo-600 dark:text-indigo-400">Saving photo…</span>
                    <span v-else-if="isRemoving" class="text-orange-500">Removing…</span>
                    <span v-else-if="uploadSuccess" class="text-emerald-600 dark:text-emerald-400">✓ Photo saved!</span>
                    <span v-else-if="!preview" class="text-muted-foreground">No photo set</span>
                </div>

                <!-- Deferred mode: file name -->
                <div v-if="!uploadUrl && modelValue" class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                    ✓ {{ modelValue.name }} ({{ formatBytes(modelValue.size) }})
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap gap-2">
                    <!-- Upload -->
                    <button type="button"
                        :disabled="isUploading || isRemoving"
                        @click="mode = mode === 'upload' ? 'idle' : 'upload'; cameraError = null"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted disabled:opacity-50"
                        :class="mode === 'upload' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Upload Photo
                    </button>

                    <!-- Camera -->
                    <button type="button"
                        :disabled="isUploading || isRemoving"
                        @click="mode === 'camera' ? cancelCamera() : startCamera()"
                        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted disabled:opacity-50"
                        :class="mode === 'camera' ? 'border-indigo-400 bg-indigo-50 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-900/20 dark:text-indigo-300' : ''">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Take Photo
                    </button>

                    <!-- Remove -->
                    <button v-if="preview" type="button"
                        :disabled="isUploading || isRemoving"
                        @click="clearPhoto"
                        class="inline-flex items-center gap-1.5 rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ isRemoving ? 'Removing…' : 'Remove' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Error messages ────────────────────────────────────────────── -->
        <p v-if="processError" class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">
            ⚠ {{ processError }}
        </p>
        <p v-if="uploadError" class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">
            ⚠ Photo not saved: {{ uploadError }}
        </p>

        <!-- ─── Upload panel ──────────────────────────────────────────────── -->
        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="mode === 'upload'"
                class="overflow-hidden rounded-xl border-2 border-dashed transition-colors"
                :class="isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700'"
                @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop">
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Drag & drop a photo here</p>
                        <p class="mt-1 text-xs text-muted-foreground">or</p>
                    </div>
                    <button type="button" @click="openFilePicker"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors">
                        Browse Files
                    </button>
                    <p class="text-xs text-muted-foreground">JPG, PNG, WEBP · Any size (auto-optimised to {{ MAX_DIM }}×{{ MAX_DIM }}px)</p>
                </div>
            </div>
        </Transition>

        <!-- ─── Camera panel ──────────────────────────────────────────────── -->
        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="mode === 'camera'" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">

                <!-- Error -->
                <div v-if="cameraError" class="flex flex-col items-center gap-4 p-8 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/>
                        </svg>
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold text-red-600 dark:text-red-400">{{ cameraError }}</p>
                        <p v-if="cameraErrorDetail" class="max-w-sm text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            {{ cameraErrorDetail }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="startCamera"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition-colors">
                            Try Again
                        </button>
                        <button type="button" @click="cancelCamera"
                            class="rounded-lg border px-4 py-2 text-xs font-medium hover:bg-muted">
                            Dismiss
                        </button>
                    </div>
                </div>

                <!-- Live feed -->
                <div v-else>
                    <div class="relative bg-black">
                        <video ref="videoEl" class="w-full max-h-72 object-cover" autoplay muted playsinline />

                        <!-- Initialising overlay -->
                        <div v-if="!cameraReady"
                            class="absolute inset-0 flex items-center justify-center bg-black/60 text-white">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span class="text-sm">Starting camera…</span>
                            </div>
                        </div>

                        <!-- Face guide oval -->
                        <div v-if="cameraReady" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="h-44 w-36 rounded-full border-2 border-dashed border-white/60"></div>
                        </div>

                        <!-- Uploading overlay (immediate mode — shown while sending to server) -->
                        <div v-if="isUploading"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/60 text-white">
                            <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span class="text-sm">Saving photo…</span>
                        </div>
                    </div>

                    <!-- Controls bar -->
                    <div class="flex items-center justify-between bg-gray-50 px-5 py-3 dark:bg-gray-900">
                        <button type="button" @click="cancelCamera"
                            class="text-xs font-medium text-muted-foreground hover:text-foreground">
                            ← Cancel
                        </button>

                        <!-- Shutter -->
                        <button type="button"
                            :disabled="!cameraReady || isProcessing || isUploading"
                            @click="takeSnapshot"
                            class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-indigo-600 text-white shadow-lg transition-transform hover:scale-105 active:scale-95 disabled:opacity-40 dark:border-gray-800"
                            title="Take snapshot">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>

                        <span class="text-xs text-muted-foreground">Align face in oval</span>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>
