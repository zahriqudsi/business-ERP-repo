/**
 * Lightweight signature pad for mouse, finger and stylus (Apple Pencil).
 * Uses Pointer Events so it works on iPad / Android tablets and desktops.
 *
 *   const pad = new SignaturePad(canvasEl);
 *   pad.isEmpty(); pad.clear(); pad.toDataURL();
 */
class SignaturePad {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.color = options.color || '#0b2545';
        this.minWidth = options.minWidth || 1.2;
        this.maxWidth = options.maxWidth || 3.2;
        this.onChange = options.onChange || (() => {});
        this.strokes = [];      // stored in CSS pixels so we can redraw after resize
        this.current = null;

        this._down = this._down.bind(this);
        this._move = this._move.bind(this);
        this._up = this._up.bind(this);

        canvas.addEventListener('pointerdown', this._down);
        canvas.addEventListener('pointermove', this._move);
        canvas.addEventListener('pointerup', this._up);
        canvas.addEventListener('pointercancel', this._up);
        canvas.addEventListener('pointerleave', this._up);

        // Re-fit on rotation / resize (common when an iPad is turned)
        this._resizeObserver = new ResizeObserver(() => this.resize());
        this._resizeObserver.observe(canvas);
        this.resize();
    }

    resize() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = this.canvas.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        this.canvas.width = rect.width * ratio;
        this.canvas.height = rect.height * ratio;
        this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        this.redraw();
    }

    _point(e) {
        const rect = this.canvas.getBoundingClientRect();
        // Pen pressure when available; mouse/finger report 0.5 by default
        const pressure = e.pointerType === 'pen' && e.pressure > 0 ? e.pressure : 0.5;
        return { x: (e.clientX - rect.left) / rect.width, y: (e.clientY - rect.top) / rect.height, p: pressure };
    }

    _down(e) {
        if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
        e.preventDefault();
        try { this.canvas.setPointerCapture(e.pointerId); } catch (err) { /* capture is best-effort */ }
        this.current = [this._point(e)];
        this.strokes.push(this.current);
        this._drawDot(this.current[0]);
    }

    _move(e) {
        if (!this.current) return;
        e.preventDefault();
        // Coalesced events give smoother lines on fast pens, but can be empty on some browsers
        const coalesced = e.getCoalescedEvents ? e.getCoalescedEvents() : [];
        const events = coalesced.length ? coalesced : [e];
        for (const ev of events) {
            this.current.push(this._point(ev));
            this._drawSegment(this.current, this.current.length - 1);
        }
    }

    _up() {
        if (!this.current) return;
        this.current = null;
        this.onChange(this);
    }

    _abs(pt) {
        const rect = this.canvas.getBoundingClientRect();
        return { x: pt.x * rect.width, y: pt.y * rect.height, p: pt.p };
    }

    _width(p) {
        return this.minWidth + (this.maxWidth - this.minWidth) * p;
    }

    _drawDot(pt) {
        const a = this._abs(pt);
        this.ctx.fillStyle = this.color;
        this.ctx.beginPath();
        this.ctx.arc(a.x, a.y, this._width(a.p) / 2, 0, Math.PI * 2);
        this.ctx.fill();
    }

    _drawSegment(stroke, i) {
        if (i < 1) return;
        const ctx = this.ctx;
        const p0 = this._abs(stroke[Math.max(i - 2, 0)]);
        const p1 = this._abs(stroke[i - 1]);
        const p2 = this._abs(stroke[i]);
        // Smooth with a quadratic curve through midpoints
        const m1 = { x: (p0.x + p1.x) / 2, y: (p0.y + p1.y) / 2 };
        const m2 = { x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2 };
        ctx.strokeStyle = this.color;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = this._width((p1.p + p2.p) / 2);
        ctx.beginPath();
        ctx.moveTo(m1.x, m1.y);
        ctx.quadraticCurveTo(p1.x, p1.y, m2.x, m2.y);
        ctx.stroke();
    }

    redraw() {
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.clearRect(0, 0, rect.width, rect.height);
        for (const stroke of this.strokes) {
            this._drawDot(stroke[0]);
            for (let i = 1; i < stroke.length; i++) this._drawSegment(stroke, i);
        }
    }

    undo() {
        this.strokes.pop();
        this.redraw();
        this.onChange(this);
    }

    clear() {
        this.strokes = [];
        this.redraw();
        this.onChange(this);
    }

    isEmpty() {
        return this.strokes.length === 0;
    }

    toDataURL() {
        return this.canvas.toDataURL('image/png');
    }
}

window.SignaturePad = SignaturePad;
