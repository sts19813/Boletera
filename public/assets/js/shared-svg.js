/**
 * =========================
 * COLORES POR ESTATUS
 * =========================
 */
const ticketStatusColors = {
    available: 'rgba(52,199,89,.45)',
    sold: 'rgba(200,0,0,.45)',
    reserved: 'rgba(255,200,0,.6)',
    locked_sale: 'rgba(120,120,120,.8)'
};

/**
 * =========================
 * UTILIDAD PINTAR <g>
 * =========================
 */
function paintGroup(group, color) {
    group.querySelectorAll('*').forEach(el => {
        el.style.setProperty('fill', color, 'important');
    });
    group.style.setProperty('fill', color, 'important');
}

/**
 * =========================
 * HEX8 → RGBA
 * =========================
 */
function hex8ToRgba(hex) {
    hex = hex.replace('#', '');
    if (hex.length !== 8) return hex;

    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    const a = parseInt(hex.slice(6, 8), 16) / 255;

    return `rgba(${r},${g},${b},${a.toFixed(2)})`;
}

/**
 * =========================
 * INICIALIZACIÓN SVG
 * =========================
 */
document.addEventListener('DOMContentLoaded', () => {

    if (!Array.isArray(window.dbLotes) || !Array.isArray(window.preloadedLots)) return;

    window.dbLotes.forEach(mapping => {

        if (!mapping.svg_selector) return;

        const group = document.getElementById(mapping.svg_selector);
        if (!group) return;

        const ticket = window.preloadedLots.find(t => t.id == mapping.ticket_id);
        if (!ticket) return;

        /**
         * COLOR BASE
         */
        let baseColor = ticketStatusColors[ticket.status] || ticketStatusColors.locked_sale;

        if (mapping.color) {
            baseColor = hex8ToRgba(mapping.color);
        }

        paintGroup(group, baseColor);

        /**
         * DATASET
         */
        group.dataset.mapped = "1";
        group.dataset.ticketId = ticket.id;
        group.dataset.status = ticket.status || 'locked_sale';
        group.dataset.mappingId = mapping.id;

        /**
         * CURSOR SEGÚN MODO
         */
        if (!window.isAdmin && ticket.status === 'available') {
            group.style.cursor = 'pointer';
        } else {
            group.style.cursor = 'not-allowed';
        }

        /**
         * TOOLTIP
         */
        const statusLabels = {
            available: 'Disponible',
            sold: 'Vendido',
            reserved: 'Apartado',
            locked_sale: 'Bloqueado'
        };

        const priceText = ticket.total_price
            ? `$${Number(ticket.total_price).toLocaleString('es-MX')}`
            : '—';

        const tooltipHtml = `
            <div style="text-align:left">
                <strong>${ticket.name}</strong><br>
                <small>Estatus: ${statusLabels[ticket.status]}</small><br>
                <small>Precio: ${priceText}</small>
            </div>
        `;

        group.setAttribute('data-bs-toggle', 'tooltip');
        group.setAttribute('data-bs-html', 'true');
        group.setAttribute('data-bs-title', tooltipHtml);

        new bootstrap.Tooltip(group);

        /**
         * HOVER ACTIVO
         */
        if (mapping.color_active) {
            const activeColor = hex8ToRgba(mapping.color_active);

            group.addEventListener('mouseenter', () => {
                paintGroup(group, activeColor);
            });

            group.addEventListener('mouseleave', () => {
                paintGroup(group, baseColor);
            });
        }
    });
});

/**
 * =========================
 * EXPONER UTILIDAD
 * =========================
 */
window.getTicketFromGroup = function (group) {
    const ticketId = group.dataset.ticketId;
    if (!ticketId) return null;
    return window.preloadedLots.find(t => t.id == ticketId);
};
