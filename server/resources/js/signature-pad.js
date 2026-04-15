document.addEventListener('alpine:init', () => {
    function createSignaturePad(wireProperty) {
        return {
            drawing: false,
            canvas: null,
            ctx: null,
            hasSignature: false,
            _initialized: false,
            _dpr: 1,

            init() {
                this.canvas = this.$refs.canvas;
                this.ctx = this.canvas.getContext('2d');
                this._dpr = window.devicePixelRatio || 1;
                this.setupCanvas();
                this._initialized = true;

                this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
                this.canvas.addEventListener('mousemove', (e) => this.draw(e));
                this.canvas.addEventListener('mouseup', () => this.stopDrawing());
                this.canvas.addEventListener('mouseleave', () => this.stopDrawing());

                this.canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    this.startDrawing(e.touches[0]);
                }, { passive: false });
                this.canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    this.draw(e.touches[0]);
                }, { passive: false });
                this.canvas.addEventListener('touchend', () => this.stopDrawing());
            },

            setupCanvas() {
                const rect = this.canvas.getBoundingClientRect();
                const dpr = this._dpr;

                this.canvas.width = rect.width * dpr;
                this.canvas.height = rect.height * dpr;

                this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                this.ctx.strokeStyle = '#000000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
            },

            getPos(e) {
                const rect = this.canvas.getBoundingClientRect();
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top,
                };
            },

            startDrawing(e) {
                // Ensure canvas dimensions are still correct before drawing
                const rect = this.canvas.getBoundingClientRect();
                const expectedWidth = rect.width * this._dpr;
                if (Math.abs(this.canvas.width - expectedWidth) > 1) {
                    this.setupCanvas();
                }

                this.drawing = true;
                this.hasSignature = true;
                const pos = this.getPos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
            },

            draw(e) {
                if (!this.drawing) return;
                const pos = this.getPos(e);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
            },

            stopDrawing() {
                if (this.drawing) {
                    this.drawing = false;
                    const dataUrl = this.canvas.toDataURL('image/png');
                    this.$wire.set(wireProperty, dataUrl);
                }
            },

            clearSignature() {
                this.setupCanvas();
                this.hasSignature = false;
                this.$wire.set(wireProperty, '');
            },
        };
    }

    Alpine.data('signaturePad', () => createSignaturePad('signatureData'));
    Alpine.data('crSignaturePad', () => createSignaturePad('crSignatureData'));
    Alpine.data('tcSignaturePad', () => createSignaturePad('tcSignatureData'));
});
