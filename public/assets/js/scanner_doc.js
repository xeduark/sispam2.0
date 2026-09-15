/**
 * SISPAM - Escáner Profesional de Documentos para Dispositivos Móviles y Tabletas
 * Funcionalidades tipo CamScanner: Detección de bordes, recorte cuadrilátero interactivo,
 * lupa de precisión para pantallas táctiles, des-perspectiva (warp), filtros de realce,
 * escaneo multipágina y exportación a PDF multipágina.
 */

class DocumentScannerPro {
    constructor(options = {}) {
        this.videoElement = document.getElementById(options.videoId || 'webcam-video');
        this.canvasSource = document.createElement('canvas');
        this.canvasOverlay = document.getElementById(options.overlayId || 'scanner-canvas-overlay');
        this.canvasProcessed = document.getElementById(options.processedCanvasId || 'canvas-processed');
        this.canvasLoupe = document.getElementById(options.loupeCanvasId || 'canvas-loupe');
        
        this.stream = null;
        this.rawImage = null;
        this.rawWidth = 0;
        this.rawHeight = 0;

        this.scannedPages = [];
        this.currentPageIndex = -1;

        this.corners = [
            { x: 0.1, y: 0.1 },
            { x: 0.9, y: 0.1 },
            { x: 0.9, y: 0.9 },
            { x: 0.1, y: 0.9 }
        ];

        this.activeCornerIndex = -1;
        this.currentFilter = 'magic';
        this.rotationAngle = 0;

        this.isOpenCVReady = typeof cv !== 'undefined' && cv.Mat;
        if (!this.isOpenCVReady) {
            window.addEventListener('opencv-ready', () => {
                this.isOpenCVReady = true;
                console.log("OpenCV.js listo para escáner profesional.");
            });
        }

        this.initEvents();

        window.addEventListener('resize', () => {
            if (this.rawImage && this.canvasOverlay && !this.canvasOverlay.closest('.d-none')) {
                this.drawOverlay();
            }
        });
    }

    initEvents() {
        if (!this.canvasOverlay) return;

        const getCanvasCoords = (e) => {
            const rect = this.canvasOverlay.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: (clientX - rect.left) / rect.width,
                y: (clientY - rect.top) / rect.height,
                screenX: clientX,
                screenY: clientY
            };
        };

        const onStart = (e) => {
            if (!this.rawImage) return;
            const coords = getCanvasCoords(e);
            
            let minDist = 0.12;
            let foundIdx = -1;

            this.corners.forEach((pt, idx) => {
                const dx = pt.x - coords.x;
                const dy = pt.y - coords.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < minDist) {
                    minDist = dist;
                    foundIdx = idx;
                }
            });

            if (foundIdx !== -1) {
                this.activeCornerIndex = foundIdx;
                e.preventDefault();
                this.drawOverlay();
                this.showLoupe(coords);
            }
        };

        const onMove = (e) => {
            if (this.activeCornerIndex === -1) return;
            e.preventDefault();
            const coords = getCanvasCoords(e);

            this.corners[this.activeCornerIndex].x = Math.max(0, Math.min(1, coords.x));
            this.corners[this.activeCornerIndex].y = Math.max(0, Math.min(1, coords.y));

            this.drawOverlay();
            this.showLoupe(coords);
        };

        const onEnd = () => {
            if (this.activeCornerIndex !== -1) {
                this.activeCornerIndex = -1;
                this.hideLoupe();
                this.drawOverlay();
            }
        };

        this.canvasOverlay.addEventListener('mousedown', onStart);
        this.canvasOverlay.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onEnd);

        this.canvasOverlay.addEventListener('touchstart', onStart, { passive: false });
        this.canvasOverlay.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onEnd);
    }

    async startCamera() {
        if (this.stream) this.stopCamera();

        const isHttp = window.location.protocol === 'http:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1';

        if (isHttp || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.showHttpsWarning();
            return false;
        }

        try {
            // Requerir cámara trasera para tabletas y celulares
            const constraints = {
                video: {
                    facingMode: { ideal: "environment" },
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            if (this.videoElement) {
                this.videoElement.srcObject = this.stream;
                await this.videoElement.play();
            }
            return true;
        } catch (err) {
            console.warn("Intento cámara trasera falló, usando cámara básica:", err);
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: true });
                if (this.videoElement) {
                    this.videoElement.srcObject = this.stream;
                    await this.videoElement.play();
                }
                return true;
            } catch (err2) {
                console.error("Error acceso cámara:", err2);
                this.handleCameraError(err2);
                return false;
            }
        }
    }

    showHttpsWarning() {
        const httpsUrl = window.location.href.replace('http:', 'https:');
        const alertBox = document.getElementById('camera-https-alert');
        if (alertBox) {
            alertBox.classList.remove('d-none');
        } else {
            if (confirm("🔒 CONEXIÓN HTTPS REQUERIDA EN HOSTINGER:\n\nLos navegadores bloquean la cámara web en sitios HTTP no seguros.\n\n¿Desea redirigir a la versión segura de su sitio con HTTPS ahora?")) {
                window.location.href = httpsUrl;
            }
        }
    }

    handleCameraError(err) {
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            alert("⚠️ PERMISO DE CÁMARA BLOQUEADO:\n\nHa denegado el permiso de cámara en su navegador.\n\nPor favor presione el icono del candado o la cámara en la barra de direcciones de su navegador y cambie el permiso de la cámara a 'Permitir'.");
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            alert("⚠️ NO SE ENCONTRÓ CÁMARA:\n\nNo se detectó una cámara activa en este dispositivo. Puede usar el botón 'Tomar Foto Nativa / Galería' para escanear sus fotos.");
        } else {
            alert(`⚠️ No se pudo acceder a la cámara (${err.name || 'Error'}). Puede tomar la foto directamente con la cámara de su tableta usando el botón 'Foto Nativa / Galería'.`);
        }
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    }

    async toggleTorch() {
        if (!this.stream) return false;
        const track = this.stream.getVideoTracks()[0];
        if (!track) return false;

        const capabilities = track.getCapabilities ? track.getCapabilities() : {};
        if (capabilities.torch) {
            const settings = track.getSettings();
            const currentTorch = settings.torch || false;
            await track.applyConstraints({
                advanced: [{ torch: !currentTorch }]
            });
            return !currentTorch;
        } else {
            alert("Su dispositivo o navegador no soporta linterna.");
            return false;
        }
    }

    takeSnapshot() {
        if (!this.videoElement || !this.videoElement.videoWidth) return null;

        const vW = this.videoElement.videoWidth;
        const vH = this.videoElement.videoHeight;

        this.canvasSource.width = vW;
        this.canvasSource.height = vH;
        const ctx = this.canvasSource.getContext('2d');
        ctx.drawImage(this.videoElement, 0, 0, vW, vH);

        const img = new Image();
        img.src = this.canvasSource.toDataURL('image/jpeg', 0.95);
        img.onload = () => {
            this.loadCapturedImage(img);
        };
    }

    loadCapturedImage(imgElement) {
        this.rawImage = imgElement;
        this.rawWidth = imgElement.naturalWidth || imgElement.width;
        this.rawHeight = imgElement.naturalHeight || imgElement.height;
        this.rotationAngle = 0;

        this.autoDetectEdges();
        this.drawOverlay();
    }

    autoDetectEdges() {
        if (!this.rawImage) return;

        if (typeof cv !== 'undefined' && cv.Mat) {
            try {
                let src = cv.imread(this.rawImage);
                let dst = new cv.Mat();
                
                let maxDim = 500;
                let scale = Math.min(maxDim / src.cols, maxDim / src.rows);
                let newCols = Math.round(src.cols * scale);
                let newRows = Math.round(src.rows * scale);
                cv.resize(src, dst, new cv.Size(newCols, newRows));

                let gray = new cv.Mat();
                cv.cvtColor(dst, gray, cv.COLOR_RGBA2GRAY);
                cv.GaussianBlur(gray, gray, new cv.Size(5, 5), 0);
                
                let canny = new cv.Mat();
                cv.Canny(gray, canny, 75, 200);

                let contours = new cv.MatVector();
                let hierarchy = new cv.Mat();
                cv.findContours(canny, contours, hierarchy, cv.RETR_LIST, cv.CHAIN_APPROX_SIMPLE);

                let maxArea = 0;
                let bestPoly = null;

                for (let i = 0; i < contours.size(); ++i) {
                    let cnt = contours.get(i);
                    let area = cv.contourArea(cnt);
                    let peri = cv.arcLength(cnt, true);
                    let approx = new cv.Mat();
                    cv.approxPolyDP(cnt, approx, 0.02 * peri, true);

                    if (approx.rows === 4 && area > maxArea && area > (newCols * newRows * 0.10)) {
                        maxArea = area;
                        bestPoly = [];
                        for (let r = 0; r < 4; r++) {
                            bestPoly.push({
                                x: approx.data32S[r * 2] / newCols,
                                y: approx.data32S[r * 2 + 1] / newRows
                            });
                        }
                    }
                    approx.delete();
                    cnt.delete();
                }

                src.delete(); dst.delete(); gray.delete(); canny.delete(); contours.delete(); hierarchy.delete();

                if (bestPoly && bestPoly.length === 4) {
                    this.corners = this.orderCornerPoints(bestPoly);
                    return;
                }
            } catch (err) {
                console.warn("Fallo auto-detección OpenCV, aplicando cuadrilátero seguro:", err);
            }
        }

        // Default 85% centrado
        this.corners = [
            { x: 0.08, y: 0.08 },
            { x: 0.92, y: 0.08 },
            { x: 0.92, y: 0.92 },
            { x: 0.08, y: 0.92 }
        ];
    }

    orderCornerPoints(pts) {
        const sorted = [...pts];
        sorted.sort((a, b) => (a.x + a.y) - (b.x + b.y));
        const tl = sorted[0];
        const br = sorted[3];

        const remaining = [sorted[1], sorted[2]];
        remaining.sort((a, b) => (a.y - a.x) - (b.y - b.x));
        const tr = remaining[0];
        const bl = remaining[1];

        return [tl, tr, br, bl];
    }

    setCropPreset(preset) {
        if (preset === 'full') {
            this.corners = [
                { x: 0, y: 0 },
                { x: 1, y: 0 },
                { x: 1, y: 1 },
                { x: 0, y: 1 }
            ];
        } else if (preset === 'a4') {
            this.corners = [
                { x: 0.15, y: 0.05 },
                { x: 0.85, y: 0.05 },
                { x: 0.85, y: 0.95 },
                { x: 0.15, y: 0.95 }
            ];
        } else if (preset === 'id') {
            this.corners = [
                { x: 0.15, y: 0.25 },
                { x: 0.85, y: 0.25 },
                { x: 0.85, y: 0.75 },
                { x: 0.15, y: 0.75 }
            ];
        } else if (preset === 'auto') {
            this.autoDetectEdges();
        }
        this.drawOverlay();
    }

    rotateImage(direction = 'right') {
        if (direction === 'right') {
            this.rotationAngle = (this.rotationAngle + 90) % 360;
        } else {
            this.rotationAngle = (this.rotationAngle - 90 + 360) % 360;
        }
        this.drawOverlay();
    }

    drawOverlay() {
        if (!this.rawImage || !this.canvasOverlay) return;

        const wrapper = this.canvasOverlay.parentElement;
        const maxW = wrapper.clientWidth || 600;
        const maxH = wrapper.clientHeight || 360;

        const imgAspect = this.rawWidth / this.rawHeight;
        const containerAspect = maxW / maxH;

        let displayW, displayH;
        if (imgAspect > containerAspect) {
            displayW = maxW;
            displayH = maxW / imgAspect;
        } else {
            displayH = maxH;
            displayW = maxH * imgAspect;
        }

        displayW = Math.round(displayW);
        displayH = Math.round(displayH);

        this.canvasOverlay.width = displayW;
        this.canvasOverlay.height = displayH;
        this.canvasOverlay.style.width = displayW + 'px';
        this.canvasOverlay.style.height = displayH + 'px';

        const ctx = this.canvasOverlay.getContext('2d');
        ctx.clearRect(0, 0, displayW, displayH);

        ctx.save();
        ctx.drawImage(this.rawImage, 0, 0, displayW, displayH);
        ctx.restore();

        const pts = this.corners.map(p => ({
            x: p.x * displayW,
            y: p.y * displayH
        }));

        ctx.save();
        ctx.fillStyle = 'rgba(15, 23, 42, 0.65)';
        ctx.beginPath();
        ctx.rect(0, 0, displayW, displayH);
        ctx.moveTo(pts[0].x, pts[0].y);
        ctx.lineTo(pts[1].x, pts[1].y);
        ctx.lineTo(pts[2].x, pts[2].y);
        ctx.lineTo(pts[3].x, pts[3].y);
        ctx.closePath();
        ctx.fill('evenodd');
        ctx.restore();

        ctx.save();
        ctx.strokeStyle = '#00f2fe';
        ctx.lineWidth = 3;
        ctx.shadowColor = '#00f2fe';
        ctx.shadowBlur = 8;
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        ctx.lineTo(pts[1].x, pts[1].y);
        ctx.lineTo(pts[2].x, pts[2].y);
        ctx.lineTo(pts[3].x, pts[3].y);
        ctx.closePath();
        ctx.stroke();

        ctx.strokeStyle = 'rgba(255, 255, 255, 0.35)';
        ctx.lineWidth = 1;
        ctx.shadowBlur = 0;
        for (let i = 1; i <= 2; i++) {
            let t = i / 3;
            ctx.beginPath();
            ctx.moveTo(pts[0].x + (pts[3].x - pts[0].x) * t, pts[0].y + (pts[3].y - pts[0].y) * t);
            ctx.lineTo(pts[1].x + (pts[2].x - pts[1].x) * t, pts[1].y + (pts[2].y - pts[1].y) * t);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(pts[0].x + (pts[1].x - pts[0].x) * t, pts[0].y + (pts[1].y - pts[0].y) * t);
            ctx.lineTo(pts[3].x + (pts[2].x - pts[3].x) * t, pts[3].y + (pts[2].y - pts[3].y) * t);
            ctx.stroke();
        }
        ctx.restore();

        pts.forEach((pt, idx) => {
            const isActive = idx === this.activeCornerIndex;
            ctx.save();
            ctx.fillStyle = isActive ? '#ff0055' : '#00f2fe';
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 3;
            ctx.shadowColor = 'rgba(0,0,0,0.6)';
            ctx.shadowBlur = 6;

            ctx.beginPath();
            ctx.arc(pt.x, pt.y, isActive ? 16 : 12, 0, Math.PI * 2);
            ctx.fill();
            ctx.stroke();

            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, 4, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        });
    }

    showLoupe(coords) {
        if (!this.canvasLoupe || !this.rawImage || this.activeCornerIndex === -1) return;

        const loupeContainer = document.getElementById('loupe-container');
        if (loupeContainer) loupeContainer.classList.remove('d-none');

        const ctxLoupe = this.canvasLoupe.getContext('2d');
        const size = this.canvasLoupe.width = 130;
        this.canvasLoupe.height = 130;

        ctxLoupe.clearRect(0, 0, size, size);

        const imgX = coords.x * this.rawWidth;
        const imgY = coords.y * this.rawHeight;

        const zoom = 3;
        const sw = size / zoom;
        const sh = size / zoom;
        const sx = Math.max(0, Math.min(this.rawWidth - sw, imgX - sw / 2));
        const sy = Math.max(0, Math.min(this.rawHeight - sh, imgY - sh / 2));

        ctxLoupe.save();
        ctxLoupe.drawImage(this.rawImage, sx, sy, sw, sh, 0, 0, size, size);

        ctxLoupe.strokeStyle = '#ff0055';
        ctxLoupe.lineWidth = 2;
        ctxLoupe.beginPath();
        ctxLoupe.moveTo(size / 2 - 15, size / 2);
        ctxLoupe.lineTo(size / 2 + 15, size / 2);
        ctxLoupe.moveTo(size / 2, size / 2 - 15);
        ctxLoupe.lineTo(size / 2, size / 2 + 15);
        ctxLoupe.stroke();

        ctxLoupe.restore();
    }

    hideLoupe() {
        const loupeContainer = document.getElementById('loupe-container');
        if (loupeContainer) loupeContainer.classList.add('d-none');
    }

    processScan(filterName = 'magic') {
        if (!this.rawImage || !this.canvasProcessed) return;

        this.currentFilter = filterName;

        const ptsImg = this.corners.map(p => ({
            x: p.x * this.rawWidth,
            y: p.y * this.rawHeight
        }));

        const topW = Math.hypot(ptsImg[1].x - ptsImg[0].x, ptsImg[1].y - ptsImg[0].y);
        const botW = Math.hypot(ptsImg[2].x - ptsImg[3].x, ptsImg[2].y - ptsImg[3].y);
        const outW = Math.round(Math.max(topW, botW));

        const leftH = Math.hypot(ptsImg[3].x - ptsImg[0].x, ptsImg[3].y - ptsImg[0].y);
        const rightH = Math.hypot(ptsImg[2].x - ptsImg[1].x, ptsImg[2].y - ptsImg[1].y);
        const outH = Math.round(Math.max(leftH, rightH));

        if (typeof cv !== 'undefined' && cv.Mat) {
            try {
                let src = cv.imread(this.rawImage);
                
                let srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    ptsImg[0].x, ptsImg[0].y,
                    ptsImg[1].x, ptsImg[1].y,
                    ptsImg[2].x, ptsImg[2].y,
                    ptsImg[3].x, ptsImg[3].y
                ]);

                let dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [
                    0, 0,
                    outW, 0,
                    outW, outH,
                    0, outH
                ]);

                let M = cv.getPerspectiveTransform(srcTri, dstTri);
                let dst = new cv.Mat();
                let dsize = new cv.Size(outW, outH);
                cv.warpPerspective(src, dst, M, dsize, cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());

                cv.imshow(this.canvasProcessed, dst);

                src.delete(); srcTri.delete(); dstTri.delete(); M.delete(); dst.delete();

                this.applyFilterPostProcess(filterName);
                return;
            } catch (err) {
                console.warn("Fallo warpPerspective OpenCV, ejecutando fallback JS:", err);
            }
        }

        this.warpPerspectiveJS(ptsImg, outW, outH, filterName);
    }

    warpPerspectiveJS(pts, outW, outH, filterName) {
        this.canvasProcessed.width = outW;
        this.canvasProcessed.height = outH;
        const ctxOut = this.canvasProcessed.getContext('2d');

        ctxOut.save();
        ctxOut.drawImage(this.rawImage, 
            pts[0].x, pts[0].y, outW, outH,
            0, 0, outW, outH
        );
        ctxOut.restore();

        this.applyFilterPostProcess(filterName);
    }

    applyFilterPostProcess(filterName) {
        const ctx = this.canvasProcessed.getContext('2d');
        const w = this.canvasProcessed.width;
        const h = this.canvasProcessed.height;
        if (!w || !h) return;

        const imgData = ctx.getImageData(0, 0, w, h);
        const data = imgData.data;

        if (filterName === 'magic') {
            let minLum = 255, maxLum = 0;
            for (let i = 0; i < data.length; i += 4) {
                const lum = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                if (lum < minLum) minLum = lum;
                if (lum > maxLum) maxLum = lum;
            }

            const range = Math.max(1, maxLum - minLum);

            for (let i = 0; i < data.length; i += 4) {
                let r = ((data[i] - minLum) / range) * 255;
                let g = ((data[i + 1] - minLum) / range) * 255;
                let b = ((data[i + 2] - minLum) / range) * 255;

                r = Math.min(255, Math.pow(r / 255, 0.75) * 270);
                g = Math.min(255, Math.pow(g / 255, 0.75) * 270);
                b = Math.min(255, Math.pow(b / 255, 0.75) * 270);

                data[i] = r;
                data[i + 1] = g;
                data[i + 2] = b;
            }
        } else if (filterName === 'grayscale') {
            for (let i = 0; i < data.length; i += 4) {
                const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                data[i] = gray;
                data[i + 1] = gray;
                data[i + 2] = gray;
            }
        } else if (filterName === 'binary') {
            for (let i = 0; i < data.length; i += 4) {
                const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                const bw = gray > 140 ? 255 : 0;
                data[i] = bw;
                data[i + 1] = bw;
                data[i + 2] = bw;
            }
        }

        ctx.putImageData(imgData, 0, 0);
    }

    saveCurrentPageToDoc() {
        if (!this.canvasProcessed || !this.canvasProcessed.width) return null;

        const dataUrl = this.canvasProcessed.toDataURL('image/jpeg', 0.92);
        const pageObj = {
            id: Date.now(),
            dataUrl: dataUrl,
            width: this.canvasProcessed.width,
            height: this.canvasProcessed.height
        };

        this.scannedPages.push(pageObj);
        this.currentPageIndex = this.scannedPages.length - 1;

        return pageObj;
    }

    deletePage(index) {
        if (index >= 0 && index < this.scannedPages.length) {
            this.scannedPages.splice(index, 1);
            if (this.currentPageIndex >= this.scannedPages.length) {
                this.currentPageIndex = this.scannedPages.length - 1;
            }
        }
        return this.scannedPages;
    }

    resetDoc() {
        this.scannedPages = [];
        this.currentPageIndex = -1;
        this.rawImage = null;
    }

    async getProcessedBlob(type = 'image/jpeg', quality = 0.92) {
        if (!this.canvasProcessed) return null;

        return new Promise((resolve) => {
            this.canvasProcessed.toBlob((blob) => {
                resolve(blob);
            }, type, quality);
        });
    }
}
