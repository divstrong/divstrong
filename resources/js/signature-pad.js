document.addEventListener('alpine:init', () => {
    Alpine.data('signaturePad', () => ({
        drawing: false,
        canvas: null,
        ctx: null,
        hasSignature: false,

        init() {
            this.canvas = this.$refs.canvas;
            this.ctx = this.canvas.getContext('2d');

            const rect = this.canvas.getBoundingClientRect();
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;

            this.ctx.strokeStyle = '#000000';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

            // Mouse events
            this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
            this.canvas.addEventListener('mousemove', (e) => this.draw(e));
            this.canvas.addEventListener('mouseup', () => this.stopDrawing());
            this.canvas.addEventListener('mouseleave', () => this.stopDrawing());

            // Touch events
            this.canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                this.startDrawing(e.touches[0]);
            });
            this.canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                this.draw(e.touches[0]);
            });
            this.canvas.addEventListener('touchend', () => this.stopDrawing());

            // Handle resize
            window.addEventListener('resize', () => {
                const imageData = this.canvas.toDataURL();
                const rect = this.canvas.getBoundingClientRect();
                this.canvas.width = rect.width;
                this.canvas.height = rect.height;
                this.ctx.strokeStyle = '#000000';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
            });
        },

        getPos(e) {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top,
            };
        },

        startDrawing(e) {
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
                this.$wire.set('signatureData', dataUrl);
            }
        },

        clearSignature() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.hasSignature = false;
            this.$wire.set('signatureData', '');
        },
    }));
});
